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
use local_lbplanner\helpers\plan_helper;

/**
 * Set the deadline for a module.
 *
 * @package local_lbplanner
 * @subpackage services_plan
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class plan_set_deadline extends external_api {
    /**
     * Parameters for set_deadline.
     * @return external_function_parameters
     */
    public static function set_deadline_parameters(): external_function_parameters {
        return new external_function_parameters([
            'moduleid' => new external_value(
                PARAM_INT,
                'ID of the module the deadline is for',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'deadlinestart' => new external_value(
                PARAM_INT,
                'Start of the deadline as a UTC+0 UNIX timestamp',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'deadlineend' => new external_value(
                PARAM_INT,
                'End of the deadline as a UTC+0 UNIX timestamp',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Set the deadline for a module
     *
     * @param int $moduleid ID of the module the deadline is for
     * @param int $deadlinestart Start of the deadline
     * @param int $deadlineend End of the deadline
     * @return void
     * @throws \moodle_exception when access denied
     */
    public static function set_deadline(int $moduleid, int $deadlinestart, int $deadlineend) {
        global $DB, $USER;

        self::validate_parameters(
            self::set_deadline_parameters(),
            [
                'moduleid' => $moduleid,
                'deadlinestart' => $deadlinestart,
                'deadlineend' => $deadlineend,
            ]
        );

        $planid = plan_helper::get_plan_id($USER->id);

        if (!plan_helper::check_edit_permissions($planid, $USER->id)) {
            throw new \moodle_exception('Access denied');
        }

        $deadline = $DB->get_record(plan_helper::DEADLINES_TABLE, ['moduleid' => $moduleid, 'planid' => $planid]);

        if ($deadline !== false) {
            // Update the existing deadline.

            $deadline->deadlinestart = $deadlinestart;
            $deadline->deadlineend = $deadlineend;

            $DB->update_record(plan_helper::DEADLINES_TABLE, $deadline);
        } else {
            // Otherwise insert a new one.
            $deadline = new \stdClass();

            $deadline->planid = $planid;
            $deadline->moduleid = $moduleid;
            $deadline->deadlinestart = $deadlinestart;
            $deadline->deadlineend = $deadlineend;

            $DB->insert_record(plan_helper::DEADLINES_TABLE, $deadline);
        }
    }

    /**
     * Returns the structure of nothing.
     * @return null
     */
    public static function set_deadline_returns() {
        return null;
    }
}
