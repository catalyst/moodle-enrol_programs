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
 * Questionnaire activity purge test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\reset\mod_questionnaire
 */
final class mod_questionnaire_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
        if (!get_config('mod_questionnaire', 'version')) {
            $this->markTestSkipped('mod_questionnaire is not installed');
        }
    }

    public function test_purge_data(): void {
        global $DB;

        /** @var \mod_questionnaire_generator $questionnairegenerator */
        $questionnairegenerator = $this->getDataGenerator()->get_plugin_generator('mod_questionnaire');

        $this->setAdminUser();
        $questionnairegenerator->create_and_fully_populate(2, 2, 2, 1);
        list($course1, $course2) = array_values($DB->get_records_select('course', "category > 0", [], 'id ASC'));
        list($student1, $student2) = array_values($DB->get_records_select('user', "username != 'guest' AND username != 'admin'", [], 'id ASC'));

        /** @var \enrol_programs_generator $programgenerator */
        $programgenerator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $this->setUser(null);
        $program1 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program1->id, 'courseid' => $course1->id]);
        $program2 = $programgenerator->create_program([]);
        $programgenerator->create_program_item(['programid' => $program2->id, 'courseid' => $course2->id]);

        // No need for real testing of unsupported modules, just make sure there are no errors.

        course_reset::purge_enrolments($student1, $program1->id);
        course_reset::purge_standard($student1, $program1->id);
    }
}
