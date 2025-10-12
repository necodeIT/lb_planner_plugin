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
use local_lbplanner\model\supervisor;

/**
 * Adds a supervisor to a slot
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_add_slot_supervisor extends external_api {
    /**
     * Parameters for add_slot_supervisor.
     * @return external_function_parameters
     */
    public static function add_slot_supervisor_parameters(): external_function_parameters {
        return new external_function_parameters([
            'userid' => new external_value(
                PARAM_INT,
                'ID of the user to be made a supervisor',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'slotid' => new external_value(
                PARAM_INT,
                'ID of the slot',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Adds a supervisor to a slot
     * @param int $userid ID of the user to be made a supervisor
     * @param int $slotid ID of the slot
     */
    public static function add_slot_supervisor(int $userid, int $slotid): void {
        global $USER, $DB;
        self::validate_parameters(
            self::add_slot_supervisor_parameters(),
            [
                'userid' => $userid,
                'slotid' => $slotid,
            ]
        );

        // Check if current user is supervisor for this slot, throw error if not.
        slot_helper::assert_slot_supervisor($USER->id, $slotid);

        // Add supervisor.
        $DB->insert_record(
            slot_helper::TABLE_SUPERVISORS,
            (new supervisor(0, $slotid, $userid))->prepare_for_db()
        );
    }

    /**
     * Return structure of add_slot_supervisor
     * @return null
     */
    public static function add_slot_supervisor_returns() {
        return null;
    }
}
