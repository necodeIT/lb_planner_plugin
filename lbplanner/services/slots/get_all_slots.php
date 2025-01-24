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
use local_lbplanner\enums\CAPABILITY_FLAG;
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\slot;
use local_lbplanner\model\user;

/**
 * Returns all slots.
 * Throws exception if the current user is not a slotmaster.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_get_all_slots extends external_api {
    /**
     * Parameters for get_all_slots.
     * @return external_function_parameters
     */
    public static function get_all_slots_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns all slots.
     */
    public static function get_all_slots(): array {
        global $USER;
        $user = user::from_db($USER);

        if (!($user->get_capabilitybitmask() & CAPABILITY_FLAG::SLOTMASTER)) {
            throw new \moodle_exception('current user is not slotmaster');
        }
        $slots = slot_helper::get_all_slots();

        return array_map(fn(slot $slot) => $slot->prepare_for_api(), $slots);
    }

    /**
     * Returns the structure of the slot array
     * @return external_multiple_structure
     */
    public static function get_all_slots_returns(): external_multiple_structure {
        return new external_multiple_structure(
            slot::api_structure()
        );
    }
}
