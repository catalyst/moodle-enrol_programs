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

/**
 * Program helper test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\program
 */
final class program_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_add_program() {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $program = program::add_program($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$syscontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame('', $program->description);
        $this->assertSame('1', $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->public);
        $this->assertSame('0', $program->archived);
        $this->assertSame('0', $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);

        $category = $this->getDataGenerator()->create_category([]);
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'public' => '1',
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $this->setCurrentTimeStart();
        $program = program::add_program($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($data->public, $program->public);
        $this->assertSame($data->archived, $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertTimeCurrent($program->timecreated);
    }

    public function test_update_program_general() {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::add_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'public' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_program_general($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame((string)$catcontext->id, $program->contextid);
        $this->assertSame($data->fullname, $program->fullname);
        $this->assertSame($data->idnumber, $program->idnumber);
        $this->assertSame($data->description, $program->description);
        $this->assertSame($data->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('0', $program->public);
        $this->assertSame($data->archived, $program->archived);
        $this->assertSame($data->creategroups, $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('enrol_programs_cohorts', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame([], array_values($cohorts));
    }

    public function test_update_program_visibility() {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::add_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'public' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_program_visibility($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame($oldprogram->contextid, $program->contextid);
        $this->assertSame($oldprogram->fullname, $program->fullname);
        $this->assertSame($oldprogram->idnumber, $program->idnumber);
        $this->assertSame($oldprogram->description, $program->description);
        $this->assertSame($oldprogram->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame('1', $program->public);
        $this->assertSame('0', $program->archived);
        $this->assertSame('0', $program->creategroups);
        $this->assertSame(null, $program->timeallocationstart);
        $this->assertSame(null, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('enrol_programs_cohorts', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame($data->cohorts, array_values($cohorts));
    }

    public function test_update_program_allocation() {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $this->setCurrentTimeStart();
        $oldprogram = program::add_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'id' => $oldprogram->id,
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'public' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
        ];

        $program = program::update_program_allocation($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame($oldprogram->contextid, $program->contextid);
        $this->assertSame($oldprogram->fullname, $program->fullname);
        $this->assertSame($oldprogram->idnumber, $program->idnumber);
        $this->assertSame($oldprogram->description, $program->description);
        $this->assertSame($oldprogram->descriptionformat, $program->descriptionformat);
        $this->assertSame('[]', $program->presentationjson);
        $this->assertSame($oldprogram->public, $program->public);
        $this->assertSame($oldprogram->archived, $program->archived);
        $this->assertSame($oldprogram->creategroups, $program->creategroups);
        $this->assertSame($data->timeallocationstart, $program->timeallocationstart);
        $this->assertSame($data->timeallocationend, $program->timeallocationend);
        $this->assertSame('{"type":"allocation"}', $program->startdatejson);
        $this->assertSame('{"type":"notset"}', $program->duedatejson);
        $this->assertSame('{"type":"notset"}', $program->enddatejson);
        $this->assertSame($oldprogram->timecreated, $program->timecreated);

        $cohorts = $DB->get_records_menu('enrol_programs_cohorts', ['programid' => $program->id], 'cohortid ASC', 'id, cohortid');
        $this->assertSame([], array_values($cohorts));
    }

    public function test_import_program_allocation() {
        global $DB;

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program1 = $generator->create_program($data);

        $category = $this->getDataGenerator()->create_category([]);
        $cohort1 = $this->getDataGenerator()->create_cohort();
        $cohort2 = $this->getDataGenerator()->create_cohort();
        $cohort3 = $this->getDataGenerator()->create_cohort();
        $catcontext = \context_coursecat::instance($category->id);
        $data = (object)[
            'fullname' => 'Some other program',
            'idnumber' => 'SP2',
            'contextid' => $catcontext->id,
            'description' => 'Some desc',
            'descriptionformat' => '2',
            'presentation' => ['some' => 'test'],
            'public' => '1',
            'cohorts' => [$cohort1->id, $cohort2->id],
            'archived' => '1',
            'creategroups' => '1',
            'timeallocationstart' => (string)(time() - 60 * 60 * 24),
            'timeallocationend' => (string)(time() + 60 * 60 * 24),
            'sources' => [
                'manual' => [],
                'approval' => [],
                'cohort' => ['cohorts' => [$cohort2->id]],
                'selfallocation' => [],
            ],
        ];
        $program2 = $generator->create_program($data);
        $data = (object)[
            'id' => $program2->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program2 = program::update_program_scheduling($data);
        $scohort2 = $DB->get_record('enrol_programs_sources', ['programid' => $program2->id, 'type' => 'cohort'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
        ];
        $program1x = program::import_program_allocation($data);
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importallocationstart' => 1,
            'importallocationend' => 1,
        ];
        $program1x = program::import_program_allocation($data);
        $program1->timeallocationstart = $program2->timeallocationstart;
        $program1->timeallocationend = $program2->timeallocationend;
        $this->assertSame((array)$program1, (array)$program1x);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importprogramstart' => 1,
            'importprogramdue' => 1,
            'importprogramend' => 1,
        ];
        $program1x = program::import_program_allocation($data);
        $program1->startdatejson = $program2->startdatejson;
        $program1->duedatejson = $program2->duedatejson;
        $program1->enddatejson = $program2->enddatejson;
        $this->assertSame((array)$program1, (array)$program1x);

        $sources1 = $DB->get_records('enrol_programs_sources', ['programid' => $program1->id]);
        $this->assertCount(0, $sources1);

        $sources2 = $DB->get_records('enrol_programs_sources', ['programid' => $program2->id]);
        $this->assertCount(4, $sources2);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
        ];
        $program1x = program::import_program_allocation($data);
        $sources1 = $DB->get_records('enrol_programs_sources', ['programid' => $program1->id]);
        $this->assertCount(1, $sources1);
        $smanual1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);

        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcemanual' => 1,
            'importsourcecohort' => 1,
            'importsourceapproval' => 1,
            'importsourceselfallocation' => 1,
        ];
        $program1x = program::import_program_allocation($data);
        $sources1 = $DB->get_records('enrol_programs_sources', ['programid' => $program1->id]);
        $this->assertCount(4, $sources1);
        $smanual1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $scohort1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'cohort'], '*', MUST_EXIST);
        $sapproval1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'approval'], '*', MUST_EXIST);
        $sselfallocation1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'selfallocation'], '*', MUST_EXIST);

        $cohorts = $DB->get_records('enrol_programs_src_cohorts', ['sourceid' => $scohort1->id]);
        $this->assertCount(1, $cohorts);
        $cr = reset($cohorts);
        $this->assertSame($cohort2->id, $cr->cohortid);

        $DB->delete_records('enrol_programs_src_cohorts', ['sourceid' => $scohort2->id]);
        $DB->insert_record('enrol_programs_src_cohorts', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort1->id]);
        $DB->insert_record('enrol_programs_src_cohorts', (object)['sourceid' => $scohort2->id, 'cohortid' => $cohort3->id]);
        $data = (object)[
            'id' => $program1->id,
            'fromprogram' => $program2->id,
            'importsourcecohort' => 1,
        ];
        $program1x = program::import_program_allocation($data);
        $cohorts = $DB->get_records('enrol_programs_src_cohorts', ['sourceid' => $scohort1->id]);
        $this->assertCount(3, $cohorts);
    }

    public function test_get_program_startdate_types() {
        $types = program::get_program_startdate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('allocation', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_duedate_types() {
        $types = program::get_program_duedate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_get_program_enddate_types() {
        $types = program::get_program_enddate_types();
        $this->assertIsArray($types);
        $this->assertArrayHasKey('notset', $types);
        $this->assertArrayHasKey('date', $types);
        $this->assertArrayHasKey('delay', $types);
    }

    public function test_update_program_scheduling() {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];

        $oldprogram = program::add_program($data);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'allocation',
            'programdue_type' => 'notset',
            'programend_type' => 'notset',
        ];
        $program = program::update_program_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(util::json_encode(['type' => 'allocation']), $program->startdatejson);
        $this->assertSame(util::json_encode(['type' => 'notset']), $program->duedatejson);
        $this->assertSame(util::json_encode(['type' => 'notset']), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'date',
            'programstart_date' => time() + 60 * 60,
            'programdue_type' => 'date',
            'programdue_date' => time() + 60 * 60 * 3,
            'programend_type' => 'date',
            'programend_date' => time() + 60 * 60 * 6,
        ];
        $program = program::update_program_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(util::json_encode(['type' => 'date', 'date' => $data->programstart_date]), $program->startdatejson);
        $this->assertSame(util::json_encode(['type' => 'date', 'date' => $data->programdue_date]), $program->duedatejson);
        $this->assertSame(util::json_encode(['type' => 'date', 'date' => $data->programend_date]), $program->enddatejson);

        $data = (object)[
            'id' => $oldprogram->id,
            'programstart_type' => 'delay',
            'programstart_delay' => ['type' => 'hours', 'value' => 3],
            'programdue_type' => 'delay',
            'programdue_delay' => ['type' => 'days', 'value' => 6],
            'programend_type' => 'delay',
            'programend_delay' => ['type' => 'months', 'value' => 2],
        ];
        $program = program::update_program_scheduling($data);
        $this->assertInstanceOf('stdClass', $program);
        $this->assertSame(util::json_encode(['type' => 'delay', 'delay' => 'PT3H']), $program->startdatejson);
        $this->assertSame(util::json_encode(['type' => 'delay', 'delay' => 'P6D']), $program->duedatejson);
        $this->assertSame(util::json_encode(['type' => 'delay', 'delay' => 'P2M']), $program->enddatejson);
    }

    public function test_delete_program() {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::add_program($data);

        program::delete_program($program->id);
        $this->assertFalse($DB->record_exists('enrol_programs_programs', ['id' => $program->id]));
    }

    public function test_make_snapshot() {
        global $DB;

        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::add_program($data);
        $this->setAdminUser();
        $admin = get_admin();

        $this->setCurrentTimeStart();
        $DB->delete_records('enrol_programs_prg_snapshots', []);
        program::make_snapshot($program->id, 'test', 'some explanation');

        $records = $DB->get_records('enrol_programs_prg_snapshots', []);
        $this->assertCount(1, $records);

        $record = reset($records);
        $this->assertSame($program->id, $record->programid);
        $this->assertSame('test', $record->reason);
        $this->assertTimeCurrent($record->timesnapshot);
        $this->assertSame($admin->id, $record->snapshotby);
        $this->assertSame('some explanation', $record->explanation);

        program::delete_program($program->id);
        $this->setCurrentTimeStart();
        $DB->delete_records('enrol_programs_prg_snapshots', []);
        program::make_snapshot($program->id, 'delete', 'some explanation');

        $records = $DB->get_records('enrol_programs_prg_snapshots', []);
        $this->assertCount(1, $records);

        $record = reset($records);
        $this->assertSame($program->id, $record->programid);
        $this->assertSame('delete', $record->reason);
        $this->assertTimeCurrent($record->timesnapshot);
        $this->assertSame($admin->id, $record->snapshotby);
        $this->assertSame('some explanation', $record->explanation);
    }

    public function test_load_content() {
        $syscontext = \context_system::instance();
        $data = (object)[
            'fullname' => 'Some program',
            'idnumber' => 'SP1',
            'contextid' => $syscontext->id,
        ];
        $program = program::add_program($data);

        $top = program::load_content($program->id);
        $this->assertInstanceOf(\enrol_programs\local\content\top::class, $top);
    }

    public function test_category_pre_delete() {
        global $DB;

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);
        $category2 = $this->getDataGenerator()->create_category(['parent' => $category1->id]);
        $catcontext2 = \context_coursecat::instance($category2->id);
        $this->assertSame($category1->id, $category2->parent);

        $program1 = $generator->create_program(['contextid' => $catcontext1->id]);
        $program2 = $generator->create_program(['contextid' => $catcontext2->id]);

        $this->assertSame((string)$catcontext1->id, $program1->contextid);
        $this->assertSame((string)$catcontext2->id, $program2->contextid);

        program::pre_course_category_delete($category2->get_db_record());
        $program2 = $DB->get_record('enrol_programs_programs', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$catcontext1->id, $program2->contextid);

        program::pre_course_category_delete($category1->get_db_record());
        $program1 = $DB->get_record('enrol_programs_programs', ['id' => $program1->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program1->contextid);
        $program2 = $DB->get_record('enrol_programs_programs', ['id' => $program2->id], '*', MUST_EXIST);
        $this->assertSame((string)$syscontext->id, $program2->contextid);
    }

    public function test_programs_customfields() {
        global $DB;

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $this->setAdminUser();
        $fieldcategory = $this->getDataGenerator()->create_custom_field_category([
            'component' => 'enrol_programs',
            'area' => 'fields',
            'name' => 'Other custom fields',
        ]);

        $field1 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield1',
            'name' => 'Custom field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
        ]);
        $field2 = $this->getDataGenerator()->create_custom_field([
            'shortname' => 'testfield2',
            'name' => 'Custom field',
            'type' => 'text',
            'categoryid' => $fieldcategory->get('id'),
            'configdata' => ['visibilitymanagers' => true]
        ]);

        $program1 = $generator->create_program(['customfield_testfield1' => 'Test value 1']);
        $program2 = $generator->create_program(['customfield_testfield1' => 'hocus', 'customfield_testfield2' => 'pocus']);

        $this->assertTrue($DB->record_exists('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')]));

        $handler = \enrol_programs\customfield\fields_handler::create();
        $customfieldsdata = $handler->export_instance_data_object($program1->id);
        $this->assertEquals('Test value 1', $customfieldsdata->testfield1);

        $customfieldsdata = $handler->export_instance_data_object($program2->id);
        $this->assertEquals('hocus', $customfieldsdata->testfield1);

        $customfieldsdata = $handler->export_instance_data_object($program2->id);
        $this->assertEquals('pocus', $customfieldsdata->testfield2);

        $program2->customfield_testfield1 = 'hocus-pocus';
        program::update_program_general($program2);

        $customfieldsdata = $handler->export_instance_data_object($program2->id);
        $this->assertEquals('hocus-pocus', $customfieldsdata->testfield1);

        program::delete_program($program1->id);

        $this->assertFalse($DB->record_exists('customfield_data', ['instanceid' => $program1->id, 'fieldid' => $field1->get('id')]));

    }
}
