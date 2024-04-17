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

use enrol_programs\local\content\set;
use enrol_programs\external\form_item_append_frameworkid;

/**
 * Add program content item.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class item_append extends \local_openlms\dialog_form {
    protected function definition() {
        global $DB;

        $mform = $this->_form;
        /** @var set $parentset */
        $parentset = $this->_customdata['parentset'];

        $select = 'programid = :programid AND courseid IS NOT NULL';
        $params = ['programid' => $parentset->get_programid()];
        $exclude = $DB->get_fieldset_select('enrol_programs_items', 'courseid', $select, $params);

        $mform->addElement('course', 'courses', get_string('courses'),
            ['multiple' => true, 'exclude' => $exclude, 'requiredcapabilities' => ['enrol/programs:addcourse']]);

        if ($DB->record_exists('customfield_training_frameworks', ['archived' => 0])) {
            $arguments = ['programid' => $parentset->get_programid()];
            form_item_append_frameworkid::add_form_element(
                $mform, $arguments, 'frameworkid', get_string('training', 'enrol_programs'));
        }

        $mform->addElement('select', 'addset', get_string('addset', 'enrol_programs'), ['0' => get_string('no'), '1' => get_string('yes')]);

        $mform->addElement('text', 'fullname', get_string('fullname'), 'maxlength="254" size="50"');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->hideIf('fullname', 'addset', 'eq', 0);

        $mform->addElement('text', 'points', get_string('itempoints', 'enrol_programs'));
        $mform->setType('points', PARAM_INT);
        $mform->setDefault('points', '1');

        $stypes = set::get_sequencetype_types();
        $mform->addElement('select', 'sequencetype', get_string('sequencetype', 'enrol_programs'), $stypes);
        $mform->hideIf('sequencetype', 'addset', 'eq', 0);

        $mform->addElement('text', 'minprerequisites', $stypes[set::SEQUENCE_TYPE_ATLEAST]);
        $mform->setType('minprerequisites', PARAM_INT);
        $mform->setDefault('minprerequisites', 1);
        $mform->hideIf('minprerequisites', 'addset', 'eq', 0);
        $mform->hideIf('minprerequisites', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_ATLEAST);

        $mform->addElement('text', 'minpoints', $stypes[set::SEQUENCE_TYPE_MINPOINTS]);
        $mform->setType('minpoints', PARAM_INT);
        $mform->hideIf('minpoints', 'sequencetype', 'noteq', set::SEQUENCE_TYPE_MINPOINTS);
        $mform->setDefault('minpoints', 1);

        $mform->addElement('duration', 'completiondelay', get_string('completiondelay', 'enrol_programs'),
            ['optional' => true, 'defaultunit' => DAYSECS]);

        $mform->addElement('hidden', 'parentitemid');
        $mform->setType('parentitemid', PARAM_INT);
        $mform->setDefault('parentitemid', $parentset->get_id());

        $this->add_action_buttons(true, get_string('appenditem', 'enrol_programs'));
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $context = $this->_customdata['context'];
        /** @var set $parentset */
        $parentset = $this->_customdata['parentset'];

        if ($data['points'] < 0) {
            $errors['points'] = get_string('error');
        }

        if ($data['addset']) {
            if (trim($data['fullname']) === '') {
                $errors['fullname'] = get_string('required');
            }
            if ($data['sequencetype'] === set::SEQUENCE_TYPE_ATLEAST) {
                if ($data['minprerequisites'] <= 0) {
                    $errors['minprerequisites'] = get_string('required');
                }
            } else if ($data['sequencetype'] === set::SEQUENCE_TYPE_MINPOINTS) {
                if ($data['minpoints'] <= 0) {
                    $errors['minpoints'] = get_string('required');
                }
            }
        } else {
            if (!$data['courses'] && empty($data['frameworkid'])) {
                $errors['courses'] = get_string('required');
            } else {
                if (\enrol_programs\local\tenant::is_active()) {
                    $tenantid = \tool_olms_tenant\tenants::get_context_tenant_id($context);
                    if ($tenantid) {
                        foreach ($data['courses'] as $courseid) {
                            // The caps are removed in other tenants, but we need to make sure
                            // admins do not add other tenant courses accidentally.
                            $coursecontext = \context_course::instance($courseid);
                            $coursetenantid = \tool_olms_tenant\tenants::get_context_tenant_id($coursecontext);
                            if ($coursetenantid && $coursetenantid != $tenantid) {
                                $errors['courses'] = get_string('errordifferenttenant', 'enrol_programs');
                                break;
                            }
                        }
                    }
                }
            }
        }

        if (!empty($data['frameworkid'])) {
            $arguments = ['programid' => $parentset->get_programid()];
            $error = form_item_append_frameworkid::validate_form_value($arguments, $data['frameworkid']);
            if ($error !== null) {
                $errors['frameworkid'] = $error;
            }
        }

        return $errors;
    }
}
