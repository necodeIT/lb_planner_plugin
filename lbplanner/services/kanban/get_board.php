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

use core_external\{
    external_api,
    external_function_parameters,
    external_multiple_structure,
    external_single_structure,
    external_value,
};
use local_lbplanner\enums\{KANBANCOL_TYPE, KANBANCOL_TYPE_NUMERIC, MODULE_TYPE};
use local_lbplanner\helpers\kanban_helper;
use local_lbplanner\model\{module, user};

/**
 * Returns all entries in the kanban board for the current user.
 *
 * @package local_lbplanner
 * @subpackage services_kanban
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class kanban_get_board extends external_api {
    /**
     * Parameters for kanban_get_board.
     * @return external_function_parameters
     */
    public static function get_board_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Returns all entries in the kanban board for the current user.
     */
    public static function get_board(): array {
        global $USER;

        $entries = kanban_helper::get_all_entries_by_user($USER->id);

        $sorted = [
            KANBANCOL_TYPE_NUMERIC::TODO => [],
            KANBANCOL_TYPE_NUMERIC::INPROGRESS => [],
            KANBANCOL_TYPE_NUMERIC::DONE => [],
        ];

        $ekenabled = user::from_mdlobj($USER)->ekenabled;

        foreach ($entries as $entry) {
            if (!$ekenabled) {
                $module = module::from_cmid($entry->cmid);
                if ($module->get_type() === MODULE_TYPE::EK) {
                    continue;
                }
            }
            array_push($sorted[$entry->column], $entry->cmid);
        }

        return [
            KANBANCOL_TYPE::TODO => $sorted[KANBANCOL_TYPE_NUMERIC::TODO],
            KANBANCOL_TYPE::INPROGRESS => $sorted[KANBANCOL_TYPE_NUMERIC::INPROGRESS],
            KANBANCOL_TYPE::DONE => $sorted[KANBANCOL_TYPE_NUMERIC::DONE],
        ];
    }

    /**
     * Return structure of kanban_get_board
     * @return external_multiple_structure
     */
    public static function get_board_returns() {
        return new external_single_structure([
            KANBANCOL_TYPE::TODO => new external_multiple_structure(new external_value(PARAM_INT, 'course-module ID')),
            KANBANCOL_TYPE::INPROGRESS => new external_multiple_structure(new external_value(PARAM_INT, 'course-module ID')),
            KANBANCOL_TYPE::DONE => new external_multiple_structure(new external_value(PARAM_INT, 'course-module ID')),
        ]);
    }
}
