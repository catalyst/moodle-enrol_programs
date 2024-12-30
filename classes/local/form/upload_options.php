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

global $CFG;
require_once($CFG->dirroot . '/lib/formslib.php');

/**
 * Upload programs confirmation.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class upload_options extends \moodleform {
    protected $uploadcount;
    protected $invalidcount;

    protected function definition() {
        $mform = $this->_form;
        $contextid = $this->_customdata['contextid'];
        $draftid = $this->_customdata['files'];
        $filedata = $this->_customdata['filedata'];

        $this->uploadcount = 0;
        $this->invalidcount = 0;
        $cateogryfail = false;
        foreach ($filedata as $program) {
            if ($program->errors) {
                $this->invalidcount++;
                continue;
            }
            $this->uploadcount++;
            if (!$program->contextid) {
                $cateogryfail = true;
            }
        }

        $mform->addElement('advcheckbox', 'usecategory', get_string('upload_usecategory', 'enrol_programs'), '&nbsp;');
        if ($cateogryfail) {
            $mform->setConstant('usecategory', 0);
            $mform->hardFreeze('usecategory');
        } else {
            $mform->setDefault('usecategory', 1);
        }

        $mform->addElement('select', 'contextid', get_string('upload_targetcontext', 'enrol_programs'), self::get_category_options());
        if ($contextid) {
            $mform->setDefault('contextid', $contextid);
        }
        $mform->hideIf('contextid', 'usecategory', 'eq', 1);

        $mform->addElement('static', 'uploadcount', get_string('upload_uploadcount', 'enrol_programs'), $this->uploadcount);
        $mform->addElement('static', 'invalidcount', get_string('upload_invalidcount', 'enrol_programs'), $this->invalidcount);

        $mform->addElement('hidden', 'files');
        $mform->setType('files', PARAM_INT);
        $mform->setDefault('files', $draftid);

        if ($this->uploadcount) {
            $this->add_action_buttons(true, get_string('upload', 'enrol_programs'));
        } else {
            $mform->addElement('cancel');
        }
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        return $errors;
    }

    protected function get_category_options(): array {
        $syscontext = \context_system::instance();
        if (has_capability('enrol/programs:upload', $syscontext)) {
            $options[$syscontext->id] = $syscontext->get_context_name();
        }
        $categories = \core_course_category::make_categories_list('enrol/programs:upload');
        foreach ($categories as $catid => $categoryname) {
            $catcontext = \context_coursecat::instance($catid);
            $options[$catcontext->id] = $categoryname;
        }
        return $options;
    }
}