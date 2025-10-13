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

use local_lbplanner\enums\KANBANCOL_TYPE_NUMERIC;
use local_lbplanner\model\kanbanentry;

/**
 * Helper class for the kanban board
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2025 Pallasys
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

    /**
     * Gets specific kanban entry.
     * @param int $userid ID of the user to look entries up for
     * @param int $cmid ID of the content-module
     * @return kanbanentry the kanban entry matching this selection or null if not found
     */
    public static function get_entry(int $userid, int $cmid): ?kanbanentry {
        global $DB;

        $res = $DB->get_record(self::TABLE, ['userid' => $userid, 'cmid' => $cmid]);

        return $res !== false ? kanbanentry::from_obj($res) : null;
    }

    /**
     * Sets specific kanban entry.
     * @param kanbanentry $entry the entry to set
     */
    public static function set_entry(kanbanentry $entry): void {
        global $DB, $CFG;

        try {
            $DB->delete_records(self::TABLE, ['userid' => $entry->userid, 'cmid' => $entry->cmid]);
        } catch (\dml_exception $e) {
            // Needed for low-reporting contexts such as a prod server.
            echo 'error while trying to delete preexisting kanban entries: '
                . $e->getMessage()
                . "\nFurther info:\n"
                . $e->debuginfo;
            var_dump($entry);
            throw $e;
        }
        if ($entry->column !== KANBANCOL_TYPE_NUMERIC::BACKLOG) {
            $newid = $DB->insert_record(self::TABLE, $entry->prepare_for_db(), true);
            $entry->set_fresh($newid);
        }
    }
}
