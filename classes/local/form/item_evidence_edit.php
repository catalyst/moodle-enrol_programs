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
 * Edit item completion evidence data.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_evidence_edit extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        $context = $this->_customdata['context'];
        $allocation = $this->_customdata['allocation'];
        $item = $this->_customdata['item'];
        $completion = $this->_customdata['completion'];
        $evidence = $this->_customdata['evidence'];

        $mform->addElement('static', 'staticitem', get_string('item', 'enrol_programs'),
            format_string($item->fullname));

        if ($completion && $completion->timecompleted) {
            $strcompleted = userdate($completion->timecompleted);
        } else {
            $strcompleted = get_string('notset', 'enrol_programs');
        }
        $mform->addElement('static', 'statictimecompleted', get_string('completiondate', 'enrol_programs'), $strcompleted);

        $mform->addElement('date_time_selector', 'evidencetimecompleted', get_string('evidencedate', 'enrol_programs'), ['optional' => true]);
        if ($evidence && $evidence->timecompleted) {
            $mform->setDefault('evidencetimecompleted', $evidence->timecompleted);
        }

        $mform->addElement('textarea', 'evidencedetails', get_string('evidence_details' , 'enrol_programs'));
        $mform->setType('evidencedetails', PARAM_TEXT);
        if ($evidence && $evidence->evidencejson) {
            $data = (object)json_decode($evidence->evidencejson);
            if ($data->details) {
                $mform->setDefault('evidencedetails', $data->details);
            }
        }
        $mform->hideIf('evidencedetails', 'evidencetimecompleted[enabled]', 'notchecked');

        $mform->addElement('advcheckbox', 'itemrecalculate', get_string('itemrecalculate' , 'enrol_programs'));
        if (!$item->topitem && $evidence && $completion && $evidence->timecompleted == $completion->timecompleted) {
            $mform->setDefault('itemrecalculate', 1);
        }

        $mform->addElement('hidden', 'allocationid');
        $mform->setType('allocationid', PARAM_INT);
        $mform->setDefault('allocationid', $allocation->id);

        $mform->addElement('hidden', 'itemid');
        $mform->setType('itemid', PARAM_INT);
        $mform->setDefault('itemid', $item->id);

        $this->add_action_buttons(true, get_string('update'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['evidencetimecompleted']) {
            if (trim($data['evidencedetails']) === '') {
                $errors['evidencedetails'] = get_string('required');
            }
        }

        return $errors;
    }
}
