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

use \core_external\{external_api, external_function_parameters, external_value};
use local_lbplanner\helpers\slot_helper;

/**
 * Delete a filter from a slot
 *
 * NOTE: after deleting a filter, the associated slot may have reservations
 * for people who don't fit the filters anymore. This is intended behaviour.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_delete_slot_filter extends external_api {
    /**
     * Parameters for delete_slot_filter.
     * @return external_function_parameters
     */
    public static function delete_slot_filter_parameters(): external_function_parameters {
        return new external_function_parameters([
            'filterid' => new external_value(
                PARAM_INT,
                'ID of the filter to delete',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Delete a filter from a slot
     * @param int $filterid ID of the filter to delete
     */
    public static function delete_slot_filter(int $filterid): void {
        global $USER, $DB;
        self::validate_parameters(
            self::delete_slot_filter_parameters(),
            [
                'filterid' => $filterid,
            ]
        );

        $filter = slot_helper::get_slot_filter($filterid);

        // Check if user is supervisor for this slot, throw error if not.
        if (!slot_helper::check_slot_supervisor($USER->id, $filter->slotid)) {
            throw new \moodle_exception('Insufficient Permission: you\'re not supervisor of this filter\'s associated slot');
        }

        // Delete filter.
        $DB->delete_records(
            slot_helper::TABLE_SLOT_FILTERS,
            ['id' => $filterid]
        );
    }

    /**
     * Return structure of delete_slot_filter
     * @return null
     */
    public static function delete_slot_filter_returns() {
        return null;
    }
}
