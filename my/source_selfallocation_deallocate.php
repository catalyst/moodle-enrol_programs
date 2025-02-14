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
 * Confirm self-allocation deallocate from program.
 *
 * @package    enrol_programs
 * @copyright  2024 Catalyst IT Australia Pty Ltd
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

if (!empty($_SERVER['HTTP_X_LEGACY_DIALOG_FORM_REQUEST'])) {
    define('AJAX_SCRIPT', true);
}

require('../../../config.php');

$sourceid = required_param('sourceid', PARAM_INT);

require_login();

$usercontext = context_user::instance($USER->id);
$PAGE->set_context($usercontext);
$PAGE->set_url(new moodle_url('/enrol/programs/my/source_selfallocation_deallocate.php', ['sourceid' => $sourceid]));

if (!enrol_is_enabled('programs')) {
    redirect(new moodle_url('/'));
}
if (isguestuser()) {
    redirect(new moodle_url('/enrol/programs/catalogue/program.php'));
}

$source = $DB->get_record('enrol_programs_sources', ['id' => $sourceid, 'type' => 'selfallocation'], '*', MUST_EXIST);
$program = $DB->get_record('enrol_programs_programs', ['id' => $source->programid]);
if (!$program || $program->archived) {
    redirect(new moodle_url('/enrol/programs/my/index.php'));
}

$allocation = $DB->get_record('enrol_programs_allocations', ['programid' => $program->id, 'userid' => $USER->id]);
if (!$allocation || $allocation->archived) {
    if (\enrol_programs\local\catalogue::is_program_visible($program)) {
        redirect(new moodle_url('/enrol/programs/catalogue/program.php', ['id' => $program->id]));
    } else {
        redirect(new moodle_url('/enrol/programs/my/index.php'));
    }
}

$title = get_string('myprograms', 'enrol_programs');
$PAGE->navigation->extend_for_user($USER);
$PAGE->set_title($title);
$PAGE->set_pagelayout('report');
$PAGE->navbar->add(get_string('profile'), new moodle_url('/user/profile.php', ['id' => $USER->id]));
$PAGE->navbar->add($title, new moodle_url('/enrol/programs/my/index.php'));
$PAGE->navbar->add(format_string($program->fullname));

$returnurl = new moodle_url('/enrol/programs/my/program.php', ['id' => $program->id]);

$form = new enrol_programs\local\form\source_selfallocation_deallocate(null, ['source' => $source]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    enrol_programs\local\source\selfallocation::deallocate_user($program, $source, $allocation);
    $returnurl = new moodle_url('/enrol/programs/my/index.php');
    $message = get_string('source_selfallocation_deallocated', 'enrol_programs', format_string($program->fullname));
    $form->redirect_submitted($returnurl, $message);
}

/** @var \enrol_programs\output\my\renderer $myoutput */
$myoutput = $PAGE->get_renderer('enrol_programs', 'my');

echo $OUTPUT->header();
echo $myoutput->render_program($program);
echo $form->render();
echo $OUTPUT->footer();
