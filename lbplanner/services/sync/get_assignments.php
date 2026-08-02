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

use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use local_lbplanner\external\sync_service;
use local_lbplanner\sync\provider;

/**
 * Return the current token owner's visible assignments.
 *
 * @package local_lbplanner
 * @subpackage services_sync
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class sync_get_assignments extends sync_service {
    /**
     * Parameters for get_assignments.
     *
     * @return external_function_parameters
     */
    public static function get_assignments_parameters(): external_function_parameters {
        return sync_service::no_parameters();
    }

    /**
     * Return the current token owner's visible assignments.
     *
     * @return array Assignment rows.
     */
    public static function get_assignments(): array {
        self::validate_parameters(self::get_assignments_parameters(), []);
        self::require_access();
        return provider::get_assignments();
    }

    /**
     * Return structure for get_assignments.
     *
     * @return external_multiple_structure
     */
    public static function get_assignments_returns(): external_multiple_structure {
        return sync_service::activities_structure();
    }
}
