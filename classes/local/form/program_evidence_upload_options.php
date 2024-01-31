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

namespace enrol_programs\local\form;

/**
 * Allocate users via file upload.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_evidence_upload_options extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $program = $this->_customdata['program'];
        $context = $this->_customdata['context'];
        $csvfile = $this->_customdata['csvfile'];
        $filedata = $this->_customdata['filedata'];

        $preview = new \html_table();
        $preview->data = [];
        $i = 0;
        foreach ($filedata as $row) {
            $i++;
            if ($i > 5) {
                $preview->data[] = array_fill(0, count($row), '...');
                break;
            }
            $preview->data[] = array_map('s', $row);
        }
        $mform->addElement('static', 'preview', get_string('preview'), \html_writer::table($preview));

        $fileoptions = reset($filedata);
        $mform->addElement('select', 'usercolumn', get_string('source_manual_usercolumn', 'enrol_programs'), $fileoptions);
        $firstcolumn = reset($fileoptions);

        $options = [
            'username' => get_string('username'),
            'idnumber' => get_string('idnumber'),
            'email' => get_string('email'),
        ];
        $mform->addElement('select', 'usermapping', get_string('source_manual_usermapping', 'enrol_programs'), $options);
        if (isset($options[$firstcolumn])) {
            $mform->setDefault('usermapping', $firstcolumn);
        }

        $mform->addElement('advcheckbox', 'hasheaders', get_string('source_manual_hasheaders', 'enrol_programs'));
        if (isset($options[$filedata[0][0]])) {
            $mform->setDefault('hasheaders', 1);
        }

        $options = [-1 => get_string('choose')] + $fileoptions;
        $mform->addElement('select', 'completiondatecolumn', get_string('completiondate', 'enrol_programs'), $options);
        $mform->addRule('completiondatecolumn', get_string('required'), 'required');

        $mform->addElement('select', 'evidencedetails', get_string('evidence_details', 'enrol_programs'), $options);

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $program->id);

        $mform->addElement('hidden', 'csvfile');
        $mform->setType('csvfile', PARAM_INT);
        $mform->setDefault('csvfile', $csvfile);

        $this->add_action_buttons(true, get_string('uploadotherevidence', 'enrol_programs'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        $usedfields = [];

        $columns = ['completiondatecolumn', 'usermapping', 'evidencedetails'];
        foreach ($columns as $column) {
            if ($data[$column] != -1 && in_array($data[$column], $usedfields)) {
                $errors[$column] = get_string('columnusedalready', 'enrol_programs');
            } else {
                $usedfields[] = $data[$column];
            }
        }

        return $errors;
    }
}
