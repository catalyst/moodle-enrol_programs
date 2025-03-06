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

use enrol_programs\local\program;
use local_commerce\local\benefit;

/**
 * Commerce allocation source test.
 *
 * @group openlms
 * @group local_commerce
 *
 * @package enrol_programs
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2023, Andrew Hancox
 *
 * @covers \enrol_programs\local\source\ecommerce
 */
final class ecommerce_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();

        if (!\enrol_programs\local\source\ecommerce::is_commerce_available()) {
            $this->markTestSkipped('Commerce not available');
        }

        \local_commerce\local\util::enable_commerce();
    }

    public function test_get_type() {
        $this->assertSame('ecommerce', ecommerce::get_type());
    }

    public function test_is_new_alloved() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');
        $program = $generator->create_program();

        $this->assertFalse(ecommerce::is_new_allowed($program));

        set_config('source_ecommerce_allownew', 1, 'enrol_programs');
        $this->assertTrue(ecommerce::is_new_allowed($program));

        \local_commerce\local\util::disable_commerce();
        $this->assertFalse(ecommerce::is_new_allowed($program));
    }

    public function test_benefit_registration() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $this->assertFalse(benefit::get_record(['pluginname' => 'enrol_programs']));

        $program1 = $generator->create_program(['sources' => ['ecommerce' => []]]);

        ecommerce::update_source((object)[
            'programid' => $program1->id,
            'type' => 'ecommerce',
            'enable' => 1,
            'ecommerce_maxusers' => 2,
        ]);

        $benefit = benefit::get_record(['pluginname' => 'enrol_programs']);
        $this->assertEquals($program1->id, $benefit->get('instance'));
    }
}
