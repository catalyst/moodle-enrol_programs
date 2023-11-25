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

namespace enrol_programs\local\source;

use enrol_programs\local\allocation;
use enrol_programs\local\notification_manager;
use html_writer;
use local_commerce\local\benefit;
use local_commerce\local\product;
use stdClass;

/**
 * Commerce source.
 *
 * @package enrol_programs
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2023, Andrew Hancox
 */
final class ecommerce extends base {
    /**
     * Return short type name of source, it is used in database to identify this source.
     *
     * NOTE: this must be unique and ite cannot be changed later
     *
     * @return string
     */
    public static function get_type(): string {
        return 'ecommerce';
    }

    /**
     * Can a new source of this type be added to programs?
     *
     * NOTE: Existing enabled sources in programs cannot be deleted/hidden
     * if there are any allocated users to program.
     *
     * @param stdClass $program
     * @return bool
     */
    public static function is_new_allowed(\stdClass $program): bool {
        if (!get_config('local_commerce', 'enablecommerce')) {
            return false;
        }

        return parent::is_new_allowed($program);
    }

    /**
     * Decode extra source settings.
     *
     * @param stdClass $source
     * @return stdClass
     */
    public static function decode_datajson(stdClass $source): stdClass {
        $source->ecommerce_maxusers = '';
        $source->ecommerce_allowsignup = 1;

        if (isset($source->datajson)) {
            $data = (object)json_decode($source->datajson);
            if (isset($data->maxusers) && $data->maxusers !== '') {
                $source->ecommerce_maxusers = (int)$data->maxusers;
            }
            if (isset($data->allowsignup)) {
                $source->ecommerce_allowsignup = (int)(bool)$data->allowsignup;
            }
        }

        return $source;
    }

    /**
     * Encode extra source settings.
     *
     * @param stdClass $formdata
     * @return string
     */
    public static function encode_datajson(stdClass $formdata): string {
        $data = ['maxusers' => null, 'key' => null, 'allowsignup' => 1];
        if (isset($formdata->ecommerce_maxusers)
            && trim($formdata->ecommerce_maxusers) !== ''
            && $formdata->ecommerce_maxusers >= 0) {

            $data['maxusers'] = (int)$formdata->ecommerce_maxusers;
        }
        if (isset($formdata->ecommerce_allowsignup)) {
            $data['allowsignup'] = (int)(bool)$formdata->ecommerce_allowsignup;
        }
        return \enrol_programs\local\util::json_encode($data);
    }

    /**
     * Allocate users manually.
     *
     * @param int $programid
     * @param int $sourceid
     * @param array $userids
     * @param array $dateoverrides
     * @return void
     */
    public static function grantbenefit(int $programid, int $userid, array $dateoverrides = []): void {
        global $DB;

        $program = $DB->get_record('enrol_programs_programs', ['id' => $programid], '*', MUST_EXIST);
        $source = $DB->get_record('enrol_programs_sources', ['type' => static::get_type(), 'programid' => $program->id], '*', MUST_EXIST);

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);
        if ($allocation = $DB->get_record(
            'enrol_programs_allocations',
            ['programid' => $program->id, 'userid' => $user->id]
        )) {
            if ($allocation->sourceid != $source->id) {
                return;
            }

            $allocation->timestart = $dateoverrides['timestart'];
            $allocation->timeend = $dateoverrides['timeend'];
            $allocation->archived = false;
            allocation::update_user($allocation);
        } else {
            self::allocate_user($program, $source, $user->id, [], $dateoverrides);
            allocation::fix_user_enrolments($programid, $userid);
            notification_manager::trigger_notifications($programid, $userid);
        }
    }

    /**
     * Callback method for source updates.
     *
     * We never de-register the benefit handler as marking an allocation source as inactive feels
     * like a potentially temporary act.
     *
     * @param stdClass|null $oldsource
     * @param stdClass $data
     * @param stdClass|null $source
     * @return void
     */
    public static function after_update(?stdClass $oldsource, stdClass $data, ?stdClass $source): void {
        benefit::register('enrol_programs', $source->programid);
    }

    /**
     * Returns list of actions available in Program catalogue.
     *
     * NOTE: This is intended mainly for students.
     *
     * @param stdClass $program
     * @param stdClass $source
     * @return string[]
     */
    public static function get_catalogue_actions(\stdClass $program, \stdClass $source): array {
        global $USER, $OUTPUT, $PAGE;

        $products = product::get_products_to_offer_for_sale('enrol_programs', $program->id, 0, false, $USER->id);

        if (empty($products)) {
            return [];
        }

        $retval = [];

        if (!empty($products)) {
            $output = html_writer::tag('legend', $OUTPUT->heading(get_string('purchaseaccess', 'enrol_programs')));
            $output .= $PAGE->get_renderer('local_commerce')->render_products($products, false);

            $retval[] = $output;
        }

        return $retval;
    }
}