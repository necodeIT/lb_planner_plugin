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

use external_api;
use external_function_parameters;
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot;

/**
 * Returns all slots the user is supposed to see.
 *
 * @package local_lbplanner
 * @subpackage services_plan
 * @copyright 2024 necodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_slots extends external_api {
    /**
     * Parameters for get_my_slots.
     * @return external_function_parameters
     */
    public static function get_my_slots_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns slots the current user is supposed to see
     */
    public static function get_my_slots() {
        global $USER;
        // NOTE: could be better solved by applying filters within one complex SQL query.
        // Oh well.

        $all_slots = slot_helper::get_all_slots();

        $my_courses = self::call_external_function('local_lbplanner_courses_get_all_courses', ['userid' => $USER->id]);
        $my_courseids = [];
        foreach($my_courses as $course){
            array_push($my_courseids, $course->courseid);
        }

        $my_slots = [];
        foreach($all_slots as $slot){
            $filters = slot_helper::get_filters_for_slot($slot->id);
            foreach($filters as $filter) {
                // Checking for course ID.
                if(!is_null($filter->courseid) and !in_array($filter->courseid, $my_courseids)) {
                    continue;
                }
                // Checking for vintage.
                if(!is_null($filter->vintage) and $USER->address !== $filter->vintage) {
                    continue;
                }
                // If all filters passed, add slot to my slots and break.
                array_push($my_slots, $slot);
                break;
            }
        }

        // TODO: check for time, weekday, etc.

        $returnslots = [];
        foreach($my_slots as $slot){
            array_push($returnslots, $slot->prepare_for_api());
        }

        return $returnslots;
    }

    /**
     * Returns the structure of the slot array
     * @return external_single_structure
     */
    public static function get_my_slots_returns() {
        return slot::api_structure();
    }
}
