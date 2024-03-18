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
 * @covers \enrol_programs\local\util
 */
final class util_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_json_encode() {
        $this->assertSame('{"abc":"\\\\šk\"\'"}', util::json_encode(['abc' => '\šk"\'']));
    }

    public function test_normalise_delay() {
        $this->assertSame('P1M', util::normalise_delay('P1M'));
        $this->assertSame('P99D', util::normalise_delay('P99D'));
        $this->assertSame('PT9H', util::normalise_delay('PT9H'));
        $this->assertDebuggingNotCalled();
        $this->assertSame(null, util::normalise_delay(''));
        $this->assertSame(null, util::normalise_delay(null));
        $this->assertSame(null, util::normalise_delay('P0M'));
        $this->assertDebuggingNotCalled();

        $this->assertSame(null, util::normalise_delay('P9X'));
        $this->assertDebuggingCalled();
        $this->assertSame(null, util::normalise_delay('P1M1D'));
        $this->assertDebuggingCalled();
    }

    public function test_format_delay() {
        $this->assertSame('2 months', util::format_delay('P2M'));
        $this->assertSame('2 days', util::format_delay('P2D'));
        $this->assertSame('2 hours', util::format_delay('PT2H'));
        $this->assertSame('1 month, 2 days, 3 hours', util::format_delay('P1M2DT3H'));
        $this->assertSame('', util::format_delay(''));
        $this->assertSame('', util::format_delay(null));
    }

    public function test_format_duration() {
        $this->assertSame('2 days', util::format_duration(DAYSECS * 2));
        $this->assertSame('38 days, 4 hours, 35 seconds', util::format_duration(DAYSECS * 3 + HOURSECS * 4 + WEEKSECS * 5 + 35));
        $this->assertSame('Not set', util::format_duration(null));
        $this->assertSame('Not set', util::format_duration(0));
        $this->assertSame('Error', util::format_duration(DAYSECS * -1));
    }

    public function test_convert_to_count_sql() {
        $sql = 'SELECT *
                  FROM {user}
              ORDER BY id';
        $expected = 'SELECT COUNT(\'x\') FROM {user}';
        $this->assertSame($expected, util::convert_to_count_sql($sql));
    }

    public function test_store_uploaded_data() {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'somefile.csv',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);

        $files = $fs->get_area_files($context->id, 'enrol_programs', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);
        $file = reset($files);
        $this->assertSame('/', $file->get_filepath());
        $this->assertSame('data.json', $file->get_filename());
        $this->assertEquals($csvdata, json_decode($file->get_content()));
    }

    public function test_get_uploaded_data() {
        global $CFG;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = file_get_unused_draft_itemid();

        $this->assertNull(util::get_uploaded_data($draftid));
        $this->assertNull(util::get_uploaded_data(-1));
        $this->assertNull(util::get_uploaded_data(0));

        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $record = [
            'contextid' => $context->id,
            'component' => 'user',
            'filearea' => 'draft',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'somefile.csv',
        ];
        $fs->create_file_from_string($record, 'content is irrelevant');

        $this->assertNull(util::get_uploaded_data($draftid));

        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);

        $this->assertEquals($csvdata, util::get_uploaded_data($draftid));
    }

    public function test_cleanup_uploaded_data() {
        global $CFG, $DB;
        require_once("$CFG->libdir/filelib.php");

        $admin = get_admin();
        $this->setUser($admin);
        $draftid = file_get_unused_draft_itemid();
        $fs = get_file_storage();
        $context = \context_user::instance($admin->id);
        $csvdata = [
            ['username', 'firstname', 'lastname'],
            ['user1', 'First', 'User'],
            ['user2', 'Second', 'User'],
        ];
        util::store_uploaded_data($draftid, $csvdata);
        $files = $fs->get_area_files($context->id, 'enrol_programs', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'enrol_programs', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        $old = time() - 60*60*24*1;
        $DB->set_field('files', 'timecreated', $old, ['component' => 'enrol_programs']);
        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'enrol_programs', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(1, $files);

        $old = time() - 60*60*24*2 - 10;
        $DB->set_field('files', 'timecreated', $old, ['component' => 'enrol_programs']);
        util::cleanup_uploaded_data();
        $files = $fs->get_area_files($context->id, 'enrol_programs', 'upload', $draftid, 'id ASC', false);
        $this->assertCount(0, $files);
    }
}
