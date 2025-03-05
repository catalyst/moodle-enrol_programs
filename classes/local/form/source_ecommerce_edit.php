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
 * Edit cohort allocation settings.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_ecommerce_edit extends \local_openlms\dialog_form {
    protected function definition() {
        global $DB;
        $mform = $this->_form;
        $source = $this->_customdata['source'];
        $program = $this->_customdata['program'];

        $mform->addElement('select', 'enable', get_string('active'), ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('enable', $source->enable);
        if ($source->hasallocations) {
            $mform->hardFreeze('enable');
        }

        $mform->addElement('select', 'ecommerce_allowsignup', get_string('source_ecommerce_allowsignup', 'enrol_programs'),
            ['1' => get_string('yes'), '0' => get_string('no')]);
        $mform->setDefault('ecommerce_allowsignup', 1);
        $mform->hideIf('ecommerce_allowsignup', 'enable', 'eq', '0');

        $mform->addElement('text', 'ecommerce_maxusers', get_string('source_ecommerce_maxusers', 'enrol_programs'), 'size="8"');
        $mform->setType('ecommerce_maxusers', PARAM_RAW);
        $mform->setDefault('ecommerce_maxusers', $source->ecommerce_maxusers);
        $mform->hideIf('ecommerce_maxusers', 'enable', 'eq', '0');

        $mform->addElement('hidden', 'programid');
        $mform->setType('programid', PARAM_INT);
        $mform->setDefault('programid', $program->id);

        $mform->addElement('hidden', 'type');
        $mform->setType('type', PARAM_ALPHANUMEXT);
        $mform->setDefault('type', $source->type);

        $this->add_action_buttons(true, get_string('update'));
    }
}
