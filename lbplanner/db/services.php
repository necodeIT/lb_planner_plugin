<?php
// This file is part of local_lbplanner.
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
 * External service definitions for the EduPlanner sync contract.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_lbplanner_sync_get_identity' => [
        'classname' => 'local_lbplanner_services\sync_get_identity',
        'methodname' => 'get_identity',
        'classpath' => 'local/lbplanner/services/sync/get_identity.php',
        'description' => 'Return sync identity for the current token owner',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:sync',
        'ajax' => false,
    ],
    'local_lbplanner_sync_get_courses' => [
        'classname' => 'local_lbplanner_services\sync_get_courses',
        'methodname' => 'get_courses',
        'classpath' => 'local/lbplanner/services/sync/get_courses.php',
        'description' => 'Return the current token owner\'s courses and group memberships',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:sync',
        'ajax' => false,
    ],
    'local_lbplanner_sync_get_assignments' => [
        'classname' => 'local_lbplanner_services\sync_get_assignments',
        'methodname' => 'get_assignments',
        'classpath' => 'local/lbplanner/services/sync/get_assignments.php',
        'description' => 'Return the current token owner\'s visible assignments',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:sync',
        'ajax' => false,
    ],
    'local_lbplanner_sync_get_quizzes' => [
        'classname' => 'local_lbplanner_services\sync_get_quizzes',
        'methodname' => 'get_quizzes',
        'classpath' => 'local/lbplanner/services/sync/get_quizzes.php',
        'description' => 'Return the current token owner\'s visible quizzes',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:sync',
        'ajax' => false,
    ],
];

$services = [
    'LB Planner Sync API' => [
        'functions' => [
            'local_lbplanner_sync_get_identity',
            'local_lbplanner_sync_get_courses',
            'local_lbplanner_sync_get_assignments',
            'local_lbplanner_sync_get_quizzes',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'requiredcapability' => 'local/lb_planner:sync',
        'shortname' => 'lb_planner_sync_api',
    ],
];
