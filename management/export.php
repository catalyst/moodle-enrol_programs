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
 * Programs export.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use enrol_programs\local\management;
use enrol_programs\local\export;

/** @var moodle_database $DB */
/** @var moodle_page $PAGE */
/** @var core_renderer $OUTPUT */
/** @var stdClass $CFG */

if (!empty($_POST['format'])) {
    define('NO_DEBUG_DISPLAY', true);
}

require('../../../config.php');

require_login();

$id = optional_param('id', 0, PARAM_INT);
$contextid = optional_param('contextid', 0, PARAM_INT);
$archived = optional_param('archived', 0, PARAM_BOOL);

if ($id) {
    $program = $DB->get_record('enrol_programs_programs', ['id' => $id], '*', MUST_EXIST);
    $context = context::instance_by_id($program->contextid);
    $returnurl = new moodle_url('/enrol/programs/management/program.php', ['id' => $program->id]);
    $contextid = $context->id;
    $archived = $program->archived;
} else {
    $program = null;
    if ($contextid) {
        $context = context::instance_by_id($contextid);
    } else {
        $context = context_system::instance();
    }
    $returnurl = new moodle_url('/enrol/programs/management/index.php',
        ['contextid' => $contextid, 'archived' => $archived]);
}
$currenturl = new moodle_url('/enrol/programs/management/export.php',
    ['id' => $id, 'contextid' => $contextid, 'archived' => $archived]);

require_capability('enrol/programs:export', $context);

if ($program) {
    management::setup_program_page($currenturl, $context, $program);
} else {
    management::setup_index_page($currenturl, $context, $contextid);
}

$form = new \enrol_programs\local\form\export(null,
    ['program' => $program, 'contextid' => $contextid, 'archived' => $archived]);

if ($form->is_cancelled()) {
    redirect($returnurl);
}
if ($data = $form->get_data()) {
    export::process($data);
    die;
}

$PAGE->set_heading(get_string('export', 'enrol_programs'));
echo $OUTPUT->header();

echo $form->render();

echo $OUTPUT->footer();
