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

class plan_update_plan extends external_api {
    public static function update_plan_parameters() {
        return new external_function_parameters(array(
            'userid' => new external_value(
                PARAM_INT,
                'The id of the user to get the data for',
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
            'planname' => new external_value(
                PARAM_TEXT,
                'The Name of the Plan',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ));
    }

    public static function update_plan($userid, $planid, $planname) {
        global $DB;
        global $USER;

        $params = self::validate_parameters(
            self::update_plan_parameters(),
            array('userid' => $userid, 'planid' => $planid, 'planname' => $planname)
        );

        // TODO: Check if the token is from the same User as the UserId.
        // TODO: Check if the planname is valid.
        // TODO: Change the planname from the Plan ID.

        return array('message' => 'Sucessfull');
    }

    public static function update_plan_returns() {
        return new external_single_structure(
            array('message' => new external_value(PARAM_TEXT, 'Sucessfull'))
        );
    }
}