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

use local_lbplanner\helpers\slot_helper;
use local_lbplanner\helpers\notifications_helper;
use local_lbplanner\enums\NOTIF_TRIGGER;

/**
 * Deletes slot
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_delete_slot extends external_api {
    /**
     * Parameters for delete_slot.
     * @return external_function_parameters
     */
    public static function delete_slot_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slotid' => new external_value(
                PARAM_INT,
                'ID of the slot to be deleted',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Tries to request unbooking
     * @param int $slotid which slot to delete
     */
    public static function delete_slot(int $slotid): void {
        global $USER, $DB;
        self::validate_parameters(
            self::delete_slot_parameters(),
            [
                'slotid' => $slotid,
            ]
        );

        // Check if user is supervisor for this slot, throw error if not.
        if (!slot_helper::check_slot_supervisor($USER->id, $slotid)) {
            throw new \moodle_exception('Insufficient Permission: you\'re not supervisor of this slot');
        }

        // Notify affected users.
        $reservations = slot_helper::get_reservations_for_slot($slotid);
        foreach ($reservations as $res) {
            notifications_helper::notify_user($res->userid, $res->id, NOTIF_TRIGGER::UNBOOK_FORCED);
        }
        // Delete all reservations for this slot.
        $DB->delete_records(
            slot_helper::TABLE_RESERVATIONS,
            ['slotid' => $slotid]
        );
        // Delete Supervisors for this slot.
        $DB->delete_records(
            slot_helper::TABLE_SUPERVISORS,
            ['slotid' => $slotid]
        );
        // Delete Filters for this slot.
        $DB->delete_records(
            slot_helper::TABLE_SLOT_FILTERS,
            ['slotid' => $slotid]
        );
        // Finally, delete slot.
        $DB->delete_records(
            slot_helper::TABLE_SLOTS,
            ['id' => $slotid]
        );
    }

    /**
     * Returns nothing at all
     * @return null
     */
    public static function delete_slot_returns() {
        return null;
    }
}
