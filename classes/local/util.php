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
 * Utility class for programs.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class util {

    /**
     * Encode JSON date in a consistent way.
     *
     * @param $data
     * @return string
     */
    public static function json_encode($data): string {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Normalise delays used in allocation settings.
     *
     * NOTE: for now only simple P22M, P22D and PT22H formats are supported,
     *       support for more options may be added later.
     *
     * @param string|null $string
     * @return string|null
     */
    public static function normalise_delay(?string $string): ?string {
        if (trim($string ?? '') === '') {
            return null;
        }

        if (preg_match('/^P\d+M$/D', $string)) {
            if ($string === 'P0M') {
                return null;
            }
            return $string;
        }
        if (preg_match('/^P\d+D$/D', $string)) {
            if ($string === 'P0D') {
                return null;
            }
            return $string;
        }
        if (preg_match('/^PT\d+H$/D', $string)) {
            if ($string === 'PT0H') {
                return null;
            }
            return $string;
        }

        debugging('Unsupported delay format: ' . $string, DEBUG_DEVELOPER);
        return null;
    }

    /**
     * Format delay that was stored in format of PHP DateInterval
     * to human readable form.
     *
     * @param string|null $string
     * @return string
     */
    public static function format_delay(?string $string): string {
        if (!$string) {
            return '';
        }

        $interval = new \DateInterval($string);

        $result = [];
        if ($interval->y) {
            if ($interval->y == 1) {
                $result[] = get_string('numyear', 'core', $interval->y);
            } else {
                $result[] = get_string('numyears', 'core', $interval->y);
            }
        }
        if ($interval->m) {
            if ($interval->m == 1) {
                $result[] = get_string('nummonth', 'core', $interval->m);
            } else {
                $result[] = get_string('nummonths', 'core', $interval->m);
            }
        }
        if ($interval->d) {
            if ($interval->d == 1) {
                $result[] = get_string('numday', 'core', $interval->d);
            } else {
                $result[] = get_string('numdays', 'core', $interval->d);
            }
        }
        if ($interval->h) {
            $result[] = get_string('numhours', 'core', $interval->h);
        }
        if ($interval->i) {
            $result[] = get_string('numminutes', 'core', $interval->i);
        }
        if ($interval->s) {
            $result[] = get_string('numseconds', 'core', $interval->s);
        }

        if ($result) {
            return implode(', ', $result);
        } else {
            return '';
        }
    }

    /**
     * Format duration of interval specified using seconds value.
     *
     * @param int|null $duration seconds
     * @return string
     */
    public static function format_duration(?int $duration): string {
        if ($duration < 0) {
            return get_string('error');
        }
        if (!$duration) {
            return get_string('notset', 'enrol_programs');
        }
        $days = intval($duration / DAYSECS);
        $duration = $duration - $days * DAYSECS;
        $hours = intval($duration / HOURSECS);
        $duration = $duration - $hours * HOURSECS;
        $minutes = intval($duration / MINSECS);
        $seconds = $duration - $minutes * MINSECS;

        $interval = 'P';
        if ($days) {
            $interval .= $days . 'D';
        }
        if ($hours || $minutes || $seconds) {
            $interval .= 'T';
            if ($hours) {
                $interval .= $hours . 'H';
            }
            if ($minutes) {
                $interval .= $minutes . 'M';
            }
            if ($seconds) {
                $interval .= $seconds . 'S';
            }
        }

        return self::format_delay($interval);
    }

    /**
     * Convert SELECT query to format suitable for $DB->count_records_sql().
     *
     * @param string $sql
     * @return string
     */
    public static function convert_to_count_sql(string $sql): string {
        $count = null;
        $sql = preg_replace('/^\s*SELECT.*FROM/Uis', "SELECT COUNT('x') FROM", $sql, 1, $count);
        if ($count !== 1) {
            debugging('Cannot convert SELECT query to count compatible form', DEBUG_DEVELOPER);
        }
        // Subqueries should not have ORDER BYs, so this should be safe,
        // worst case there will be a fatal error caused by cutting the query short.
        $sql = preg_replace('/\s*ORDER BY.*$/is', '', $sql);
        return $sql;
    }

    /**
     * Stores csv file contents as normalised JSON file.
     *
     * NOTE: uploaded file is deleted and instead a new data.json file is stored.
     *
     * @param int $draftid
     * @param array $filedata
     * @return void
     */
    public static function store_uploaded_data(int $draftid, array $filedata): void {
        global $USER;

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $fs->delete_area_files($context->id, 'enrol_programs', 'upload', $draftid);

        $content = json_encode($filedata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $record = [
            'contextid' => $context->id,
            'component' => 'enrol_programs',
            'filearea' => 'upload',
            'itemid' => $draftid,
            'filepath' => '/',
            'filename' => 'data.json',
        ];

        $fs->create_file_from_string($record, $content);
    }

    /**
     * Returns preprocessed data.json user allocation file contents.
     *
     * @param int $draftid
     * @return array|null
     */
    public static function get_uploaded_data(int $draftid): ?array {
        global $USER;

        if (!$draftid) {
            return null;
        }

        $fs = get_file_storage();
        $context = \context_user::instance($USER->id);

        $file = $fs->get_file($context->id, 'enrol_programs', 'upload', $draftid, '/', 'data.json');
        if (!$file) {
            return null;
        }
        $data = json_decode($file->get_content(), true);
        if (!is_array($data)) {
            return null;
        }
        $data = fix_utf8($data);
        return $data;
    }

    /**
     * Deletes old orphaned upload related data.
     *
     * @return void
     */
    public static function cleanup_uploaded_data(): void {
        global $DB;

        $fs = get_file_storage();
        $sql = "SELECT contextid, itemid
                  FROM {files}
                 WHERE component = 'enrol_programs' AND filearea = 'upload' AND filepath = '/' AND filename = '.'
                       AND timecreated < :old";
        $rs = $DB->get_recordset_sql($sql, ['old' => time() - 60*60*24*2]);
        foreach ($rs as $dir) {
            $fs->delete_area_files($dir->contextid, 'enrol_programs', 'upload', $dir->itemid);
        }
        $rs->close();
    }
}
