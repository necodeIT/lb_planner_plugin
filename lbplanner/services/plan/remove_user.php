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
use external_value;
use local_lbplanner\helpers\plan_helper;

/**
 * Remove a user from your plan
 */
class plan_remove_user extends external_api {
    public static function remove_user_parameters() {
        return new external_function_parameters([
            'userid' => new external_value(PARAM_INT, 'ID of the user to remove', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    public static function remove_user($userid, $planid) {
        global $DB, $USER;

        self::validate_parameters(
            self::remove_user_parameters(),
            ['userid' => $userid]
        );

        $planid = plan_helper::get_plan_id($USER->id);

        plan_helper::remove_user($planid, $USER->id, $userid);
    }

    public static function remove_user_returns() {
        return null;
    }
}
