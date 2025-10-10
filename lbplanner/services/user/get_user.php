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
use core_external\{external_function_parameters, external_single_structure};
use moodle_exception;

use local_lbplanner\helpers\user_helper;
use local_lbplanner\model\user;

/**
 * Returns current userdata.
 *
 * @package local_lbplanner
 * @subpackage services_user
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class user_get_user extends \core_external\external_api {
    /**
     * Parameters for get_user
     * @return external_function_parameters
     */
    public static function get_user_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns current userdata.
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     * @return array The data of the user
     */
    public static function get_user(): array {
        global $USER;

        return user_helper::get_user($USER->id)->prepare_for_api();
    }

    /**
     * Returns the data of a user.
     * @return external_single_structure
     */
    public static function get_user_returns(): external_single_structure {
        return user::api_structure();
    }
}
