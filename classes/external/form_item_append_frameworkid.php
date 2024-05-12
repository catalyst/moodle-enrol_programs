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

namespace enrol_programs\external;

use core_external\external_function_parameters;
use core_external\external_value;

/**
 * Provides list of candidates for adding frameworks to program.
 *
 * @package     enrol_programs
 * @copyright   2024 Open LMS (https://www.openlms.net/)
 * @author      Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_item_append_frameworkid extends \local_openlms\external\form_autocomplete_field {
    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(\PARAM_RAW, 'The search query', \VALUE_REQUIRED),
            'programid' => new external_value(\PARAM_INT, 'Program id', \VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request.
     * @param int $programid The framework.
     * @return array
     */
    public static function execute(string $query, int $programid): array {
        global $DB;

        $params = self::validate_parameters(self::execute_parameters(),
            ['query' => $query, 'programid' => $programid]);
        $query = $params['query'];
        $programid = $params['programid'];

        $program = $DB->get_record('enrol_programs_programs', ['id' => $programid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('enrol/programs:edit', $context);

        $tenantselect = '';
        if (\enrol_programs\local\tenant::is_available()) {
            $targetprogramtenantid = $DB->get_field('context', 'tenantid', ['id' => $context->id]);
            if ($targetprogramtenantid) {
                $tenantselect = "AND (c.tenantid = :tenantid OR c.tenantid IS NULL)";
                $params['tenantid'] = $targetprogramtenantid;
            }
        }

        $sql = "SELECT f.id, f.name, f.idnumber, f.archived, f.contextid, f.public
                  FROM {customfield_training_frameworks} f
                  JOIN {context} c ON c.id = f.contextid
                 WHERE f.archived = 0
                       $tenantselect
              ORDER BY f.name ASC";
        $frameworks = $DB->get_records_sql($sql, $params);

        $list = [];
        foreach ($frameworks as $framework) {
            if ($query) {
                if (!str_contains($framework->name, $query) && !str_contains($framework->idnumber ?? '', $query)) {
                    continue;
                }
            }
            if (!$framework->public) {
                $context = \context::instance_by_id($framework->contextid);
                if (!has_capability('customfield/training:viewframeworks', $context)) {
                    continue;
                }
            }
            $list[] = [
                'value' => $framework->id,
                'label' => format_string($framework->name),
            ];
        }

        return [
            'notice' => null,
            'list' => $list,
        ];
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            global $DB;

            $framework = $DB->get_record('customfield_training_frameworks', ['id' => $value]);
            if (!$framework) {
                return get_string('error');
            }
            return format_string($framework->name);
        };
    }

    /**
     * @param array $arguments
     * @param $value
     * @return string|null error message, NULL means value is ok
     */
    public static function validate_form_value(array $arguments, $value): ?string {
        global $DB;

        if (!$value) {
            return null;
        }

        $framework = $DB->get_record('customfield_training_frameworks', ['id' => $value]);
        if (!$framework || $framework->archived) {
            return get_string('error');
        }

        if ($framework->public) {
            return null;
        }

        $context = \context::instance_by_id($framework->contextid);
        if (has_capability('customfield/training:viewframeworks', $context)) {
            return null;
        } else {
            return get_string('error');
        }
    }
}
