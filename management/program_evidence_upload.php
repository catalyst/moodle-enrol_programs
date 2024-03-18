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
 * Uploads program evidence.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */
/** @var stdClass $COURSE */

use enrol_programs\local\management;
use enrol_programs\local\source\manual;

if (!empty($_SERVER['HTTP_X_LEGACY_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}

require('../../../config.php');
require_once($CFG->dirroot . '/lib/formslib.php');

$programid = required_param('programid', PARAM_INT);
$draftitemid = optional_param('csvfile', null, PARAM_INT);

require_login();

$program = $DB->get_record('enrol_programs_programs', ['id' => $programid], '*', MUST_EXIST);
$context = context::instance_by_id($program->contextid);
require_capability('enrol/programs:manageevidence', $context);

$currenturl = new moodle_url('/enrol/programs/management/program_evidence_upload.php', ['programid' => $programid]);
$returnurl = new moodle_url('/enrol/programs/management/program_users.php', ['id' => $programid]);

if ($program->archived) {
    redirect($returnurl);
}

management::setup_program_page($currenturl, $context, $program);

$filedata = null;
if ($draftitemid && confirm_sesskey()) {
    $filedata = \enrol_programs\local\util::get_uploaded_data($draftitemid);
}

if (!$filedata) {
    $form = new \enrol_programs\local\form\program_evidence_upload_file(null, ['program' => $program, 'context' => $context]);
} else {
    $form = new \enrol_programs\local\form\program_evidence_upload_options(null, ['program' => $program,
        'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
}

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    if ($filedata && $form instanceof \enrol_programs\local\form\program_evidence_upload_options) {
        $result = \enrol_programs\local\allocation::process_evidence_uploaded_data($data, $filedata);

        if ($result['updated']) {
            $message = get_string('evidenceupload_updated', 'enrol_programs', $result['updated']);
            \core\notification::add($message, \core\output\notification::NOTIFY_SUCCESS);
        }
        if ($result['skipped']) {
            $message = get_string('evidenceupload_skipped', 'enrol_programs', $result['skipped']);
            \core\notification::add($message, \core\output\notification::NOTIFY_INFO);
        }
        if ($result['errors']) {
            $message = get_string('evidenceupload_errors', 'enrol_programs', $result['errors']);
            \core\notification::add($message, \core\output\notification::NOTIFY_WARNING);
        }

        $form->redirect_submitted($returnurl);
    }
    if (!$filedata && $form instanceof \enrol_programs\local\form\program_evidence_upload_file) {
        $filedata = \enrol_programs\local\util::get_uploaded_data($draftitemid);
        if ($filedata) {
            $form = new \enrol_programs\local\form\program_evidence_upload_options(null, ['program' => $program,
                'context' => $context, 'csvfile' => $draftitemid, 'filedata' => $filedata]);
        }
    }
}

/** @var \enrol_programs\output\management\renderer $managementoutput */
$managementoutput = $PAGE->get_renderer('enrol_programs', 'management');

echo $OUTPUT->header();

echo $managementoutput->render_management_program_tabs($program, 'users');

echo $OUTPUT->heading(get_string('evidenceupload', 'enrol_programs'), 3);

echo $form->render();

echo $OUTPUT->footer();
