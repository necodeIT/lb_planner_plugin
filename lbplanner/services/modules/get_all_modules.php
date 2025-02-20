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

use core_external\{external_api, external_function_parameters, external_multiple_structure, external_value};
use local_lbplanner\helpers\{modules_helper, plan_helper, course_helper};
use local_lbplanner\model\module;

/**
 * Get all the modules of the current year.
 *
 * @package local_lbplanner
 * @subpackage services_modules
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class modules_get_all_modules extends external_api {
    /**
     * Parameters for get_all_modules.
     * @return external_function_parameters
     */
    public static function get_all_modules_parameters(): external_function_parameters {
        return new external_function_parameters([
            'ekenabled' => new external_value(
                PARAM_BOOL,
                'Whether to include ek modules',
                VALUE_DEFAULT,
                false,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Returns all the modules for a user.
     * @param bool $ekenabled Whether to include ek modules
     * @return array the modules
     */
    public static function get_all_modules(bool $ekenabled): array {
        global $USER;
        self::validate_parameters(
            self::get_all_modules_parameters(),
            ['ekenabled' => $ekenabled]
        );

        $modules = [];

        $courses = course_helper::get_all_eduplanner_courses();
        $planid = plan_helper::get_plan_id($USER->id);

        foreach ($courses as $course) {
            if (!$course->enabled) {
                continue;
            }
            $modules = array_merge(
                modules_helper::get_all_modules_by_course($course->courseid, $ekenabled),
                $modules
            );
        }
        return array_map(fn(module $m) => $m->prepare_for_api_personal($USER->id, $planid), $modules);
    }

    /**
     * Returns the structure of the module array.
     * @return external_multiple_structure
     */
    public static function get_all_modules_returns(): external_multiple_structure {
        return new external_multiple_structure(
            module::api_structure_personal(),
        );
    }
}
