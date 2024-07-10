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

namespace enrol_programs\rb\form;

require_once("$CFG->dirroot/lib/formslib.php");

use html_writer;
use moodleform;

/**
 * Form to show expanded details of a program in a report
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @link       https://www.openlms.net/
 */
final class program_expand extends moodleform {
    public function definition() {
        $mform = $this->_form;

        // The following are required.
        $summary = $this->_customdata['summary'];
        $status = $this->_customdata['status'];
        $programid = $this->_customdata['programid'];
        $allocationtype = isset($this->_customdata['allocationtype']) ? $this->_customdata['allocationtype'] : '';
        $progress = isset($this->_customdata['progress']) ? $this->_customdata['progress'] : '';
        $enddate = isset($this->_customdata['enddate']) ? $this->_customdata['enddate'] : '';
        $action = isset($this->_customdata['action']) ? $this->_customdata['action'] : '';
        $url = isset($this->_customdata['url']) ? $this->_customdata['url'] : '';

        if ($summary != '') {
            $mform->addElement('static', 'summary', get_string('rb_programsummary', 'enrol_programs'), $summary);
        }
        if ($status != '') {
            $mform->addElement('static', 'status', get_string('status'), $status);
        }
        if ($allocationtype != '') {
            $mform->addElement('static', 'allocationtype',
                get_string('rb_programallocationtype', 'enrol_programs'), $allocationtype);
        }
        if ($progress != '') {
            $mform->addElement('static', 'progress', get_string('rb_progresspercent', 'enrol_programs'), $progress);
        }
        if ($enddate != '') {
            $mform->addElement('static', 'enddate', get_string('rb_programenddate', 'enrol_programs'), $enddate);
        }

        if ($url != '') {
            $link = html_writer::link($url, $action, array('class' => 'link-as-button btn btn-default'));
            $mform->addElement('static', 'enrol', '', $link);
        }

        $mform->addElement('hidden', 'programid', $programid);
        $mform->setType('programid', PARAM_INT);
    }
}
