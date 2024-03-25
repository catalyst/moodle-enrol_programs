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

namespace enrol_programs\callback;

/**
 * Hook callbacks from customfield_training related code.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class customfield_training {
    /**
     * Callback to discover training framework usage.
     */
    public static function framework_usage(\customfield_training\hook\framework_usage $hook): void {
        global $DB;

        $count = $DB->count_records('enrol_programs_items', ['frameworkid' => $hook->get_frameworkid()]);
        if ($count) {
            $hook->add_usage($count);
        }
    }

    /**
     * Callback to announce new completions relevant to framework and user.
     */
    public static function completion_updated(\customfield_training\hook\completion_updated $hook): void {
        global $DB;

        list($fselect, $params) = $DB->get_in_or_equal($hook->get_frameworkids(), SQL_PARAMS_NAMED);
        $fselect = "pi.frameworkid $fselect";
        $params['userid'] = $hook->get_userid();

        $sql = "SELECT DISTINCT pi.programid
                  FROM {enrol_programs_items} pi
                  JOIN {enrol_programs_allocations} pa ON pa.programid = pi.programid
                  JOIN {enrol_programs_programs} p ON p.id = pa.programid
                 WHERE pa.userid = :userid AND $fselect
                       AND p.archived = 0 AND pa.archived = 0
              ORDER BY pi.programid";
        $programids = $DB->get_fieldset_sql($sql, $params);

        if (!$programids) {
            return;
        }
        if (count($programids) > 1) {
            \enrol_programs\local\allocation::fix_user_enrolments(null, $hook->get_userid());
        } else {
            $programid = reset($programids);
            \enrol_programs\local\allocation::fix_user_enrolments($programid, $hook->get_userid());
        }
    }
}
