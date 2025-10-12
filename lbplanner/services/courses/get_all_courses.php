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

use core_external\{external_function_parameters, external_multiple_structure};
use local_lbplanner\enums\CAPABILITY_FLAG;
use local_lbplanner\helpers\course_helper;
use local_lbplanner\model\course;
use local_lbplanner\model\user;

/**
 * Returns ALL courses.
 *
 * @package local_lbplanner
 * @subpackage services_courses
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class courses_get_all_courses extends \core_external\external_api {
    /**
     * Has no Parameters
     * @return external_function_parameters
     */
    public static function get_all_courses_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns ALL courses.
     */
    public static function get_all_courses(): array {
        global $USER;
        $user = user::from_mdlobj($USER);
        if (!($user->get_capabilitybitmask() & (CAPABILITY_FLAG::SLOTMASTER | CAPABILITY_FLAG::TEACHER))) {
            throw new \moodle_exception('access denied: must be slotmaster');
        }

        $courses = course_helper::get_eduplanner_courses(false);
        $results = [];
        foreach ($courses as $course) {
            array_push($results, $course->prepare_for_api());
        }
        return $results;
    }

    /**
     * Returns description of method result value
     * @return external_multiple_structure description of method result value
     */
    public static function get_all_courses_returns(): external_multiple_structure {
        return new external_multiple_structure(
            course::api_structure()
        );
    }
}
