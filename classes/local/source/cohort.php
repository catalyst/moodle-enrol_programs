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

namespace enrol_programs\local\source;

use stdClass;

/**
 * Program allocation for all visible cohort members.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class cohort extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'cohort';
    }

    /**
     * Can settings of this source be imported to other program?
     *
    /**
     * Can settings of this source be imported to other program?
     *
     * @param stdClass $fromprogram
     * @param stdClass $targetprogram
     * @return bool
     */
    public static function is_import_allowed(stdClass $fromprogram, stdClass $targetprogram): bool {
        global $DB;

        if (!$DB->record_exists('enrol_programs_sources', ['type' => static::get_type(), 'programid' => $fromprogram->id])) {
            return false;
        }

        if (!$DB->record_exists('enrol_programs_sources', ['type' => static::get_type(), 'programid' => $targetprogram->id])) {
            if (!static::is_new_allowed($targetprogram)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Import source data from one program to another.
     *
     * @param int $fromprogramid
     * @param int $targetprogramid
     * @return stdClass created or updated source record
     */
    public static function import_source_data(int $fromprogramid, int $targetprogramid): stdClass {
        global $DB;

        $targetsource = parent::import_source_data($fromprogramid, $targetprogramid);

        $sql = "SELECT fc.*
                  FROM {enrol_programs_src_cohorts} fc
                  JOIN {enrol_programs_sources} fs ON fs.id = fc.sourceid AND fs.programid = :fromprogramid AND fs.type = 'cohort'
             LEFT JOIN {enrol_programs_src_cohorts} tc ON tc.cohortid = fc.cohortid AND tc.sourceid = :targetsourceid
                 WHERE tc.id IS NULL
              ORDER BY fc.id ASC";
        $params = ['fromprogramid' => $fromprogramid, 'targetsourceid' => $targetsource->id];
        $records = $DB->get_records_sql($sql, $params);
        foreach ($records as $record) {
            unset($record->id);
            $record->sourceid = $targetsource->id;
            $DB->insert_record('enrol_programs_src_cohorts', $record);
        }

        return $targetsource;
    }

    /**
     * Render details about this enabled source in a program management ui.
     *
     * @param stdClass $program
     * @param stdClass|null $source
     * @return string
     */
    public static function render_status_details(stdClass $program, ?stdClass $source): string {
        $result = parent::render_status_details($program, $source);

        if ($source) {
            $cohorts = cohort::fetch_allocation_cohorts_menu($source->id);
            \core_collator::asort($cohorts);
            if ($cohorts) {
                $cohorts = array_map('format_string', $cohorts);
                $result .= ' (' . implode(', ', $cohorts) .')';
            }
        }

        return $result;
    }

    /**
     * Is it possible to manually edit user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function allocation_edit_supported(stdClass $program, stdClass $source, stdClass $allocation): bool {
        return true;
    }

    /**
     * Is it possible to manually delete user allocation?
     *
     * @param stdClass $program
     * @param stdClass $source
     * @param stdClass $allocation
     * @return bool
     */
    public static function allocation_delete_supported(stdClass $program, stdClass $source, stdClass $allocation): bool {
        if ($allocation->archived) {
            return true;
        }
        return false;
    }

    /**
     * Callback method for source updates.
     *
     * @param stdClass|null $oldsource
     * @param stdClass $data
     * @param stdClass|null $source
     * @return void
     */
    public static function after_update(?stdClass $oldsource, stdClass $data, ?stdClass $source): void {
        global $DB;

        if (!$source) {
            // Just deleted or not enabled at all.
            return;
        }

        $oldcohorts = cohort::fetch_allocation_cohorts_menu($source->id);
        $sourceid = $DB->get_field('enrol_programs_sources', 'id', ['programid' => $data->programid, 'type' => 'cohort']);
        $data->cohorts = $data->cohorts ?? [];
        foreach ($data->cohorts as $cid) {
            if (isset($oldcohorts[$cid])) {
                unset($oldcohorts[$cid]);
                continue;
            }
            $record = (object)['sourceid' => $sourceid, 'cohortid' => $cid];
            $DB->insert_record('enrol_programs_src_cohorts', $record);
        }
        foreach ($oldcohorts as $cid => $unused) {
            $DB->delete_records('enrol_programs_src_cohorts', ['sourceid' => $sourceid, 'cohortid' => $cid]);
        }
    }

    /**
     * Fetch cohorts that allow program allocation automatically.
     *
     * @param int $sourceid
     * @return array
     */
    public static function fetch_allocation_cohorts_menu(int $sourceid): array {
        global $DB;

        $sql = "SELECT c.id, c.name
                  FROM {cohort} c
                  JOIN {enrol_programs_src_cohorts} pc ON c.id = pc.cohortid                                    
                 WHERE pc.sourceid = :sourceid
              ORDER BY c.name ASC, c.id ASC";
        $params = ['sourceid' => $sourceid];

        return $DB->get_records_sql_menu($sql, $params);
    }

    /**
     * Make sure users are allocated properly.
     *
     * This is expected to be called from cron and when
     * program allocation settings are updated.
     *
     * @param int|null $programid
     * @param int|null $userid
     * @return bool true if anything updated
     */
    public static function fix_allocations(?int $programid, ?int $userid): bool {
        global $DB, $USER;

        $updated = false;

        // Allocate all missing users and revert archived allocations.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = 'AND p.id = :programid';
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND cm.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT DISTINCT p.id, cm.userid, s.id AS sourceid, pa.id AS allocationid
                  FROM {cohort_members} cm
                  JOIN {enrol_programs_src_cohorts} psc ON psc.cohortid = cm.cohortid
                  JOIN {enrol_programs_sources} s ON s.id = psc.sourceid
                  JOIN {enrol_programs_programs} p ON p.id = s.programid
             LEFT JOIN {enrol_programs_allocations} pa ON pa.programid = p.id AND pa.userid = cm.userid
                 WHERE (pa.id IS NULL OR (pa.archived = 1 AND pa.sourceid = s.id))
                       AND p.archived = 0
                       AND (p.timeallocationstart IS NULL OR p.timeallocationstart <= :now1)
                       AND (p.timeallocationend IS NULL OR p.timeallocationend > :now2)
                       $programselect $userselect
              ORDER BY p.id ASC, s.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $record) {
            if ($record->allocationid) {
                $DB->set_field('enrol_programs_allocations', 'archived', 0, ['id' => $record->allocationid]);
                $updated = true;
            } else {
                if (PHPUNIT_TEST) {
                    self::task_allocate_user($record->id, $record->sourceid, $record->userid);
                } else {
                    $task = new \enrol_programs\task\allocate_user_task();
                    $task->set_userid($USER->id);
                    $task->set_custom_data([
                        'programid'    => $record->id,
                        'userid'       => $record->userid,
                        'sourceid'     => $record->sourceid,
                        'sourceclass'  => '\\' . self::class,
                    ]);

                    \core\task\manager::queue_adhoc_task($task, true);
                }
            }
        }
        $rs->close();

        // Archive allocations if user not member.
        $params = [];
        $programselect = '';
        if ($programid) {
            $programselect = 'AND p.id = :programid';
            $params['programid'] = $programid;
        }
        $userselect = '';
        if ($userid) {
            $userselect = "AND pa.userid = :userid";
            $params['userid'] = $userid;
        }
        $now = time();
        $params['now1'] = $now;
        $params['now2'] = $now;
        $sql = "SELECT pa.id
                  FROM {enrol_programs_allocations} pa
                  JOIN {enrol_programs_sources} s ON s.programid = pa.programid AND s.type = 'cohort' AND s.id = pa.sourceid
                  JOIN {enrol_programs_programs} p ON p.id = pa.programid
                 WHERE p.archived = 0 AND pa.archived = 0
                       AND NOT EXISTS (
                            SELECT 1
                              FROM {cohort_members} cm
                              JOIN {enrol_programs_src_cohorts} psc ON psc.cohortid = cm.cohortid
                             WHERE cm.userid = pa.userid AND psc.sourceid = s.id
                       )
                       AND (p.timeallocationstart IS NULL OR p.timeallocationstart <= :now1)
                       AND (p.timeallocationend IS NULL OR p.timeallocationend > :now2)
                       $programselect $userselect
              ORDER BY pa.id ASC";
        $rs = $DB->get_recordset_sql($sql, $params);
        foreach ($rs as $pa) {
            // NOTE: it is expected that enrolment fixing is executed right after this method.
            $DB->set_field('enrol_programs_allocations', 'archived', 1, ['id' => $pa->id]);
            $updated = true;
        }
        $rs->close();

        return $updated;
    }

    /**
     * Allocate user to program.
     *
     * @param int $programid
     * @param int $sourceid
     * @param int $userid
     * @return stdClass user allocation record
     */
    public static function task_allocate_user(int $programid, int $sourceid, int $userid): \stdClass {
        global $DB;

        $params = ['programid' => $programid, 'sourceid' => $sourceid, 'userid' => $userid];
        if ($allocation = $DB->get_record('enrol_programs_allocations', $params)) {
            // Allocation already exists, no need to allocate, just return the allocation.
            return $allocation;
        }

        $program = \enrol_programs\local\program::get_instance($programid);
        $source = self::get_instance($sourceid);

        return self::allocate_user($program, $source, $userid, []);
    }
}
