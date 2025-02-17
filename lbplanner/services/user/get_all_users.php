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

use dml_exception;
use core_external\{external_api, external_function_parameters, external_multiple_structure, external_value};
use invalid_parameter_exception;
use local_lbplanner\enums\CAPABILITY;
use moodle_exception;
use core\context\system as context_system;

use local_lbplanner\helpers\user_helper;
use local_lbplanner\model\user;

/**
 * Gets all users with one or several LBP capabilities.
 *
 * @package local_lbplanner
 * @subpackage services_user
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class user_get_all_users extends external_api {
    /**
     * Parameters for get_all_users
     * @return external_function_parameters
     */
    public static function get_all_users_parameters(): external_function_parameters {
        return new external_function_parameters([
            'vintage' => new external_value(PARAM_TEXT, 'The vintage to filter the users by', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Gives back all users.
     * @param ?string $vintage (optional) gives back all users with the given vintage
     * @throws moodle_exception
     * @throws dml_exception
     * @throws invalid_parameter_exception
     */
    public static function get_all_users(?string $vintage): array {
        global $DB;

        self::validate_parameters(self::get_all_users_parameters(), ['vintage' => $vintage]);

        $syscontext = context_system::instance();

        $alluserids = $DB->get_fieldset(user_helper::MOODLE_USER_TABLE, 'id');

        $results = [];

        foreach ($alluserids as $userid) {
            $user = null;
            foreach (CAPABILITY::cases() as $case) {
                if (has_capability($case->value, $syscontext, $userid)) {
                    $user = user_helper::get_user($userid);
                    break;
                }
            }
            if ($user === null) {
                continue;
            }
            if ($vintage === null || $vintage == $user->get_mdluser()->vintage) {
                array_push($results, $user->prepare_for_api_short());
            }
        }

        return $results;
    }

    /**
     * Returns the structure of the data returned by the get_all_users function
     * @return external_multiple_structure
     */
    public static function get_all_users_returns(): external_multiple_structure {
        return new external_multiple_structure(
            user::api_structure_short()
        );
    }
}
