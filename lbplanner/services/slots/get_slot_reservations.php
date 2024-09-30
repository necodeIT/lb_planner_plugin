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
use local_lbplanner\model\reservation;

/**
 * Returns all slots a supervisor can theoretically reserve for a user.
 * This does not include times the user has already reserved a slot for.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slots_get_slot_reservations extends external_api {
    /**
     * Parameters for get_student_slots.
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

        if (!slot_helper::check_slot_supervisor($USER->id, $slotid)) {
            throw new \moodle_exception('Insufficient Permissions: not a supervisor of this slot');
        }

        $reservations = slot_helper::get_reservations_for_slot($slotid);

        return array_map(fn(reservation $reservation) => $reservation->prepare_for_api(), $reservations);
    }

    /**
     * Returns the structure of the slot array
     * @return external_multiple_structure
     */
    public static function get_slot_reservations_returns(): external_multiple_structure {
        return new external_multiple_structure(
            reservation::api_structure()
        );
    }
}
