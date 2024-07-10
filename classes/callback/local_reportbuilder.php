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

namespace enrol_programs\callback;

/**
 * Hook callbacks from local_reportbuilder related code.
 *
 * @package    enrol_programs
 * @copyright  2024 Open LMS
 * @author     Petr Skoda
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class local_reportbuilder {
    /**
     * Callback method for discovering of primary navigation item classes.
     * @param \local_reportbuilder\hook\report_sources $hook
     */
    public static function report_sources(\local_reportbuilder\hook\report_sources $hook): void {
        $hook->add_class(
            \enrol_programs\rb\source\allocations::get_sourcename(),
            \enrol_programs\rb\source\allocations::class);
    }
}
