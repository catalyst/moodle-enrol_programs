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

use html_writer;
use local_reportbuilder\filterstateprovider\filterstateprovider;
use stdClass;

/**
 * Allows for filtering of multiple programs in a report
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       https://www.openlms.net/
 */
final class program_multi extends \local_reportbuilder\rb\filter\base {

    // Constants relating to comparison operators for this filter.
    const PROGRAM_MULTI_ANYVALUE = 0;
    const PROGRAM_MULTI_EQUALTO = 1;
    const PROGRAM_MULTI_NOTEQUALTO = 2;

    public function __construct($type, $value, $advanced, $region, $report) {
        parent::__construct($type, $value, $advanced, $region, $report);

        // We need to check the user has permission to view the programs in the saved
        // search as these may be a search created by someone else who can view
        // a different selection of programs.

        $filterstateprovider = filterstateprovider::fetch($this->report->get_uniqueid());
        $defaults = $filterstateprovider->get_filter($this->name);
        if (isset($defaults)) {
            if (isset($defaults['value'])) {
                $programids = array_filter(explode(',', $defaults['value']));

                $defaults['value'] = implode(',', $programids);

                // Even if operator is set, if there are no more course ids after checking what the user
                // can view, we set the operator to 'Is any value'.
                if (!isset($defaults['operator']) || empty($programids)) {
                    $defaults['operator'] = self::PROGRAM_MULTI_ANYVALUE;
                }

            } else {
                $defaults['value'] = '';
                $defaults['operator'] = self::PROGRAM_MULTI_ANYVALUE;
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

        $objs = [];
        $objs[] =& $mform->createElement('select', $this->name . '_op', $label, $this->get_operators());
        $objs[] =& $mform->createElement('static', 'title' . $this->name, '',
            html_writer::tag('span', '', array('id' => $this->name . 'title', 'class' => 'dialog-result-title')));
        $mform->setType($this->name . '_op', PARAM_TEXT);

        $settings = array('multiple' => 'multiple');
        $choices = [];

        $programs = $DB->get_records('enrol_programs_programs');
        foreach ($programs as $program) {
            $choices[$program->id] = $program->fullname;
        }

        $objs[] = $mform->createElement('autocomplete', $this->name, $label, $choices, $settings);

        $mform->disabledIf($this->name . '[]', $this->name . '_op', 'eq', 0);
        $PAGE->requires->js_call_amd('local_reportbuilder/autocompletedisabled', 'watchautocomplete', [$this->name . '[]']);

        // Create a group for the elements.
        $grp =& $mform->addElement('group', $this->name . '_grp', $label, $objs, '', false);
        $mform->addHelpButton($grp->_name, 'reportbuilderdialogfilter', 'local_reportbuilder');

        if ($advanced) {
            $mform->setAdvanced($this->name . '_grp');
        }

        // Set default values.
        $defaults = filterstateprovider::fetch($this->report->get_uniqueid())->get_filter($this->name);
        if (isset($defaults['operator'])) {
            $mform->setDefault($this->name . '_op', $defaults['operator']);
        }
        if (isset($defaults['value'])) {
            $mform->setDefault($this->name, explode(',', $defaults['value']));
        }
    }

    /**
     * Returns an array of comparison operators.
     */
    public function get_operators() {
        return array(self::PROGRAM_MULTI_ANYVALUE => get_string('isanyvalue', 'local_reportbuilder'),
            self::PROGRAM_MULTI_EQUALTO => get_string('isequalto', 'local_reportbuilder'),
            self::PROGRAM_MULTI_NOTEQUALTO => get_string('isnotequalto', 'local_reportbuilder'));
    }

    /**
     * Retrieves data from the form data.
     *
     * @param object $formdata data submited with the form
     * @return mixed array filter data or false when filter not set
     */
    public function check_data($formdata) {
        $field = $this->name;
        $operator = $field . '_op';

        if (isset($formdata->$field) && $formdata->$field != '') {
            return array('operator' => (int)$formdata->$operator,
                'value' => implode(',', $formdata->$field));
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
        global $DB;

        $programids = explode(',', $data['value']);
        $query = $this->get_field();
        $operator = $data['operator'];

        switch ($operator) {
            case self::PROGRAM_MULTI_EQUALTO:
                $equal = true;
                break;
            case self::PROGRAM_MULTI_NOTEQUALTO:
                $equal = false;
                break;
            default:
                // Return 1=1 instead of TRUE for MSSQL support.
                return array(' 1=1 ', []);
        }

        // None selected - match everything.
        if (empty($programids)) {
            // Using 1=1 instead of TRUE for MSSQL support.
            return array(' 1=1 ', []);
        }

        list($insql, $params) = $DB->get_in_or_equal($programids, SQL_PARAMS_NAMED, 'cid', $equal);
        $sql = ' (' . $query . ') ' . $insql;

        return array($sql, $params);
    }

    /**
     * Returns a human friendly description of the filter used as label.
     *
     * @param array $data filter settings
     * @return string active filter label
     */
    public function get_label($data) {
        global $DB;

        $operator = $data['operator'];
        $values = explode(',', $data['value']);

        if (empty($operator) || empty($values)) {
            return '';
        }

        $a = new stdClass();
        $a->label = $this->label;

        $selected = [];
        list($insql, $inparams) = $DB->get_in_or_equal($values);
        if ($programs = $DB->get_records_select('enrol_programs_programs', "id " . $insql, $inparams)) {
            foreach ($programs as $program) {
                $selected[] = '"' . format_string($program->fullname) . '"';
            }
        }

        $orandstr = ($operator == self::PROGRAM_MULTI_EQUALTO) ? 'or' : 'and';
        $a->value = implode(get_string($orandstr, 'local_reportbuilder'), $selected);
        $operators = $this->get_operators();
        $a->operator = $operators[$operator];

        return get_string('selectlabel', 'local_reportbuilder', $a);
    }
}
