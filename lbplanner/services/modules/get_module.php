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

class moudles_get_module extends external_api {
    public static function get_module_parameters() {
        return new external_function_parameters(array(
            'moduleid' => new external_value(PARAM_INT, 'The id of the module'),
            'userid' => new external_value(PARAM_INT, 'The id of the user'),
        ));
    }

    public static function get_module($moduleid, $userid) {
        global $DB;
        global $USER;

        $params = self::validate_parameters(self::get_module_parameters(), array('moduleid' => $moduleid, 'userid' => $userid));

        // TODO: Check if token is allowed to access this function.

        return array();
    }

    public static function get_module_returns() {
        return new external_single_structure(
            array(
                'moduleid' => new external_value(PARAM_INT, 'The id of the module'),
                'name' => new external_value(PARAM_TEXT, 'The name of the module'),
                'courseid' => new external_value(PARAM_INT, 'The id of the course'),
                'status' => new external_value(PARAM_INT, 'The status of the module'),
                'type' => new external_value(PARAM_INT, 'The type of the module'),
                'url' => new external_value(PARAM_TEXT, 'The url of the module in moodle'),
            )
        );
    }
}