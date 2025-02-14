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
 * Program self-allocation deallocate confirmation.
 *
 * @package    enrol_programs
 * @copyright  2024 Catalyst IT Australia Pty Ltd
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class source_selfallocation_deallocate extends \local_openlms\dialog_form {
    /**
     * Define the form.
     */
    protected function definition() {
        global $OUTPUT;
        $mform = $this->_form;
        $source = $this->_customdata['source'];

        $confirmation = markdown_to_html(get_string('source_selfallocation_deallocate_confirm', 'enrol_programs'));
        $mform->addElement('static', 'confirmation', '', clean_text($confirmation));

        $mform->addElement('hidden', 'sourceid');
        $mform->setType('sourceid', PARAM_INT);
        $mform->setDefault('sourceid', $source->id);

        $this->add_action_buttons(true, get_string('source_selfallocation_deallocate', 'enrol_programs'));
    }
}
