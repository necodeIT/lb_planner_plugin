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

use core_external\{external_api, external_function_parameters, external_value};

use local_lbplanner\enums\WEEKDAY;
use local_lbplanner\helpers\slot_helper;

/**
 * Update a slot's values
 *
 * @package local_lbplanner
 * @subpackage services_TODO
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_update_slot extends external_api {
    /**
     * Parameters for update_slot.
     * @return external_function_parameters
     */
    public static function update_slot_parameters(): external_function_parameters {
        // TODO: set hardcoded doc values with constants instead of hardcoded values.
        return new external_function_parameters([
            'slotid' => new external_value(
                PARAM_INT,
                'ID of the slot to update',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'startunit' => new external_value(
                PARAM_INT,
                'The school unit this slot starts in, starting at 1 for 8:00 and ending at 16 for 21:00 (null to ignore)',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'duration' => new external_value(
                PARAM_INT,
                'The amount of units this slot is long. startunit + duration may not exceed 16 (null to ignore)',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'weekday' => new external_value(
                PARAM_INT,
                'The weekday this slot happens on. '.WEEKDAY::format().' (null to ignore)',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'room' => new external_value(
                PARAM_TEXT,
                'The room this slot happens in. max. 7 characters (null to ignore)',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
            'size' => new external_value(
                PARAM_INT,
                'How many pupils this slot can fit (null to ignore)',
                VALUE_DEFAULT,
                null,
                NULL_ALLOWED
            ),
        ]);
    }


    /**
     * Update a slot's values
     * @param int $slotid ID of the slot to update
     * @param int $startunit the unit this slot starts in
     * @param int $duration how long the unit lasts for
     * @param int $weekday which day of the week this slot is on
     * @param string $room which room this slot is for
     * @param int $size how many pupils this slot can fit
     */
    public static function update_slot(int $slotid, int $startunit, int $duration, int $weekday, string $room, int $size): void {
        global $USER, $DB;
        self::validate_parameters(
            self::update_slot_parameters(),
            [
                'slotid' => $slotid,
                'startunit' => $startunit,
                'duration' => $duration,
                'weekday' => $weekday,
                'room' => $room,
                'size' => $size,
            ]
        );

        // Check if user is supervisor for this slot, throw error if not.
        slot_helper::assert_slot_supervisor($USER->id, $slotid);

        // Replace slot's values with new values if not null.
        $slot = slot_helper::get_slot($slotid);
        $varnames = ['startunit', 'duration', 'weekday', 'room', 'size'];
        foreach ($varnames as $varname) {
            if (!is_null($$varname)) {
                $slot->$varname = $$varname;
            }
        }
        // Validate slot with new data.
        $slot->validate();
        // Update DB.
        $DB->update_record(
            slot_helper::TABLE_SLOTS,
            $slot->prepare_for_db()
        );
        // Check if slot is now overfull, and notify frontend via exception if so.
        if ($slot->get_fullness() > $slot->size) {
            throw new \moodle_exception('Slot is now overfull!');
        }
    }

    /**
     * Return structure of update_slot
     * @return null
     */
    public static function update_slot_returns() {
        return null;
    }
}
