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
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot_filter;

/**
 * Creates a new filter and adds it to a slot
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_add_slot_filter extends external_api {
    /**
     * Parameters for add_slot_filter.
     * @return external_function_parameters
     */
    public static function add_slot_filter_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slotid' => new external_value(
                PARAM_INT,
                'slot to add the filter to',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'courseid' => new external_value(
                PARAM_INT,
                'course to filter for (or null if "any")',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'vintage' => new external_value(
                PARAM_TEXT,
                'school class to filter for (or null if "any")',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
        ]);
    }

    /**
     * Creates a new filter and adds it to a slot
     *
     * NOTE: either $courseid or $vintage *have* to be non-null
     *
     * @param int $slotid slot to add the filter to
     * @param ?int $courseid course to filter for (or null if 'any')
     * @param ?string $vintage school class to filter for (or null if 'any')
     */
    public static function add_slot_filter(int $slotid, ?int $courseid, ?string $vintage): array {
        global $USER, $DB;
        self::validate_parameters(
            self::add_slot_filter_parameters(),
            [
                'slotid' => $slotid,
                'courseid' => $courseid,
                'vintage' => $vintage,
            ]
        );

        // Check if user is supervisor for this slot, throw error if not.
        slot_helper::assert_slot_supervisor($USER->id, $slotid);
        // Ensure that either $courseid or $vintage are non-null.
        if (is_null($courseid) && is_null($vintage)) {
            throw new \moodle_exception('courseid and vintage can\'t both be null');
        }

        $filter = new slot_filter(0, $slotid, $courseid, $vintage);

        // Insert into DB.
        $filterid = $DB->insert_record(
            slot_helper::TABLE_SLOT_FILTERS,
            $filter->prepare_for_db()
        );
        $filter->set_fresh($filterid);
        // Return for frontend.
        return $filter->prepare_for_api();
    }

    /**
     * Return structure of add_slot_filter
     * @return external_single_structure
     */
    public static function add_slot_filter_returns(): external_single_structure {
        return slot_filter::api_structure();
    }
}
