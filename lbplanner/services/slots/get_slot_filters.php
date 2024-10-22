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
use external_multiple_structure;
use external_value;

use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot_filter;

/**
 * List all filters that apply to a slot.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_get_slot_filters extends external_api {
    /**
     * Parameters for get_slot_filters.
     * @return external_function_parameters
     */
    public static function get_slot_filters_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slotid' => new external_value(
                PARAM_INT,
                'ID of the slot to query for',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * List all filters that apply to a slot.
     * @param int $slotid slot to query for
     */
    public static function get_slot_filters(int $slotid): array {
        global $USER;
        self::validate_parameters(
            self::get_slot_filters_parameters(),
            [
                'slotid' => $slotid,
            ]
        );

        // Check if user is supervisor for this slot, throw error if not.
        if (!slot_helper::check_slot_supervisor($USER->id, $slotid)) {
            throw new \moodle_exception('Insufficient Permission: you\'re not supervisor of this slot');
        }
        // Get all filters for this slot, and return their API representations.
        $filters = slot_helper::get_filters_for_slot($slotid);
        $filters_returns = [];
        foreach($filters as $filter) {
            array_push($filters_returns, $filter->prepare_for_api());
        }
        return $filters_returns;
    }

    /**
     * Return structure of get_slot_filters
     * @return external_multiple_structure
     */
    public static function get_slot_filters_returns(): external_multiple_structure {
        return new external_multiple_structure(
            slot_filter::api_structure()
        );
    }
}
