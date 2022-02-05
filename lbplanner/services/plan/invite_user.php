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
use external_single_structure;
use external_value;

class plan_invite_user extends external_api {
    public static function leave_plan_parameters() {
        return new external_function_parameters(array(
            'owneruserid' => new external_value(
                PARAM_INT,
                'The id of the Owner of the plan',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'inviteuserid' => new external_value(
                PARAM_INT,
                'The id of the user who gets invited',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'planid' => new external_value(
                PARAM_INT,
                'The id of the plan',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ));
    }

    public static function invite_user($owneruserid, $inviteuserid , $planid) {
        global $DB;
        global $USER;

        $params = self::validate_parameters(
            self::leave_plan_parameters(),
            array('userid' => $owneruserid, 'inviteuserid' => $inviteuserid, 'planid' => $planid)
        );

        // TODO: Check if the token is from the same User as the UserId.
        // TODO: Check if Invited User is Valid.
        // TODO: Add Owner/Invitation/Time/Status/PlanId to the Invitation DB.

        return array('message' => 'Sucessfull');
    }

    public static function invite_user_returns() {
        return new external_single_structure(
            array('message' => new external_value(PARAM_TEXT, 'Sucessfull'))
        );
    }
}