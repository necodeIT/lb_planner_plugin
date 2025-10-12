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

use core_external\{external_api, external_function_parameters, external_single_structure, external_value};
use local_lbplanner\model\module;

/**
 * Returns the data for a module.
 *
 * @package local_lbplanner
 * @subpackage services_modules
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class modules_get_module extends external_api {
    /**
     * Parameters for get_module.
     * @return external_function_parameters
     */
    public static function get_module_parameters(): external_function_parameters {
        return new external_function_parameters([
            'assignid' => new external_value(PARAM_INT, 'The assignment ID of the module', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    /**
     * Returns the data for a module
     *
     * @param int $assignid The assignment ID of the module
     * @return array the module
     */
    public static function get_module(int $assignid): array {
        global $USER;

        self::validate_parameters(self::get_module_parameters(), ['moduleid' => $assignid]);

        return module::from_assignid($assignid)->prepare_for_api_personal($USER->id);
    }

    /**
     * Returns the structure of the module.
     * @return external_single_structure
     */
    public static function get_module_returns(): external_single_structure {
        return module::api_structure_personal();
    }
}
