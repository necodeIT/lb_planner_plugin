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

use coding_exception;
use context_system;
use dml_exception;
use moodle_exception;
use stdClass;
use user_picture;
use core_user;

use local_lbplanner\enums\{CAPABILITY, CAPABILITY_FLAG};
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
     * Checks if the current user has access to the given user id.
     *
     * @param int $userid The id of the user to check access for.
     *
     * @return bool True if the current user has access to the given user id, false otherwise.
     */
    public static function check_access(int $userid): bool {
        global $USER;
        return ((int) $USER->id) === $userid;
    }

    /**
     * Checks if the current user has access to the given user id.
     * Throws an exception if the current user does not have access.
     *
     * @param int $userid The id of the user to check access for.
     *
     * @return void
     * @throws moodle_exception
     */
    public static function assert_access(int $userid): void {
        if (!self::check_access($userid)) {
            throw new moodle_exception('Access denied');
        }
    }

    /**
     * Checks if the given user is an admin.
     *
     * @param int $userid The id of the user to check.
     *
     * @return bool True if the given user is an admin, false otherwise.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function is_admin(int $userid): bool {
        $context = context_system::instance();
        return has_capability(CAPABILITY::ADMIN, $context, $userid, false);
    }

    /**
     * Gives back a bitmask which represents the capabilities of the given user.
     * 0 = no capabilities
     * 1 = admin
     * 2 = manager
     * 3 = admin + manager
     * 4 = teacher
     * 5 = admin + teacher
     * 6 = manager + teacher
     * 7 = admin + manager + teacher
     * 8 = student
     * 9 = admin + student
     * 10 = manager + student
     * 11 = admin + manager + student
     * 12 = teacher + student
     * 13 = admin + teacher + student
     * 14 = manager + teacher + student
     * 15 = admin + manager + teacher + student
     *
     *
     * @param int $userid The id of the user to check access for.
     *
     * @return int The capabilities of the given user.
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function get_user_capability_bitmask(int $userid): int {
        $capabilities = 0;
        $context = context_system::instance();
        if (has_capability(CAPABILITY::ADMIN, $context, $userid, false)) {
            $capabilities += CAPABILITY_FLAG::ADMIN;
        }
        if (has_capability(CAPABILITY::MANAGER, $context, $userid, false)) {
            $capabilities += CAPABILITY_FLAG::MANAGER;
        }
        if (has_capability(CAPABILITY::TEACHER, $context, $userid, false)) {
            $capabilities += CAPABILITY_FLAG::TEACHER;
        }
        if (has_capability(CAPABILITY::STUDENT, $context, $userid, false)) {
            $capabilities += CAPABILITY_FLAG::STUDENT;
        }
        return $capabilities;
    }

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
     *
     * @param int $userid The id of the user to retrieve.
     *
     * @return user The user with the given id.
     * @throws dml_exception
     */
    public static function get_user(int $userid): user {
        global $DB;
        return user::from_db($DB->get_record(self::LB_PLANNER_USER_TABLE, ['userid' => $userid], '*', MUST_EXIST));
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
