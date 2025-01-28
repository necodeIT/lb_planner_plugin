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

namespace local_lbplanner\helpers;

use dml_exception;
use stdClass;
use core_user;

use local_lbplanner\enums\NOTIF_TRIGGER;
use local_lbplanner\enums\PLAN_ACCESS_TYPE;
use local_lbplanner\model\user;

/**
 * Provides helper methods for user related stuff.
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2024 NecodeIT
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class user_helper {

    /**
     * Name of the user database
     */
    const LB_PLANNER_USER_TABLE = 'local_lbplanner_users';

    /**
     * Checks if the given user exists in the LB_PLANNER_USER database.
     *
     * @param int $userid The id of the user to check.
     *
     * @return bool True if the user exists, false otherwise.
     * @throws dml_exception
     */
    public static function check_user_exists(int $userid): bool {
        global $DB;
        return $DB->record_exists(self::LB_PLANNER_USER_TABLE, ['userid' => $userid]);
    }

    /**
     * Retrieves the user with the given id.
     * If user doesn't exist in the DB yet, it will be created.
     *
     * @param int $userid The id of the user to retrieve.
     *
     * @return user The user with the given id.
     * @throws dml_exception
     */
    public static function get_user(int $userid): user {
        global $DB;
        $dbuser = $DB->get_record(self::LB_PLANNER_USER_TABLE, ['userid' => $userid]);

        if ($dbuser !== false) {
            return user::from_db($dbuser);
        }

        // Register user if not found.
        $lbplanneruser = new user(0, $userid, 'default', 'none', 1, false);
        $lbpid = $DB->insert_record(user_helper::LB_PLANNER_USER_TABLE, $lbplanneruser->prepare_for_db());
        $lbplanneruser->set_fresh($lbpid);

        // Create empty plan for newly registered user.
        $plan = new \stdClass();
        $plan->name = 'Plan for ' . ($lbplanneruser->get_mdluser()->username);
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

        return $lbplanneruser;
    }

    /**
     * Retrieves the user with the given id.
     *
     * @param int $userid The id of the user to retrieve.
     *
     * @return \stdClass The user with the given id.
     * @throws dml_exception
     */
    public static function get_mdluser(int $userid): \stdClass {
        global $USER;
        if ($userid === ((int) $USER->id)) {
            $data = $USER;
        } else {
            $data = core_user::get_user($userid, '*', MUST_EXIST);
        }
        return $data;
    }
}
