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
use local_lbplanner\enums\WEEKDAY;
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot;
use local_lbplanner\model\supervisor;

/**
 * Create a slot
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_create_slot extends external_api {
    /**
     * Parameters for create_slot.
     * @return external_function_parameters
     */
    public static function create_slot_parameters(): external_function_parameters {
        // TODO: set hardcoded doc values with constants instead of hardcoded values.
        return new external_function_parameters([
            'startunit' => new external_value(
                PARAM_INT,
                'The school unit this slot starts in, starting at 1 for 8:00 and ending at 16 for 21:00',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'duration' => new external_value(
                PARAM_INT,
                'The amount of units this slot is long. startunit + duration may not exceed 16',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'weekday' => new external_value(
                PARAM_INT,
                'The weekday this slot happens on. '.WEEKDAY::format(),
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'room' => new external_value(
                PARAM_TEXT,
                'The room this slot happens in. max. 7 characters',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'size' => new external_value(
                PARAM_INT,
                'How many pupils this slot can fit',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Create a slot
     * @param int $startunit the unit this slot starts in
     * @param int $duration how long the unit lasts for
     * @param int $weekday which day of the week this slot is on
     * @param string $room which room this slot is for
     * @param int $size how many pupils this slot can fit
     */
    public static function create_slot(int $startunit, int $duration, int $weekday, string $room, int $size): array {
        global $DB, $USER;
        self::validate_parameters(
            self::create_slot_parameters(),
            [
                'startunit' => $startunit,
                'duration' => $duration,
                'weekday' => $weekday,
                'room' => $room,
                'size' => $size,
            ]
        );

        $slot = new slot(0, $startunit, $duration, $weekday, $room, $size);
        $slot->validate();

        // Actually inserting the slot.
        $id = $DB->insert_record(slot_helper::TABLE_SLOTS, $slot->prepare_for_db());
        $slot->set_fresh($id);

        // Set current user as supervisor for this new slot.
        $DB->insert_record(
            slot_helper::TABLE_SUPERVISORS,
            (new supervisor(0, $slot->id, $USER->id))->prepare_for_db()
        );

        return $slot->prepare_for_api();
    }

    /**
     * Returns the structure of the slot
     * @return external_single_structure
     */
    public static function create_slot_returns(): external_single_structure {
        return slot::api_structure();
    }
}
