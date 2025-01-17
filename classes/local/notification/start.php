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

use stdClass;

/**
 * Program start notification.
 *
 * @package    enrol_programs
 * @copyright  2023 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class start extends base {
    /**
     * Send notifications.
     *
     * @param stdClass|null $program
     * @param stdClass|null $user
     * @return void
     */
    public static function notify_users(?stdClass $program, ?stdClass $user): void {
        global $DB;

        $source = null;
        $loadfunction = function(stdClass $allocation) use (&$program, &$source, &$user): void {
            global $DB;
            if (!$source || $source->id != $allocation->sourceid) {
                $source = $DB->get_record('enrol_programs_sources', ['id' => $allocation->sourceid], '*', MUST_EXIST);
            }
            if (!$user || $user->id != $allocation->userid) {
                $user = $DB->get_record('user', ['id' => $allocation->userid], '*', MUST_EXIST);
            }
            if (!$program || $program->id != $source->programid) {
                $program = $DB->get_record('enrol_programs_programs', ['id' => $allocation->programid], '*', MUST_EXIST);
            }
        };

        $params = [];
        $programselect = '';
        if ($program) {
            $programselect = "AND p.id = :programid";
            $params['programid'] = $program->id;
        }
        $userselect = '';
        if ($user) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $user->id;
        }
        $params['now'] = time();
        $params['cutoff'] = $params['now'] - self::TIME_CUTOFF;

        $sql = "SELECT pa.*
                  FROM {enrol_programs_allocations} pa
                  JOIN {user} u ON u.id = pa.userid AND u.deleted = 0 AND u.suspended = 0
                  JOIN {enrol_programs_sources} s ON s.id = pa.sourceid
                  JOIN {enrol_programs_programs} p ON p.id = pa.programid
                  JOIN {local_openlms_notifications} n
                       ON n.component = 'enrol_programs' AND n.notificationtype = 'start' AND n.instanceid = p.id AND n.enabled = 1
             LEFT JOIN {local_openlms_user_notified} un
                       ON un.notificationid = n.id AND un.userid = pa.userid AND un.otherid1 = pa.id
                 WHERE un.id IS NULL AND p.archived = 0 AND pa.archived = 0
                       $programselect $userselect
                       AND pa.timestart <= :now AND pa.timestart > :cutoff
              ORDER BY p.id, s.id, pa.userid";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $allocation) {
            $loadfunction($allocation);
            self::notify_allocated_user($program, $source, $allocation, $user);
        }
        $rs->close();
    }

    /**
     * Send notification related to start.
     *
     * @param stdClass $user
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     */
    public static function notify_now(stdClass $user, stdClass $program, stdClass $source, stdClass $allocation) {
        self::notify_allocated_user($program, $source, $allocation, $user);
    }
}
