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
use local_lbplanner\model\slot;

/**
 * Returns all slots a supervisor can see.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_get_supervisor_slots extends external_api {
    /**
     * Parameters for get_supervisor_slots.
     * @return external_function_parameters
     */
    public static function get_supervisor_slots_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns all slots a supervisor controls.
     */
    public static function get_supervisor_slots(): array {
        global $USER;

        $slots = slot_helper::get_supervisor_slots($USER->id);

        return array_map(fn(slot $slot) => $slot->prepare_for_api(), $slots);
    }

    /**
     * Returns the structure of the slot array
     * @return external_multiple_structure
     */
    public static function get_supervisor_slots_returns(): external_multiple_structure {
        return new external_multiple_structure(
            slot::api_structure()
        );
    }
}
