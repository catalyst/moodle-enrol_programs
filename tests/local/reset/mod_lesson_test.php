<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace enrol_programs\local\reset;

use enrol_programs\local\course_reset;

/**
 * Lesson activity purge test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\reset\mod_lesson
 */
final class mod_lesson_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_lesson_generator $lessongenerator */
        $lessongenerator = $this->getDataGenerator()->get_plugin_generator('mod_lesson');

        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course();
        $lesson1 = $lessongenerator->create_instance(['course' => $course1->id]);
        $tfrecord1 = $lessongenerator->create_question_truefalse($lesson1);
        $cm1 = get_coursemodule_from_instance('lesson', $lesson1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $lesson2 = $lessongenerator->create_instance(['course' => $course2->id]);
        $tfrecord2 = $lessongenerator->create_question_truefalse($lesson2);
        $cm2 = get_coursemodule_from_instance('lesson', $lesson2->id);
        $context2 = \context_module::instance($cm2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $this->setUser($student1);
        \mod_lesson_external::launch_attempt($lesson1->id);
        $data = array(
            array(
                'name' => 'answerid',
                'value' => $DB->get_field('lesson_answers', 'id', array('pageid' => $tfrecord1->id, 'jumpto' => -1)),
            ),
            array(
                'name' => '_qf__lesson_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        \mod_lesson_external::process_page($lesson1->id, $tfrecord1->id, $data);
        \mod_lesson_external::finish_attempt($lesson1->id);

        $this->setUser($student2);
        \mod_lesson_external::launch_attempt($lesson1->id);
        $data = array(
            array(
                'name' => 'answerid',
                'value' => $DB->get_field('lesson_answers', 'id', array('pageid' => $tfrecord1->id, 'jumpto' => -1)),
            ),
            array(
                'name' => '_qf__lesson_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        \mod_lesson_external::process_page($lesson1->id, $tfrecord1->id, $data);
        \mod_lesson_external::finish_attempt($lesson1->id);

        $this->setUser($student1);
        \mod_lesson_external::launch_attempt($lesson2->id);
        $data = array(
            array(
                'name' => 'answerid',
                'value' => $DB->get_field('lesson_answers', 'id', array('pageid' => $tfrecord2->id, 'jumpto' => -1)),
            ),
            array(
                'name' => '_qf__lesson_display_answer_form_truefalse',
                'value' => 1,
            )
        );
        \mod_lesson_external::process_page($lesson2->id, $tfrecord2->id, $data);
        \mod_lesson_external::finish_attempt($lesson2->id);

        /** @var \enrol_programs_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('lesson_grades', []));
        $this->assertCount(1, $DB->get_records('lesson_grades', ['userid' => $student1->id, 'lessonid' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('lesson_grades', ['userid' => $student1->id, 'lessonid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('lesson_grades', ['userid' => $student2->id, 'lessonid' => $cm1->instance]));
        $this->assertCount(3, $DB->get_records('lesson_attempts', []));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student1->id, 'lessonid' => $cm1->instance]));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student1->id, 'lessonid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student2->id, 'lessonid' => $cm1->instance]));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('lesson_attempts', []));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student1->id, 'lessonid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student2->id, 'lessonid' => $cm1->instance]));
        $this->assertCount(2, $DB->get_records('lesson_attempts', []));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student1->id, 'lessonid' => $cm2->instance]));
        $this->assertCount(1, $DB->get_records('lesson_attempts', ['userid' => $student2->id, 'lessonid' => $cm1->instance]));
    }
}
