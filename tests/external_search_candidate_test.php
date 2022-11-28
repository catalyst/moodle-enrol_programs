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

namespace enrol_programs;

/**
 * External API for program allocation candidate test.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @covers \enrol_programs\external\search_candidate
 */
final class external_search_candidate_test extends \advanced_testcase {
    public function setUp(): void {
        $this->resetAfterTest();
    }

    public function test_execution() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/lib/externallib.php");

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $category2 = $this->getDataGenerator()->create_category([]);
        $catcontext2 = \context_coursecat::instance($category2->id);

        $program1 = $generator->create_program(['fullname' => 'hokus', 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'pokus', 'contextid' => $catcontext2->id, 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('enrol_programs_sources', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1']);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2']);
        $user3 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 3']);
        $user4 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 4']);
        $user5 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 5']);

        $managerrole = $DB->get_record('role', ['shortname' => 'manager'], '*', MUST_EXIST);
        role_assign($managerrole->id, $user5->id, $catcontext2);

        \enrol_programs\local\source\manual::allocate_users($program1->id, $source1->id, [$user1->id, $user2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $CFG->maxusersperpage = 10;
        $result = \enrol_programs\external\search_candidate::execute('', $program1->id);
        $this->assertSame(10, $result['maxusersperpage']);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(4, $result['list']); // Admin is included.
        foreach ($result['list'] as $u) {
            if ($u->id == $user3->id) {
                $this->assertSame(fullname($user3, true), $u->fullname);
            } else if ($u->id == $user4->id) {
                $this->assertSame(fullname($user4, true), $u->fullname);
            } else if ($u->id == $user5->id) {
                $this->assertSame(fullname($user5, true), $u->fullname);
            } else if ($u->id == $admin->id) {
                $this->assertSame(fullname($admin, true), $u->fullname);
            } else {
                $this->fail('Unexpected user returned: ' . $u->fullname);
            }
        }
        $result = \enrol_programs\external\search_candidate::execute('Prijmeni', $program1->id);
        $this->assertSame(10, $result['maxusersperpage']);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(3, $result['list']); // Admin is NOT included.
        foreach ($result['list'] as $u) {
            if ($u->id == $user3->id) {
                $this->assertSame(fullname($user3, true), $u->fullname);
            } else if ($u->id == $user4->id) {
                $this->assertSame(fullname($user4, true), $u->fullname);
            } else if ($u->id == $user5->id) {
                $this->assertSame(fullname($user5, true), $u->fullname);
            } else {
                $this->fail('Unexpected user returned: ' . $u->fullname);
            }
        }

        $CFG->maxusersperpage = 2;
        $result = \enrol_programs\external\search_candidate::execute('', $program1->id);
        $this->assertSame(2, $result['maxusersperpage']);
        $this->assertSame(true, $result['overflow']);
        $this->assertCount(2, $result['list']);

        $this->setUser($user5);
        try {
            \enrol_programs\external\search_candidate::execute('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf('required_capability_exception', $ex);
        }

        $this->setUser($user5);
        $CFG->maxusersperpage = 10;
        $result = \enrol_programs\external\search_candidate::execute('', $program2->id);
        $this->assertSame(10, $result['maxusersperpage']);
        $this->assertSame(false, $result['overflow']);
        $this->assertCount(6, $result['list']);
    }

    public function test_execution_tenant() {
        global $DB, $CFG;
        require_once("$CFG->dirroot/lib/externallib.php");

        if (!\enrol_programs\local\tenant::is_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_olms_tenant\tenants::activate_tenants();

        /** @var \tool_olms_tenant_generator $generator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_olms_tenant');

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program(['fullname' => 'prg0', 'sources' => ['manual' => []]]);
        $source0 = $DB->get_record('enrol_programs_sources', ['programid' => $program0->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program1 = $generator->create_program(['idnumber' => 'prg2', 'contextid' => $tenant1catcontext->id, 'sources' => ['manual' => []]]);
        $source1 = $DB->get_record('enrol_programs_sources', ['programid' => $program1->id, 'type' => 'manual'], '*', MUST_EXIST);
        $program2 = $generator->create_program(['idnumber' => 'prg3', 'contextid' => $tenant2catcontext->id, 'sources' => ['manual' => []]]);
        $source2 = $DB->get_record('enrol_programs_sources', ['programid' => $program2->id, 'type' => 'manual'], '*', MUST_EXIST);

        $admin = get_admin();
        $user0 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 1', 'tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['lastname' => 'Prijmeni 2', 'tenantid' => $tenant2->id]);

        $admin = get_admin();
        $this->setUser($admin);

        $result = \enrol_programs\external\search_candidate::execute('', $program0->id);
        $this->assertEquals([$user0->id, $user1->id, $user2->id, $admin->id], array_keys($result['list']));

        $result = \enrol_programs\external\search_candidate::execute('', $program1->id);
        $this->assertEquals([$user0->id, $user1->id, $admin->id], array_keys($result['list']));

        $result = \enrol_programs\external\search_candidate::execute('', $program2->id);
        $this->assertEquals([$user0->id, $user2->id, $admin->id], array_keys($result['list']));

        \tool_olms_tenant\tenancy::force_tenant_id($tenant1->id);

        $result = \enrol_programs\external\search_candidate::execute('', $program0->id);
        $this->assertEquals([$user1->id], array_keys($result['list']));

        $result = \enrol_programs\external\search_candidate::execute('', $program1->id);
        $this->assertEquals([$user1->id], array_keys($result['list']));

        $result = \enrol_programs\external\search_candidate::execute('', $program2->id);
        $this->assertEquals([], array_keys($result['list']));
    }
}
