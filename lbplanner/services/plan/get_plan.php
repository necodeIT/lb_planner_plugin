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

use core_external\{external_function_parameters, external_single_structure};
use local_lbplanner\helpers\plan_helper;

/**
 * Returns the plan of the current user.
 *
 * @package local_lbplanner
 * @subpackage services_plan
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class plan_get_plan extends \core_external\external_api {
    /**
     * Parameters for get_plan.
     * @return external_function_parameters
     */
    public static function get_plan_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns the plan of the current user.
     *
     * @return array
     */
    public static function get_plan(): array {
        global $USER;

        $planid = plan_helper::get_plan_id($USER->id);

        return plan_helper::get_plan($planid);
    }

    /**
     * Returns the structure of the plan.
     * @return external_single_structure
     */
    public static function get_plan_returns(): external_single_structure {
        return plan_helper::plan_structure();
    }
}
