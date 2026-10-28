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

namespace enrol_programs\local\notification;

use enrol_programs\local\source\manual;
use enrol_programs\local\notification_manager;

/**
 * Program disabled notifications test.
 *
 * @package    enrol_programs
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2026 Catalyst IT Australia Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\local\notification\base
 */
final class disabled_notification_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
        parent::setUp();
    }

    public function test_disabled_notifications(): void {
        global $DB;

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $user = $this->getDataGenerator()->create_user();
        $program = $generator->create_program(['sources' => ['manual' => []]]);
        $params = ['programid' => $program->id, 'type' => 'manual'];
        $source = $DB->get_record('enrol_programs_sources', $params, '*', MUST_EXIST);

        $notifications = [];
        $types = notification_manager::get_all_types();
        foreach ($types as $type => $class) {
            $notifications[$type] = $generator->create_program_notification([
                'notificationtype' => $type,
                'programid' => $program->id,
            ]);
            $DB->set_field('local_openlms_notifications', 'enabled', 0, ['id' => $notifications[$type]->id]);
        }

        // Test allocation notification.
        $this->test_notification_type(
            [manual::class, 'allocate_users'],
            [$program->id, $source->id, [$user->id]],
            $notifications['allocation']->id,
            $user->id
        );

        $params = ['programid' => $program->id, 'userid' => $user->id];
        $allocation = $DB->get_record('enrol_programs_allocations', $params, '*', MUST_EXIST);

        // Test start notification.
        $DB->set_field('enrol_programs_allocations', 'timestart', time(), ['id' => $allocation->id]);
        $this->test_notification_type(
            [start::class, 'notify_users'],
            [$program, null],
            $notifications['start']->id,
            $user->id
        );

        // Test due soon notification.
        $timedue = time() + base::TIME_SOON - 100;
        $DB->set_field('enrol_programs_allocations', 'timedue', $timedue, ['id' => $allocation->id]);
        $this->test_notification_type(
            [duesoon::class, 'notify_users'],
            [$program, null],
            $notifications['duesoon']->id,
            $user->id
        );

        // Test due notification.
        $timedue = time() - base::TIME_CUTOFF + 100;
        $DB->set_field('enrol_programs_allocations', 'timedue', $timedue, ['id' => $allocation->id]);
        $this->test_notification_type(
            [due::class, 'notify_users'],
            [$program, null],
            $notifications['due']->id,
            $user->id
        );

        // Test completion notification.
        $DB->set_field('enrol_programs_allocations', 'timecompleted', time(), ['id' => $allocation->id]);
        $this->test_notification_type(
            [completion::class, 'notify_users'],
            [$program, null],
            $notifications['completion']->id,
            $user->id
        );

        // Test end completed notification.
        $timeend = time() - base::TIME_CUTOFF + 100;
        $DB->set_field('enrol_programs_allocations', 'timeend', $timeend, ['id' => $allocation->id]);
        $this->test_notification_type(
            [endcompleted::class, 'notify_users'],
            [$program, null],
            $notifications['endcompleted']->id,
            $user->id
        );

        // Test end failed notification.
        $DB->set_field('enrol_programs_allocations', 'timecompleted', null, ['id' => $allocation->id]);
        $this->test_notification_type(
            [endfailed::class, 'notify_users'],
            [$program, null],
            $notifications['endfailed']->id,
            $user->id
        );
    }

    /**
     * Asserts that the notified record is created and no message was sent.
     *
     * @param callable $function the function to call to trigger the notification
     * @param array $args the args to pass to the function
     * @param int $notificationid
     * @param int $userid
     * @return void
     */
    private function test_notification_type(
        callable $function,
        array $args,
        int $notificationid,
        int $userid
    ): void {
        global $DB;

        $sink = $this->redirectMessages();
        $function(...$args);
        $messages = $sink->get_messages();
        $sink->close();
        $this->assertCount(0, $messages);
        $this->assertTrue(
            $DB->record_exists('local_openlms_user_notified', [
                'notificationid' => $notificationid,
                'userid' => $userid,
            ])
        );
    }
}
