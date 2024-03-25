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

namespace enrol_programs\external;

/**
 * External API for adding of training to program.
 *
 * @group      openlms
 * @package    enrol_programs
 * @copyright  2024 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @runTestsInSeparateProcesses
 * @covers \enrol_programs\external\form_item_append_frameworkid
 */
final class form_item_append_frameworkid_test extends \advanced_testcase {
    public function setUp(): void {
        global $CFG;
        require_once("$CFG->dirroot/lib/externallib.php");
        $this->resetAfterTest();
    }

    public function test_execute() {
        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $category1 = $this->getDataGenerator()->create_category([]);
        $catcontext1 = \context_coursecat::instance($category1->id);

        /** @var \customfield_training_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $fielcategory = $this->getDataGenerator()->create_custom_field_category(
            ['component' => 'core_course', 'area' => 'course']);
        $field1 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field1']);
        $field2 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field2']);
        $field3 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'training', 'shortname' => 'field3']);
        $field4 = $this->getDataGenerator()->create_custom_field(
            ['categoryid' => $fielcategory->get('id'), 'type' => 'text', 'shortname' => 'field4']);

        $data = (object)[
            'name' => 'Some framework',
            'public' => 1,
            'fields' => [$field1->get('id')],
        ];
        $framework1 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Other framework',
            'public' => 1,
            'idnumber' => 'ofr2',
            'fields' => [$field2->get('id')],
        ];
        $framework2 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Another framework',
            'contextid' => $catcontext1->id,
            'public' => 0,
            'fields' => [],
        ];
        $framework3 = $traininggenerator->create_framework($data);
        $data = (object)[
            'name' => 'Grrr framework',
            'public' => 1,
            'archived' => 1,
            'fields' => [],
        ];
        $framework4 = $traininggenerator->create_framework($data);

        $program1 = $generator->create_program([
            'contextid' => $syscontext->id,
        ]);
        $program2 = $generator->create_program([
            'contextid' => $catcontext1->id,
        ]);

        $user1 = $this->getDataGenerator()->create_user();
        $user2 = $this->getDataGenerator()->create_user();
        $user3 = $this->getDataGenerator()->create_user();
        $user4 = $this->getDataGenerator()->create_user();

        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('enrol/programs:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user1->id, $syscontext->id);
        role_assign($editorroleid, $user2->id, $syscontext->id);
        role_assign($editorroleid, $user3->id, $catcontext1->id);

        $fviewerroleid = $this->getDataGenerator()->create_role();
        assign_capability('customfield/training:viewframeworks', CAP_ALLOW, $fviewerroleid, $syscontext);
        role_assign($fviewerroleid, $user1->id, $syscontext->id);

        $this->setUser($user1);
        $response = form_item_append_frameworkid::execute ('', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework3->id, 'label' => $framework3->name],
            ['value' => $framework2->id, 'label' => $framework2->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('framework', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework3->id, 'label' => $framework3->name],
            ['value' => $framework2->id, 'label' => $framework2->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('Another', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework3->id, 'label' => $framework3->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('fr2', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('xxx', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user2);
        $response = form_item_append_frameworkid::execute ('', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework2->id, 'label' => $framework2->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user3);
        $response = form_item_append_frameworkid::execute ('', $program2->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework2->id, 'label' => $framework2->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user3);
        try {
            form_item_append_frameworkid::execute ('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }

        $this->setUser($user4);
        try {
            form_item_append_frameworkid::execute ('', $program1->id);
            $this->fail('Exception expected');
        } catch (\moodle_exception $ex) {
            $this->assertInstanceOf(\required_capability_exception::class, $ex);
        }
    }

    public function test_execute_tenant() {
        if (!\enrol_programs\local\tenant::is_available()) {
            $this->markTestSkipped('tenant support not available');
        }

        \tool_olms_tenant\tenants::activate_tenants();

        /** @var \tool_olms_tenant_generator $generator */
        $tenantgenerator = $this->getDataGenerator()->get_plugin_generator('tool_olms_tenant');

        /** @var \enrol_programs_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('enrol_programs');

        $syscontext = \context_system::instance();
        $tenant1 = $tenantgenerator->create_tenant();
        $tenant1context = \context_tenant::instance($tenant1->id);
        $tenant1catcontext = \context_coursecat::instance($tenant1->categoryid);
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant2context = \context_tenant::instance($tenant2->id);
        $tenant2catcontext = \context_coursecat::instance($tenant2->categoryid);

        $program0 = $generator->create_program([]);
        $program1 = $generator->create_program(['contextid' => $tenant1catcontext->id]);
        $program2 = $generator->create_program(['contextid' => $tenant2catcontext->id]);

        $user0 = $this->getDataGenerator()->create_user(['tenantid' => 0]);
        $user1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        /** @var \customfield_training_generator $traininggenerator */
        $traininggenerator = $this->getDataGenerator()->get_plugin_generator('customfield_training');

        $data = (object)[
            'name' => 'Framework 0',
            'contextid' => $syscontext->id,
            'public' => 1,
        ];
        $framework0 = $traininggenerator->create_framework($data);

        $data = (object)[
            'name' => 'Framework 1',
            'contextid' => $tenant1catcontext->id,
            'public' => 1,
        ];
        $framework1 = $traininggenerator->create_framework($data);

        $data = (object)[
            'name' => 'Framework 2',
            'contextid' => $tenant2catcontext->id,
            'public' => 1,
        ];
        $framework2 = $traininggenerator->create_framework($data);

        $syscontext = \context_system::instance();
        $editorroleid = $this->getDataGenerator()->create_role();
        assign_capability('enrol/programs:edit', CAP_ALLOW, $editorroleid, $syscontext);
        role_assign($editorroleid, $user0->id, $syscontext->id);
        role_assign($editorroleid, $user1->id, $tenant1catcontext->id);
        role_assign($editorroleid, $user2->id, $tenant2catcontext->id);

        $this->setUser($user0);
        $response = form_item_append_frameworkid::execute ('', $program0->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework0->id, 'label' => $framework0->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
            ['value' => $framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('', $program1->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework0->id, 'label' => $framework0->name],
            ['value' => $framework1->id, 'label' => $framework1->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $response = form_item_append_frameworkid::execute ('', $program2->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework0->id, 'label' => $framework0->name],
            ['value' => $framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);

        $this->setUser($user2);
        $response = form_item_append_frameworkid::execute ('', $program2->id);
        $results = form_item_append_frameworkid::clean_returnvalue(form_program_allocation_import_fromprogram::execute_returns(), $response);
        $this->assertSame(null, $results['notice']);
        $expectedlist = [
            ['value' => $framework0->id, 'label' => $framework0->name],
            ['value' => $framework2->id, 'label' => $framework2->name],
        ];
        $this->assertSame($expectedlist, $results['list']);
    }
}
