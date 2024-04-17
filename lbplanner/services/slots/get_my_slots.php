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

use DateInterval;
use DateTime;
use DateTimeImmutable;
use external_api;
use external_function_parameters;
use external_single_structure;
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot;
use local_lbplanner\enums\WEEKDAY;

/**
 * Returns all slots the user can theoretically reserve.
 * This does not include times the user has already reserved a slot for.
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

        $allslots = slot_helper::get_all_slots();

        $mycourses = self::call_external_function('local_lbplanner_courses_get_all_courses', ['userid' => $USER->id]);
        $mycourseids = [];
        foreach ($mycourses as $course) {
            array_push($mycourseids, $course->courseid);
        }

        $mySlots = [];
        foreach ($allslots as $slot) {
            $filters = slot_helper::get_filters_for_slot($slot->id);
            foreach ($filters as $filter) {
                // Checking for course ID.
                if (!is_null($filter->courseid) && !in_array($filter->courseid, $mycourseids)) {
                    continue;
                }
                // TODO: replace address with cohorts.
                // Checking for vintage.
                if (!is_null($filter->vintage) && $USER->address !== $filter->vintage) {
                    continue;
                }
                // If all filters passed, add slot to my slots and break.
                array_push($myslots, $slot);
                break;
            }
        }

        $now = new DateTimeImmutable();
        $returnslots = [];
        // Calculate date and time each slot happens next, and add it to the return list if within reach from today.
        foreach ($mySlots as $slot) {
            $slotdaytime = slot_helper::SCHOOL_UNITS[$slot->startunit];
            $slotdatetime = DateTime::createFromFormat('Y-m-d H:i', $now->format('Y-m-d ').$slotdaytime);
            // Move to next day this weekday occurs (doesn't move if it's the same as today).
            $slotdatetime->modify('this '.WEEKDAY::name_from($slot->weekday));

            // Check if slot is before now (because time of day and such) and move it a week into the future if so.
            if ($now->diff($slotdatetime)->invert === 1) {
                $slotdatetime->add(new DateInterval('P1W'));
            }

            // TODO: make setting of "3 days in advance" changeable.
            if ($now->diff($slotdatetime)->days <= 3) {
                array_push($returnslots, $slot->prepare_for_api());
            }
        }

        return $returnslots;
    }

    /**
     * Returns the structure of the slot array
     * @return external_single_structure
     */
    public static function get_my_slots_returns(): external_single_structure {
        return slot::api_structure();
    }
}
