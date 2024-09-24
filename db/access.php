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
 * Program enrolment plugin capabilities.
 *
 * @package    enrol_programs
 * @copyright  2022 Open LMS (https://www.openlms.net/)
 * @author     Petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$capabilities = [
    /* Access program catalogue - catalogue uses program.public, visible cohorts and own allocations. */
    'enrol/programs:viewcatalogue' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW,
        ],
    ],

    /* Access the program management UI - needed for program management capabilities
       this allows sidestepping of regular program visibility rules */
    'enrol/programs:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Add and update programs. */
    'enrol/programs:edit' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Delete programs. */
    'enrol/programs:delete' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Add program to plans. */
    'enrol/programs:addtoplan' => [
        'captype' => 'read', // This does not allow to change any data by itself.
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Add program to certifications. */
    'enrol/programs:addtocertifications' => [
        'captype' => 'read', // This does not allow to change any data by itself.
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Configure allowed frameworks for adding of programs to plans. */
    'enrol/programs:configframeworks' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Add course to program. This is used to find courses that user can add to programs */
    'enrol/programs:addcourse' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /* Allocate programs to users manually, used only when manual source enabled in program. */
    'enrol/programs:allocate' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /*
     * Archive and restore allocations if source allows it.
     */
    'enrol/programs:archive' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' =>  'enrol/programs:allocate'
    ],

    /*
     * Alter certification dates or
     * delete allocations if source allows it.
     */
    'enrol/programs:manageallocation' => [
        'riskbitmask' => RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],

        'clonepermissionsfrom' =>  'enrol/programs:allocate'
    ],

    /* Add, update and delete other evidence of completion */
    'enrol/programs:manageevidence' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
            'tenantmanager' => CAP_ALLOW,
        ],
    ],

    /*
     * Reset program progress manually.
     */
    'enrol/programs:reset' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
        ],

        'clonepermissionsfrom' =>  'enrol/programs:admin'
    ],

    /*
     * All other advanced functionality not intended for regular managers,
     * such as overriding of item and program completion dates.
     */
    'enrol/programs:admin' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
        ],
    ],

    /* To copy over content, allocation and notification settings to other programs */
    'enrol/programs:clone' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSECAT,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'enrol/programs:configurecustomfields' => [
        'riskbitmask' => RISK_CONFIG | RISK_DATALOSS,
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

];

// Compatibility hacks for vanilla Moodle.
if (!file_exists(__DIR__ . '/../../../admin/tool/olms_tenant/version.php')) {
    foreach ($capabilities as $k => $unused) {
        unset($capabilities[$k]['archetypes']['tenantmanager']);
    }
}
