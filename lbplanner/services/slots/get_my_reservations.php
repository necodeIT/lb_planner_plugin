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

use core_external\{external_api, external_function_parameters, external_multiple_structure};
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\reservation;

/**
 * Returns all reservations for this user.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_get_my_reservations extends external_api {
    /**
     * Parameters for get_my_reservations.
     * @return external_function_parameters
     */
    public static function get_my_reservations_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns all reservations for this user.
     */
    public static function get_my_reservations(): array {
        global $USER;

        $reservations = slot_helper::get_reservations_for_user($USER->id);
        $reservations = slot_helper::filter_reservations_for_recency($reservations);

        return array_map(fn(reservation $reservation) => $reservation->prepare_for_api(), $reservations);
    }

    /**
     * Returns the structure of the reservation array
     * @return external_multiple_structure
     */
    public static function get_my_reservations_returns(): external_multiple_structure {
        return new external_multiple_structure(
            reservation::api_structure()
        );
    }
}
