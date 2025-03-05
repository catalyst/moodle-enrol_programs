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
 * customcert activity purge test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\reset\mod_customcert
 */
final class mod_customcert_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
        if (!get_config('mod_customcert', 'version')) {
            $this->markTestSkipped('mod_customcert is not installed');
        }
    }

    public function test_purge_data(): void {
        global $DB;

        $course1 = $this->getDataGenerator()->create_course();
        $customcert1 = $this->getDataGenerator()->create_module('customcert', ['course' => $course1->id]);
        $cm1 = get_coursemodule_from_instance('customcert', $customcert1->id);
        $context1 = \context_module::instance($cm1->id);

        $course2 = $this->getDataGenerator()->create_course();
        $customcert2 = $this->getDataGenerator()->create_module('customcert', ['course' => $course2->id]);
        $cm2 = get_coursemodule_from_instance('customcert', $customcert2->id);
        $context2 = \context_module::instance($cm2->id);

        $student1 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student1->id, $course1->id, 'student');
        $this->getDataGenerator()->enrol_user($student1->id, $course2->id, 'student');
        $student2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($student2->id, $course1->id, 'student');
        $teacher = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->enrol_user($teacher->id, $course1->id, 'teacher');
        $this->getDataGenerator()->enrol_user($teacher->id, $course2->id, 'teacher');

        $customcertissue = new \stdClass();
        $customcertissue->customcertid = $customcert1->id;
        $customcertissue->userid = $student1->id;
        $customcertissue->code = \mod_customcert\certificate::generate_code();
        $customcertissue->timecreated = time();
        $DB->insert_record('customcert_issues', $customcertissue);

        $customcertissue = new \stdClass();
        $customcertissue->customcertid = $customcert1->id;
        $customcertissue->userid = $student2->id;
        $customcertissue->code = \mod_customcert\certificate::generate_code();
        $customcertissue->timecreated = time();
        $DB->insert_record('customcert_issues', $customcertissue);

        $customcertissue = new \stdClass();
        $customcertissue->customcertid = $customcert2->id;
        $customcertissue->userid = $student1->id;
        $customcertissue->code = \mod_customcert\certificate::generate_code();
        $customcertissue->timecreated = time();
        $DB->insert_record('customcert_issues', $customcertissue);

        /** @var \enrol_programs_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        $this->assertCount(3, $DB->get_records('customcert_issues', []));

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);

        $this->assertCount(2, $DB->get_records('customcert_issues', []));
        $this->assertCount(1, $DB->get_records('customcert_issues', ['customcertid' => $customcert1->id, 'userid' => $student2->id]));
        $this->assertCount(1, $DB->get_records('customcert_issues', ['customcertid' => $customcert2->id, 'userid' => $student1->id]));
    }
}
