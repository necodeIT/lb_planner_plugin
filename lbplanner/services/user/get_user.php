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

namespace local_lbplanner_services;

use coding_exception;
use dml_exception;
use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use core_user;
use moodle_exception;

use local_lbplanner\helpers\{user_helper, plan_helper, notifications_helper};
use local_lbplanner\enums\{PLAN_EK, PLAN_ACCESS_TYPE, NOTIF_TRIGGER};
use local_lbplanner\model\user;

/**
 * Get the data for a user.
 *
 * Get the data for a user. param userid (optional) gives back the user data with the given ID
 *
 * @package local_lbplanner
 * @subpackage services_user
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class user_get_user extends external_api {
    /**
     * Parameters for get_user
     * @return external_function_parameters
     */
    public static function get_user_parameters(): external_function_parameters {
        global $USER;
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'The id of the user to get the data for. If not provided it will be inferred via the token',
                VALUE_DEFAULT,
                $USER->id,
                NULL_NOT_ALLOWED,
            ),
        ]);
    }

    /**
     * Gives back the data of a user.
     * Default: The user who calls this function
     * @param int $userid gives back the data of the given user
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @return array The data of the user
     */
    public static function get_user(int $userid): array {
        global $USER, $DB;

        self::validate_parameters(self::get_user_parameters(), ['userid' => $userid]);

        // Check if the user is allowed to get the data for this userid.
        user_helper::assert_access($userid);

        // Checks if the user is enrolled in LB Planner.
        if (!user_helper::check_user_exists($userid)) {
            // Register user if not found.
            $lbplanneruser = new user(0, $userid, 'default', 'en', 'none', 1);
            $lbpid = $DB->insert_record(user_helper::LB_PLANNER_USER_TABLE, $lbplanneruser->prepare_for_db());
            $lbplanneruser->set_fresh($lbpid);

            // Create empty plan for newly registered user.
            $plan = new \stdClass();
            $plan->name = 'Plan for ' . $USER->username;
            $plan->enableek = PLAN_EK::ENABLED;
            $planid = $DB->insert_record(plan_helper::TABLE, $plan);
            $lbplanneruser->set_planid($planid);

            // Set user as owner of new plan.
            $planaccess = new \stdClass();
            $planaccess->userid = $userid;
            $planaccess->accesstype = PLAN_ACCESS_TYPE::OWNER;
            $planaccess->planid = $planid;
            $DB->insert_record(plan_helper::ACCESS_TABLE, $planaccess);

            // Notify the FE that this user likely hasn't used LBP before.
            notifications_helper::notify_user($userid, -1, NOTIF_TRIGGER::USER_REGISTERED);
        } else {
            $lbplanneruser = user_helper::get_user($userid);
        }

        return $lbplanneruser->prepare_for_api();
    }

    /**
     * Returns the data of a user.
     * @return external_single_structure
     */
    public static function get_user_returns(): external_single_structure {
        return user::api_structure();
    }
}
