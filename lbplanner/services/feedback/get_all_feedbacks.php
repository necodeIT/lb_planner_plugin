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
use external_multiple_structure;
use local_lbplanner\helpers\feedback_helper;

/**
 * Get all feedback from the database.
 */
class feedback_get_all_feedbacks extends external_api {
    public static function get_all_feedbacks_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_all_feedbacks(): array {
        feedback_helper::assert_admin_access();
        return feedback_helper::get_all_feedbacks();
    }

    public static function get_all_feedbacks_returns() {
        return new external_multiple_structure(
            feedback_helper::structure(),
        );
    }
}
