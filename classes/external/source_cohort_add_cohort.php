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
use core_external\external_api;
use enrol_programs\local\source\cohort;
use core_external\external_multiple_structure;

/**
 * Adds a cohort to the list of synchronised cohorts in a program.
 *
 * @package     enrol_programs
 * @copyright   2023 Open LMS (https://www.openlms.net/)
 * @author      Farhan Karmali
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_cohort_add_cohort extends external_api {

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'programid' => new external_value(PARAM_INT, 'Program id'),
            'cohortid' => new external_value(PARAM_INT, 'Cohort id')
        ]);
    }

    /**
     * Adds the cohort to the list of cohorts that are synced with the program.
     *
     * @param int $programid Program id for which the cohorts have to be fetched.
     * @param int $cohortid Cohort id that has to be added to the program.
     * @return array
     */
    public static function execute(int $programid, int $cohortid): array {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['programid' => $programid, 'cohortid' => $cohortid]);
        $programid = $params['programid'];
        $cohortid = $params['cohortid'];

        $program = $DB->get_record('enrol_programs_programs', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record('enrol_programs_sources', ['programid' => $program->id, 'type' => 'cohort'], '*', MUST_EXIST);
        $cohort = $DB->get_record('cohort', ['id' => $cohortid], '*', MUST_EXIST);

        // Validate context.
        $context = \context::instance_by_id($program->contextid);
        self::validate_context($context);
        require_capability('enrol/programs:edit', $context);

        $cohortcontext = \context::instance_by_id($cohort->contextid);
        require_capability('moodle/cohort:view', $cohortcontext);

        if (\enrol_programs\local\tenant::is_active()) {
            $programtenantid = $DB->get_field('context', 'tenantid', ['id' => $program->contextid]);
            if ($programtenantid) {
                $cohorttenantid = $DB->get_field('context', 'tenantid', ['id' => $cohort->contextid]);
                if ($cohorttenantid && $cohorttenantid != $programtenantid) {
                    throw new \invalid_parameter_exception('Tenant mismatch');
                }
            }
        }

        $oldcohorts = cohort::fetch_allocation_cohorts_menu($source->id);
        if (!isset($oldcohorts[$cohort->id])) {
            $oldcohorts[$cohort->id] = $cohort->name;
            $data = (object)[
                'id' => $source->id,
                'type' => $source->type,
                'programid' => $source->programid,
                'enable' => 1,
                'cohorts' => array_keys($oldcohorts)
            ];
            cohort::update_source($data);
        }

        return source_cohort_get_cohorts::get_cohorts($program->id);
    }

    /**
     * Describes the external function parameters.
     *
     * @return external_multiple_structure
     */
    public static function execute_returns(): external_multiple_structure {
        return source_cohort_get_cohorts::get_cohorts_returns();
    }
}
