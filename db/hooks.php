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
 * Programs hook callbacks.
 *
 * @package    enrol_progrmas
 * @copyright  2023 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$callbacks = [
    [
        'hook' => \local_navmenu\hook\item_classes::class,
        'callback' => \enrol_programs\callback\local_navmenu::class . '::item_classes',
    ],
    [
        'hook' => \customfield_training\hook\framework_usage::class,
        'callback' => \enrol_programs\callback\customfield_training::class . '::framework_usage',
    ],
    [
        'hook' => \customfield_training\hook\completion_updated::class,
        'callback' => \enrol_programs\callback\customfield_training::class . '::completion_updated',
    ],
    [
        'hook' => \local_reportbuilder\hook\report_sources::class,
        'callback' => \enrol_programs\callback\local_reportbuilder::class . '::report_sources',
    ],
];
