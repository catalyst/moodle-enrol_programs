<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Configuration for custom fields programs.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Farhan Karmali
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->libdir.'/adminlib.php');

admin_externalpage_setup('programs_customfield');

$output = $PAGE->get_renderer('core_customfield');

$handler = \enrol_programs\customfield\fields_handler::create();
$outputpage = new \core_customfield\output\management($handler);

echo $output->header(),
$output->heading(new lang_string('customfields', 'enrol_programs')),
$output->render($outputpage),
$output->footer();