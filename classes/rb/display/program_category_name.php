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

namespace enrol_programs\rb\display;

use local_reportbuilder\rb_column;
use local_reportbuilder\rb_column_option;
use local_reportbuilder\reportbuilder;
use stdClass;

/*
 * Program category display class.
 *
 * @package   enrol_programs
 * @copyright 2024 Open LMS
 * @author    Petr Skoda
 * @license   https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class program_category_name extends \local_reportbuilder\rb\display\base {
    public static function display($value, $format, stdClass $row, rb_column $column, reportbuilder $report) {
        $extra = self::get_extrafields_row($row, $column);

        $syscontext = \context_system::instance();
        if ($syscontext->id == $value) {
            $name = $syscontext->get_context_name();
        } else {
            $name = format_string($extra->category_name);
        }

        if ($format === 'html') {
            return $name;
        }

        $name = \core_text::entities_to_utf8($name);

        if ($format === "excel" or $format === "ods") {
            return array('string', $name, null);
        }
        // Other tabexport plugins will do their own escaping.
        return $name;
    }

    public static function is_graphable(rb_column $column, rb_column_option $option, reportbuilder $report) {
        return false;
    }
}
