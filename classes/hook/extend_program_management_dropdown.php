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

namespace enrol_programs\hook;

use core\context;
use local_openlms\output\extra_menu\dropdown;

/**
 * Extra menu in user allocation tab for program.
 *
 * @package    enrol_programs
 * @copyright  2025 Catalyst IT Australia
 * @author     Matthew Hilton <matthewhilton@catalyst-au.net>
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\attribute\tags('programs')]
#[\core\attribute\label('Exposes dropdown shown on programs management index page so plugins can add own items')]
final class extend_program_management_dropdown {

    /**
     * Create hook for extra menu.
     *
     * @param \stdClass $program
     */
    public function __construct(
        /** @var dropdown $dropdown */
        public readonly dropdown $dropdown,
        /** @var context $context */
        public readonly context $context,
        /** @var object $program */
        public readonly object $program,
    ) {
    }
}
