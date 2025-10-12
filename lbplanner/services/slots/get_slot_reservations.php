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
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\reservation;

/**
 * Returns all reservations for a slot.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_get_slot_reservations extends external_api {
    /**
     * Parameters for slots_get_slot_reservations.
     * @return external_function_parameters
     */
    public static function get_slot_reservations_parameters(): external_function_parameters {
        return new external_function_parameters([
            'slotid' => new external_value(PARAM_INT, 'ID of the slot to query for', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    /**
     * Returns all reservations for a slot.
     * @param int $slotid ID of the slot to query for
     */
    public static function get_slot_reservations(int $slotid): array {
        global $USER;
        self::validate_parameters(
            self::get_slot_reservations_parameters(),
            ['slotid' => $slotid]
        );

        slot_helper::assert_slot_supervisor($USER->id, $slotid);

        $reservations = slot_helper::get_reservations_for_slot($slotid);

        return array_map(fn(reservation $reservation) => $reservation->prepare_for_api(), $reservations);
    }

    /**
     * Returns the structure of the reservation array
     * @return external_multiple_structure
     */
    public static function get_slot_reservations_returns(): external_multiple_structure {
        return new external_multiple_structure(
            reservation::api_structure()
        );
    }
}
