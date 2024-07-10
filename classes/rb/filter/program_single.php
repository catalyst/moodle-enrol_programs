<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace enrol_programs\rb\filter;

use local_reportbuilder\filterstateprovider\filterstateprovider;
use stdClass;

/**
 * Allows for filtering of a single programs in a report
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       https://www.openlms.net/
 */
final class program_single extends \local_reportbuilder\rb\filter\base {
    public function __construct($type, $value, $advanced, $region, $report) {
        parent::__construct($type, $value, $advanced, $region, $report);

        // We need to check the user has permission to view the programs in the saved
        // search as these may be a search created by someone else who can view
        // a different selection of programs.

        $filterstateprovider = filterstateprovider::fetch($this->report->get_uniqueid());
        $defaults = $filterstateprovider->get_filter($this->name);
        if (isset($defaults)) {
            if (isset($defaults['value'])) {
                $defaults['value'] = $programid = $defaults['value'];
            } else {
                $defaults['value'] = '';
            }
            $filterstateprovider->set_filter($this->name, $defaults);
        }

    }

    /**
     * Adds controls specific to this filter in the form.
     *
     * @param object $mform a MoodleForm object to setup
     */
    public function setupForm($mform) {
        global $PAGE, $DB;

        $label = format_string($this->label);
        $advanced = $this->advanced;

        $choices = [0 => ''];
        $programs = $DB->get_records('enrol_programs_programs');
        foreach ($programs as $program) {
            $choices[$program->id] = $program->fullname;
        }

        $mform->addElement('autocomplete', $this->name, $label, $choices, ['multiple' => false]);
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, $defaults['value']);
        }
    }

    /**
     * Retrieves data from the form data.
     *
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field = $this->name;

        if (isset($formdata->$field) && $formdata->$field != '') {
            return array('value' => $formdata->$field);
        }

        return false;
    }

    /**
     * Returns the condition to be used with SQL where.
     *
     * @param array $data filter settings
     * @return array containing filtering condition SQL clause and params
     */
    public function get_sql_filter($data) {
        $programid = $data['value'];
        $query = $this->get_field();

        // None selected - match everything.
        if (empty($programid)) {
            // Using 1=1 instead of TRUE for MSSQL support.
            return array(' 1=1 ', []);
        }

        $uniqueparam = \local_reportbuilder\dblib\base::getbdlib()->get_unique_param('programid');

        $sql = " (" . $query . " = :$uniqueparam ) ";

        return array($sql, [$uniqueparam => $programid]);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     *
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        global $DB;

        if (empty($value)) {
            return '';
        }

        $a = new stdClass();
        $a->label = $this->label;

        $program = $DB->get_record('enrol_programs_programs', ['id' => $value], '*', MUST_EXIST);
        $a->value = format_string($program->fullname);

        return get_string('selectlabelnoop', 'local_reportbuilder', $a);
    }
}
