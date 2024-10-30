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

namespace enrol_programs\local;

use enrol_programs\local\content\top;
use enrol_programs\local\content\set;

/**
 * Program helper test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\upload
 */
final class upload_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_process(): void {
        global $DB;
        $this->setAdminUser();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        /** @var \customfield_training_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('customfield_training');
        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        $item0x2 = $top0->append_training($set0, $framework1->id);

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);

        $rawprograms = export::export_programs('1=1', []);
        $oldprograms = unserialize(serialize($rawprograms));
        $this->assertCount(3, $rawprograms);

        program::delete_program($program0->id);
        program::delete_program($program1->id);
        program::delete_program($program2->id);

        upload::validate_references($rawprograms);

        $data = (object)[
            'usecategory' => 1,
            'encoding' => 'UTF-8',
        ];
        upload::process($data, $rawprograms);

        $rawprograms2 = export::export_programs('1=1', []);
        $this->assertEquals($oldprograms, $rawprograms2);

        $program0x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program0->idnumber], '*', MUST_EXIST);
        $this->assertSame($program0->contextid, $program0x->contextid);
        $program1x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program1->idnumber], '*', MUST_EXIST);
        $this->assertSame($program1->contextid, $program1x->contextid);
        $program2x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program2->idnumber], '*', MUST_EXIST);
        $this->assertSame($program2->contextid, $program2x->contextid);

        program::delete_program($program0x->id);
        program::delete_program($program1x->id);
        program::delete_program($program2x->id);

        upload::validate_references($rawprograms2);
        $data = (object)[
            'usecategory' => 0,
            'contextid' => $catcontext2->id,
            'encoding' => 'UTF-8',
        ];
        upload::process($data, $rawprograms2);
        $program0x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program0->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program0x->contextid);
        $program1x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program1->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program1x->contextid);
        $program2x = $DB->get_record('enrol_programs_programs', ['idnumber' => $program2->idnumber], '*', MUST_EXIST);
        $this->assertSame($catcontext2->id, (int)$program2x->contextid);
    }

    public function test_decode_json_file(): void {
        $this->setAdminUser();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        /** @var \customfield_training_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('customfield_training');
        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        $item0x2 = $top0->append_training($set0, $framework1->id);

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);

        $rawprograms = export::export_programs('1=1', []);

        $data = (object)[
            'contextid' => 0,
            'archived' => 0,
        ];
        $file = export::export_json($data);

        $dir = make_request_directory();
        $packer = get_file_packer('application/zip');
        $packer->extract_to_pathname($file, $dir);
        $jsonfile = "$dir/programs.json";

        $result = upload::decode_json_file($jsonfile, 'UTF-8');

        $this->assertEquals($rawprograms, $result);
    }

    public function test_decode_csv_files(): void {
        $this->setAdminUser();

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program0 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program1 = $generator->create_program([
            'contextid' => $catcontext1->id,
            'timeallocationstart' => strtotime('2024-08-15T15:20:01+01:00'),
            'timeallocationend' => strtotime('2030-01-15T16:52:02+01:00'),
            'sources' => ['manual' => [], 'approval' => [], 'selfallocation' => []],
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext2->id,
            'startdate' => ['type' => 'date', 'date' => strtotime('2024-01-02T15:20:01+01:00')],
            'duedate' => ['type' => 'delay', 'delay' => 'P20D'],
            'enddate' => ['type' => 'delay', 'delay' => 'P2M'],
            'sources' => ['manual' => [],
                'approval' => ['approval_allowrequest' => 0],
                'selfallocation' => ['selfallocation_allowsignup' => 1, 'selfallocation_key' => 'abc', 'selfallocation_maxusers' => 10]],
        ]);

        $course1 = $this->getDataGenerator()->create_course();
        $course2 = $this->getDataGenerator()->create_course();
        /** @var \customfield_training_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('customfield_training');
        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $data = (object)[
            'name' => 'Some framework',
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $top0 = top::load($program0->id);
        $set0 = $top0->append_set($top0, ['fullname' => 'Optional set', 'sequencetype' => set::SEQUENCE_TYPE_ATLEAST, 'minprerequisites' => 2]);
        $item0x1 = $top0->append_course($set0, $course1->id);
        $item0x2 = $top0->append_training($set0, $framework1->id);

        $top1 = top::load($program1->id);
        $top1->update_set($top1, ['sequencetype' => 'allinorder', 'completiondelay' => 3]);
        $set1 = $top1->append_set($top1, ['fullname' => 'Another set', 'sequencetype' => set::SEQUENCE_TYPE_MINPOINTS, 'minpoints' => 3]);
        $item1x1 = $top1->append_course($set1, $course1->id, ['points' => 3]);
        $item1x2 = $top1->append_training($set1, $framework1->id, ['completiondelay' => 11]);

        $rawprograms = export::export_programs('1=1', []);
        $data = (object)[
            'contextid' => 0,
            'archived' => 0,
            'delimiter_name' => 'comma',
            'encoding' => 'UTF-8',
        ];
        $file = export::export_csv($data);

        $dir = make_request_directory();
        $packer = get_file_packer('application/zip');
        $packer->extract_to_pathname($file, $dir);
        $csvfiles = [
            "$dir/programs.csv",
            "$dir/programs_contents.csv",
            "$dir/programs_sources.csv",
        ];
        foreach ($csvfiles as $csvfile) {
            $this->assertFileExists($csvfile);
        }

        $result = upload::decode_csv_files($csvfiles, 'UTF-8');

        $this->assertEquals($rawprograms, $result);
    }
}