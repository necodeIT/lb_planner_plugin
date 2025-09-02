<?php
// This file is part of the local_lbplanner.
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

namespace local_lbplanner\helpers;

use local_lbplanner\model\kanbanentry;

/**
 * Helper class for the kanban board
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2025 NecodeIT
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class kanban_helper {

    /**
     * Table for storing kanban board entries.
     */
    const TABLE = 'local_lbplanner_kanbanentries';

    /**
     * Gets all kanban entries for a user.
     * @param int $userid ID of the user to look entries up for
     * @return kanbanentry[] all the kanban entries for this user
     */
    public static function get_all_entries_by_user(int $userid): array {
        global $DB;

        $res = $DB->get_records(self::TABLE, ['userid' => $userid]);
        $entries = [];
        foreach ($res as $obj) {
            array_push($entries, kanbanentry::from_obj($obj));
        }

        return $entries;
    }
}
