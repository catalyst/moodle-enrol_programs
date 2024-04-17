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

namespace enrol_programs\local\content;

use enrol_programs\local\util;
use stdClass;

/**
 * Program training item.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class training extends item {
    /** @var int */
    protected $frameworkid;

    /** @var ?item Previous item is not actually used because there is no concept of start of training */
    protected $previous;

    public function get_frameworkid(): int {
        return $this->frameworkid;
    }

    /**
     * What is the required total learning?
     *
     * @return int|null
     */
    public function get_required_training(): ?int {
        global $DB;
        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $this->frameworkid]);
        if (!$framework) {
            return null;
        }
        return $framework->requiredtraining;
    }

    /**
     * What is the current sum of completed training?
     * @param stdClass $allocation
     * @return int
     */
    public function get_completed_training(stdClass $allocation): int {
        global $DB;
        $sql = "SELECT SUM(cd.intvalue) AS completed
                  FROM {customfield_training_completions} ctc
                  JOIN {customfield_field} cf ON cf.id = ctc.fieldid
                  JOIN {customfield_data} cd ON cd.fieldid = cf.id AND cd.instanceid = ctc.instanceid
                  JOIN {customfield_training_fields} tf ON tf.fieldid = cf.id
                  JOIN {customfield_training_frameworks} tfr ON tfr.id = tf.frameworkid
                 WHERE tfr.id = :frameworkid AND ctc.userid = :userid AND cd.intvalue IS NOT NULL
                       AND (tfr.restrictedcompletion = 0 OR ctc.timecompleted >= :timestart)";
        $params = [
            'frameworkid' => $this->frameworkid,
            'userid' => $allocation->userid,
            'timestart' => $allocation->timestart,
        ];
        return (int)$DB->get_field_sql($sql, $params);
    }

    /**
     * Current progress for current active program allocations,
     * required training in all other cases.
     *
     * @param stdClass $allocation
     * @return string
     */
    public function get_training_progress(stdClass $allocation): string {
        global $DB;

        $now = time();

        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $this->frameworkid]);
        if (!$framework) {
            return get_string('error');
        }

        if ($framework->archived || $allocation->archived
            || $allocation->timestart > $now || ($allocation->timeend && $allocation->timeend <= $now)) {

            return get_string('trainingcompletion', 'enrol_programs', $framework->requiredtraining);
        }

        $data = [
            'current' => self::get_completed_training($allocation),
            'total' => $framework->requiredtraining,
        ];

        return get_string('trainingprogress', 'enrol_programs', $data);
    }

    /**
     * Is this item deletable?
     *
     * @return bool
     */
    public function is_deletable(): bool {
        if (!$this->id) {
            return false;
        }
        return true;
    }

    /**
     * Return item that must be completed before allowing access to this training.
     *
     * @return item|null
     */
    public function get_previous(): ?item {
        return $this->previous;
    }

    /**
     * Set previous item to new value.
     *
     * @param item|null $previous new previous item
     * @return void
     */
    protected function fix_previous(?item $previous): void {
        $this->previous = $previous;
    }

    /**
     * Factory method.
     *
     * @param \stdClass $record
     * @param item|null $previous
     * @param array $unusedrecords
     * @param array $prerequisites
     * @return training
     */
    protected static function init_from_record(\stdClass $record, ?item $previous, array &$unusedrecords, array &$prerequisites): item {
        if ($record->topitem || $record->courseid !== null || $record->frameworkid === null) {
            throw new \coding_exception('Invalid training item');
        }
        $item = new training();
        $item->id = $record->id;
        $item->programid = $record->programid;
        $item->frameworkid = $record->frameworkid;
        $item->previous = $previous;
        if ($previous) {
            if ($previous->id == $record->id) {
                $item->previous = null;
                $item->problemdetected = true;
            } else if ($record->previtemid != $previous->id) {
                $item->problemdetected = true;
            }
        } else {
            if ($record->previtemid) {
                $item->problemdetected = true;
            }
        }
        $item->fullname = $record->fullname;
        $item->points = $record->points;
        $item->completiondelay = $record->completiondelay;

        if ($record->minprerequisites !== null) {
            $item->problemdetected = true;
        }
        if ($record->minpoints !== null) {
            $item->problemdetected = true;
        }

        // NOTE: Prerequisites are verified in set that contains this training.

        return $item;
    }

    /**
     * Fix item prerequisites if necessary.
     *
     * @param array $prerequisites
     * @return bool true if fix applied
     */
    protected function fix_prerequisites(array &$prerequisites): bool {
        // Nothing to do, parent is defining the prerequisites.
        return false;
    }

    /**
     * Returns expected item record data.
     *
     * @return array
     */
    protected function get_record(): array {
        global $DB;

        $fullname = $DB->get_field('customfield_training_frameworks', 'name', ['id' => $this->frameworkid]);
        if ($fullname === false) {
            $fullname = $this->fullname;
        }

        return [
            'id' => (empty($this->id) ? null : (string)$this->id),
            'programid' => (string)$this->programid,
            'topitem' => null,
            'courseid' => null,
            'frameworkid' => (string)$this->frameworkid,
            'previtemid' => (isset($this->previous) ? (string)$this->previous->id : null),
            'fullname' => $fullname,
            'sequencejson' => util::json_encode([]),
            'minprerequisites' => null,
            'points' => (string)$this->points,
            'minpoints' => null,
            'completiondelay' => (string)$this->completiondelay,
        ];
    }
}

