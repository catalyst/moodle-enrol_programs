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
 * Scorm activity purge test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\reset\mod_scorm
 */
final class mod_scorm_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_purge_data(): void {
        global $DB, $CFG;

        /** @var \mod_scorm_generator $scormgenerator */
        $scormgenerator = $this->getDataGenerator()->get_plugin_generator('mod_scorm');

        $this->setAdminUser();

        $course1 = $this->getDataGenerator()->create_course();
        $scorm1 = $scormgenerator->create_instance([
            'course' => $course1->id,
            'packagefilepath' => $CFG->dirroot.'/mod/scorm/tests/packages/singlescobasic.zip'
        ]);
        $cm1 = get_coursemodule_from_instance('scorm', $scorm1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $scorm2 = $scormgenerator->create_instance([
            'course' => $course2->id,
            'packagefilepath' => $CFG->dirroot.'/mod/scorm/tests/packages/singlescobasic.zip'
        ]);
        $cm2 = get_coursemodule_from_instance('scorm', $scorm2->id);
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
        $scoes = scorm_get_scoes($scorm1->id);
        $sco = array_shift($scoes);
        scorm_insert_track($student1->id, $scorm1->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        scorm_insert_track($student1->id, $scorm1->id, $sco->id, 2, 'cmi.core.lesson_status', 'completed');

        $this->setUser($student2);
        $scoes = scorm_get_scoes($scorm1->id);
        $sco = array_shift($scoes);
        scorm_insert_track($student2->id, $scorm1->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        scorm_insert_track($student2->id, $scorm1->id, $sco->id, 2, 'cmi.core.lesson_status', 'completed');

        $this->setUser($student1);
        $scoes = scorm_get_scoes($scorm2->id);
        $sco = array_shift($scoes);
        scorm_insert_track($student1->id, $scorm2->id, $sco->id, 1, 'cmi.core.lesson_status', 'completed');
        scorm_insert_track($student1->id, $scorm2->id, $sco->id, 2, 'cmi.core.lesson_status', 'completed');

        /** @var \enrol_programs_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(6, $DB->get_records('scorm_attempt', []));
        $this->assertCount(2, $DB->get_records('scorm_attempt', ['userid' => $student1->id, 'scormid' => $cm1->instance]));
        $this->assertCount(2, $DB->get_records('scorm_attempt', ['userid' => $student1->id, 'scormid' => $cm2->instance]));
        $this->assertCount(2, $DB->get_records('scorm_attempt', ['userid' => $student2->id, 'scormid' => $cm1->instance]));
        $this->assertCount(6, $DB->get_records('scorm_scoes_value', []));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(4, $DB->get_records('scorm_attempt', []));
        $this->assertCount(2, $DB->get_records('scorm_attempt', ['userid' => $student1->id, 'scormid' => $cm2->instance]));
        $this->assertCount(2, $DB->get_records('scorm_attempt', ['userid' => $student2->id, 'scormid' => $cm1->instance]));
        $this->assertCount(4, $DB->get_records('scorm_scoes_value', []));
        $attempts = $DB->get_records('scorm_attempt', []);
        foreach ($attempts as $attempt) {
            $this->assertCount(1, $DB->get_records('scorm_scoes_value', ['attemptid' => $attempt->id]));
        }
    }
}
