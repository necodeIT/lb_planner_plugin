<?php
// This file is part of the local_lbplanner.
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
 * contains all service endpoints
 *
 * @package local_lbplanner
 * @subpackage db
 * @copyright 2024 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_lbplanner_user_get_user' => [
        'classname' => 'local_lbplanner_services\user_get_user',
        'methodname' => 'get_user',
        'classpath' => 'local/lbplanner/services/user/get_user.php',
        'description' => 'Get the data for a user',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_user_get_all_users' => [
        'classname' => 'local_lbplanner_services\user_get_all_users',
        'methodname' => 'get_all_users',
        'classpath' => 'local/lbplanner/services/user/get_all_users.php',
        'description' => 'Gets all users registered by the lbplanner app',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_user_update_user' => [
        'classname' => 'local_lbplanner_services\user_update_user',
        'methodname' => 'update_user',
        'classpath' => 'local/lbplanner/services/user/update_user.php',
        'description' => 'Update the data for a user',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_courses_get_all_courses' => [
        'classname' => 'local_lbplanner_services\courses_get_all_courses',
        'methodname' => 'get_all_courses',
        'classpath' => 'local/lbplanner/services/courses/get_all_courses.php',
        'description' => 'Get all courses',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_courses_get_my_courses' => [
        'classname' => 'local_lbplanner_services\courses_get_my_courses',
        'methodname' => 'get_my_courses',
        'classpath' => 'local/lbplanner/services/courses/get_my_courses.php',
        'description' => 'Get courses that belong to the user',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_courses_update_course' => [
        'classname' => 'local_lbplanner_services\courses_update_course',
        'methodname' => 'update_course',
        'classpath' => 'local/lbplanner/services/courses/update_course.php',
        'description' => 'Update the data for a course',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_modules_get_module' => [
        'classname' => 'local_lbplanner_services\modules_get_module',
        'methodname' => 'get_module',
        'classpath' => 'local/lbplanner/services/modules/get_module.php',
        'description' => 'Get the data for a module',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_modules_get_all_modules' => [
        'classname' => 'local_lbplanner_services\modules_get_all_modules',
        'methodname' => 'get_all_modules',
        'classpath' => 'local/lbplanner/services/modules/get_all_modules.php',
        'description' => 'Get all the modules of the current year',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_modules_get_all_course_modules' => [
        'classname' => 'local_lbplanner_services\modules_get_all_course_modules',
        'methodname' => 'get_all_course_modules',
        'classpath' => 'local/lbplanner/services/modules/get_all_course_modules.php',
        'description' => 'Get all the modules of the given course',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_clear_plan' => [
        'classname' => 'local_lbplanner_services\plan_clear_plan',
        'methodname' => 'clear_plan',
        'classpath' => 'local/lbplanner/services/plan/clear_plan.php',
        'description' => 'Clear the plan for the given user',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_get_plan' => [
        'classname' => 'local_lbplanner_services\plan_get_plan',
        'methodname' => 'get_plan',
        'classpath' => 'local/lbplanner/services/plan/get_plan.php',
        'description' => 'Get the plan of the given user',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_invite_user' => [
        'classname' => 'local_lbplanner_services\plan_invite_user',
        'methodname' => 'invite_user',
        'classpath' => 'local/lbplanner/services/plan/invite_user.php',
        'description' => 'Invite a user to the plan',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_remove_user' => [
        'classname' => 'local_lbplanner_services\plan_remove_user',
        'methodname' => 'remove_user',
        'classpath' => 'local/lbplanner/services/plan/remove_user.php',
        'description' => 'Remove a user from the plan',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_update_plan' => [
        'classname' => 'local_lbplanner_services\plan_update_plan',
        'methodname' => 'update_plan',
        'classpath' => 'local/lbplanner/services/plan/update_plan.php',
        'description' => 'Update the plan of the given user',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_leave_plan' => [
        'classname' => 'local_lbplanner_services\plan_leave_plan',
        'methodname' => 'leave_plan',
        'classpath' => 'local/lbplanner/services/plan/leave_plan.php',
        'description' => 'Leave the plan of the given user',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_delete_deadline' => [
        'classname' => 'local_lbplanner_services\plan_delete_deadline',
        'methodname' => 'delete_deadline',
        'classpath' => 'local/lbplanner/services/plan/delete_deadline.php',
        'description' => 'Delete a deadline from the plan',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_user_delete_user' => [
        'classname' => 'local_lbplanner_services\user_delete_user',
        'methodname' => 'delete_user',
        'classpath' => 'local/lbplanner/services/user/delete_user.php',
        'description' => 'Removes all user data stored by the lbplanner app',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_set_deadline' => [
        'classname' => 'local_lbplanner_services\plan_set_deadline',
        'methodname' => 'set_deadline',
        'classpath' => 'local/lbplanner/services/plan/set_deadline.php',
        'description' => 'Set a deadline from the plan',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_update_access' => [
        'classname' => 'local_lbplanner_services\plan_update_access',
        'methodname' => 'update_access',
        'classpath' => 'local/lbplanner/services/plan/update_access.php',
        'description' => 'Update the access of the plan',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_get_invites' => [
        'classname' => 'local_lbplanner_services\plan_get_invites',
        'methodname' => 'get_invites',
        'classpath' => 'local/lbplanner/services/plan/get_invites.php',
        'description' => 'Get all the invites of the given user',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_notifications_get_all_notifications' => [
        'classname' => 'local_lbplanner_services\notifications_get_all_notifications',
        'methodname' => 'get_all_notifications',
        'classpath' => 'local/lbplanner/services/notifications/get_all_notifications.php',
        'description' => 'Get all the notifications of the given user',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_notifications_update_notification' => [
        'classname' => 'local_lbplanner_services\notifications_update_notification',
        'methodname' => 'update_notification',
        'classpath' => 'local/lbplanner/services/notifications/update_notification.php',
        'description' => 'Update the notification status of the given user and id',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_accept_invite' => [
        'classname' => 'local_lbplanner_services\plan_accept_invite',
        'methodname' => 'accept_invite',
        'classpath' => 'local/lbplanner/services/plan/accept_invite.php',
        'description' => 'Accept the invite of the given id',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_plan_decline_invite' => [
        'classname' => 'local_lbplanner_services\plan_decline_invite',
        'methodname' => 'decline_invite',
        'classpath' => 'local/lbplanner/services/plan/decline_invite.php',
        'description' => 'Decline the invite of the given id',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_config_get_version' => [
        'classname' => 'local_lbplanner_services\config_get_version',
        'methodname' => 'get_version',
        'classpath' => 'local/lbplanner/services/config/get_version.php',
        'description' => 'Get the version of the plugin',
        'type' => 'read',
        'capabilities' => '',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_my_slots' => [
        'classname' => 'local_lbplanner_services\slots_get_my_slots',
        'methodname' => 'get_my_slots',
        'classpath' => 'local/lbplanner/services/slots/get_my_slots.php',
        'description' => 'Get all slots the user can theoretically reserve.',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_student_slots' => [
        'classname' => 'local_lbplanner_services\slots_get_student_slots',
        'methodname' => 'get_student_slots',
        'classpath' => 'local/lbplanner/services/slots/get_student_slots.php',
        'description' => 'Get all slots a supervisor can theoretically reserve for a student.',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:teacher',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_supervisor_slots' => [
        'classname' => 'local_lbplanner_services\slots_get_supervisor_slots',
        'methodname' => 'get_supervisor_slots',
        'classpath' => 'local/lbplanner/services/slots/get_supervisor_slots.php',
        'description' => 'Get all slots belonging to the supervisor.',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:teacher',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_all_slots' => [
        'classname' => 'local_lbplanner_services\slots_get_all_slots',
        'methodname' => 'get_all_slots',
        'classpath' => 'local/lbplanner/services/slots/get_all_slots.php',
        'description' => 'Get all slots.',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_book_reservation' => [
        'classname' => 'local_lbplanner_services\slots_book_reservation',
        'methodname' => 'book_reservation',
        'classpath' => 'local/lbplanner/services/slots/book_reservation.php',
        'description' => 'Book a reservation',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_slots_unbook_reservation' => [
        'classname' => 'local_lbplanner_services\slots_unbook_reservation',
        'methodname' => 'unbook_reservation',
        'classpath' => 'local/lbplanner/services/slots/unbook_reservation.php',
        'description' => 'Unbook a reservation',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_slot_reservations' => [
        'classname' => 'local_lbplanner_services\slots_get_slot_reservations',
        'methodname' => 'get_slot_reservations',
        'classpath' => 'local/lbplanner/services/slots/get_slot_reservations.php',
        'description' => 'Get reservations for a slot',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:teacher',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_my_reservations' => [
        'classname' => 'local_lbplanner_services\slots_get_my_reservations',
        'methodname' => 'get_my_reservations',
        'classpath' => 'local/lbplanner/services/slots/get_my_reservations.php',
        'description' => 'Get reservations for this user',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:student',
        'ajax' => true,
    ],
    'local_lbplanner_slots_create_slot' => [
        'classname' => 'local_lbplanner_services\slots_create_slot',
        'methodname' => 'create_slot',
        'classpath' => 'local/lbplanner/services/slots/create_slot.php',
        'description' => 'Create a slot',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_update_slot' => [
        'classname' => 'local_lbplanner_services\slots_update_slot',
        'methodname' => 'update_slot',
        'classpath' => 'local/lbplanner/services/slots/update_slot.php',
        'description' => 'Update a slot\'s values',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_delete_slot' => [
        'classname' => 'local_lbplanner_services\slots_delete_slot',
        'methodname' => 'delete_slot',
        'classpath' => 'local/lbplanner/services/slots/delete_slot.php',
        'description' => 'Delete a slot',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_add_slot_supervisor' => [
        'classname' => 'local_lbplanner_services\slots_add_slot_supervisor',
        'methodname' => 'add_slot_supervisor',
        'classpath' => 'local/lbplanner/services/slots/add_slot_supervisor.php',
        'description' => 'Add supervisor to a slot',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_remove_slot_supervisor' => [
        'classname' => 'local_lbplanner_services\slots_remove_slot_supervisor',
        'methodname' => 'remove_slot_supervisor',
        'classpath' => 'local/lbplanner/services/slots/remove_slot_supervisor.php',
        'description' => 'Removes supervisor from a slot',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_add_slot_filter' => [
        'classname' => 'local_lbplanner_services\slots_add_slot_filter',
        'methodname' => 'add_slot_filter',
        'classpath' => 'local/lbplanner/services/slots/add_slot_filter.php',
        'description' => 'Add a slot filter',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_delete_slot_filter' => [
        'classname' => 'local_lbplanner_services\slots_delete_slot_filter',
        'methodname' => 'delete_slot_filter',
        'classpath' => 'local/lbplanner/services/slots/delete_slot_filter.php',
        'description' => 'Delete a slot filter',
        'type' => 'write',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
    'local_lbplanner_slots_get_slot_filters' => [
        'classname' => 'local_lbplanner_services\slots_get_slot_filters',
        'methodname' => 'get_slot_filters',
        'classpath' => 'local/lbplanner/services/slots/get_slot_filters.php',
        'description' => 'Returns all filters associated with a slot',
        'type' => 'read',
        'capabilities' => 'local/lb_planner:slotmaster',
        'ajax' => true,
    ],
];

$services = [
    'LB Planer API' => [
        'functions' => [
            'local_lbplanner_user_get_user',
            'local_lbplanner_user_get_all_users',
            'local_lbplanner_user_update_user',
            'local_lbplanner_courses_get_all_courses',
            'local_lbplanner_courses_get_my_courses',
            'local_lbplanner_courses_update_course',
            'local_lbplanner_modules_get_all_course_modules',
            'local_lbplanner_modules_get_all_modules',
            'local_lbplanner_modules_get_module',
            'local_lbplanner_plan_clear_plan',
            'local_lbplanner_plan_delete_deadline',
            'local_lbplanner_plan_get_plan',
            'local_lbplanner_plan_invite_user',
            'local_lbplanner_plan_get_invites',
            'local_lbplanner_plan_leave_plan',
            'local_lbplanner_plan_remove_user',
            'local_lbplanner_plan_set_deadline',
            'local_lbplanner_plan_update_plan',
            'local_lbplanner_notifications_get_all_notifications',
            'local_lbplanner_notifications_update_notification',
            'local_lbplanner_plan_get_access',
            'local_lbplanner_plan_update_access',
            'local_lbplanner_user_delete_user',
            'local_lbplanner_plan_accept_invite',
            'local_lbplanner_plan_decline_invite',
            'local_lbplanner_config_get_version',
            'local_lbplanner_slots_add_slot_filter',
            'local_lbplanner_slots_add_slot_supervisor',
            'local_lbplanner_slots_book_reservation',
            'local_lbplanner_slots_create_slot',
            'local_lbplanner_slots_delete_slot',
            'local_lbplanner_slots_delete_slot_filter',
            'local_lbplanner_slots_get_all_slots',
            'local_lbplanner_slots_get_my_slots',
            'local_lbplanner_slots_get_slot_filters',
            'local_lbplanner_slots_get_slot_reservations',
            'local_lbplanner_slots_get_student_slots',
            'local_lbplanner_slots_get_supervisor_slots',
            'local_lbplanner_slots_remove_slot_supervisor',
            'local_lbplanner_slots_update_slot',
        ],
        'restrictedusers' => 0,
        'enabled' => 1,
        'shortname' => 'lb_planner_api',
    ],
];
