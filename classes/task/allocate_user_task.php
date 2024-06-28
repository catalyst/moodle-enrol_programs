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

namespace enrol_programs\task;

/**
 * Program user allocation task.
 *
 * @package    enrol_programs
 * @author     Rossco Hellmans <rosscohellmans@catalyst-au.net>
 * @copyright  2024 Catalyst IT Australia Pty Ltd
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class allocate_user_task extends \core\task\adhoc_task {
    /**
     * Get task name
     */
    public function get_name() {
        return get_string('allocateusertask', 'enrol_programs');
    }

    /**
     * Execute task
     */
    public function execute() {
        $data = $this->get_custom_data();
        $sourceclass = $data->sourceclass;

        $sourceclass::task_allocate_user($data->programid, $data->sourceid, $data->userid, []);
    }
}


