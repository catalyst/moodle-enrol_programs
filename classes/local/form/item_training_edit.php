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

use enrol_programs\local\content\training;

/**
 * Edit program training item.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_training_edit extends \local_openlms\dialog_form {
    protected function definition() {
        $mform = $this->_form;
        /** @var training $training */
        $training = $this->_customdata['training'];

        $mform->addElement('static', 'staticfullname', get_string('fullname'), format_string($training->get_fullname()));

        $mform->addElement('text', 'points', get_string('itempoints', 'enrol_programs'));
        $mform->setType('points', PARAM_INT);
        $mform->setDefault('points', $training->get_points());

        $mform->addElement('duration', 'completiondelay', get_string('completiondelay', 'enrol_programs'),
            ['optional' => true, 'defaultunit' => DAYSECS]);
        $mform->setDefault('completiondelay', $training->get_completiondelay());

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $training->get_id());

        $this->add_action_buttons(true, get_string('updatetraining', 'enrol_programs'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        return $errors;
    }
}
