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

/**
 * Programs upload.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_programs\local\management;
use enrol_programs\local\upload;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

require('../../../config.php');

require_login();

$contextid = optional_param('contextid', 0, PARAM_INT);
$draftid = optional_param('files', 0, PARAM_INT);

$returnurl = new moodle_url('/enrol/programs/management/index.php', ['contextid' => $contextid]);
$currenturl = new moodle_url('/enrol/programs/management/upload.php', ['contextid' => $contextid]);

if ($contextid) {
    $context = context::instance_by_id($contextid);
} else {
    $context = context_system::instance();
}
require_capability('enrol/programs:upload', $context);

management::setup_index_page($currenturl, $context, $contextid);

$filedata = null;
if ($draftid && confirm_sesskey()) {
    $filedata = \enrol_programs\local\util::get_uploaded_data($draftid, false);
}

if (!$filedata) {
    $form = new \enrol_programs\local\form\upload_files(null, ['contextid' => $contextid]);
} else {
    $form = new \enrol_programs\local\form\upload_options(null, [
        'files' => $draftid, 'contextid' => $contextid, 'filedata' => $filedata]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if ($filedata && $form instanceof \enrol_programs\local\form\upload_options) {
        upload::process($data, $filedata);
        redirect($returnurl);
    }
    if (!$filedata && $form instanceof \enrol_programs\local\form\upload_files) {
        $filedata = \enrol_programs\local\util::get_uploaded_data($draftid, false);
        if ($filedata) {
            $form = new \enrol_programs\local\form\upload_options(null, [
                'files' => $draftid, 'contextid' => $contextid, 'filedata' => $filedata]);
        }
    }
}

$PAGE->set_heading(get_string('upload', 'enrol_programs'));
echo $OUTPUT->header();

echo $form->render();

if ($filedata) {
    echo $OUTPUT->heading(get_string('upload_preview', 'enrol_programs'), 3);
    echo upload::preview($filedata);
}

echo $OUTPUT->footer();
