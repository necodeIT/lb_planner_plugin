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
use local_lbplanner\enums\KANBANCOL_TYPE;
use local_lbplanner\helpers\kanban_helper;
use local_lbplanner\model\kanbanentry;

/**
 * Moves a module to a different column on the kanban board.
 *
 * @package local_lbplanner
 * @subpackage services_kanban
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class kanban_move_module extends external_api {
    /**
     * Parameters for move_module.
     * @return external_function_parameters
     */
    public static function move_module_parameters(): external_function_parameters {
        return new external_function_parameters([
            'cmid' => new external_value(
                PARAM_INT,
                'ID of the module to move',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'column' => new external_value(
                PARAM_TEXT,
                'name of the target column',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Moves a module to a different column on the kanban board.
     * @param int $cmid content-module ID
     * @param string $column name of the target column
     */
    public static function move_module(int $cmid, string $column): void {
        global $USER;
        self::validate_parameters(
            self::move_module_parameters(),
            [
                'cmid' => $cmid,
                'column' => $column,
            ]
        );

        $colnr = KANBANCOL_TYPE::to_numeric($column);
        kanban_helper::set_entry(new kanbanentry(0, $USER->id, $cmid, $colnr));
    }

    /**
     * Return structure of move_module
     * @return null
     */
    public static function move_module_returns() {
        return null;
    }
}
