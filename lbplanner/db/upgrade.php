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

/**
 * Upgrade steps for the sync-only plugin.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\sync\classification;

/**
 * Destructively upgrade LB Planner to the sync-only data provider.
 *
 * @param int $oldversion Previously installed plugin version.
 * @return bool Always true after a successful upgrade.
 */
function xmldb_local_lbplanner_upgrade($oldversion): bool {
    global $DB;

    if ($oldversion < 202607260000) {
        // Validate or repair the retained Moodle-native classification field before deleting legacy data.
        classification::ensure_configuration();

        $dbman = $DB->get_manager();
        $legacytables = [
            'local_lbplanner_kanbanentries',
            'local_lbplanner_notification',
            'local_lbplanner_plan_invites',
            'local_lbplanner_deadlines',
            'local_lbplanner_plan_access',
            'local_lbplanner_courses',
            'local_lbplanner_reservations',
            'local_lbplanner_slot_courses',
            'local_lbplanner_supervisors',
            'local_lbplanner_slots',
            'local_lbplanner_plans',
            'local_lbplanner_users',
        ];

        foreach ($legacytables as $tablename) {
            $table = new xmldb_table($tablename);
            if ($dbman->table_exists($table)) {
                $dbman->drop_table($table);
            }
        }

        foreach ([
            'release',
            'release_fullnum',
            'panic',
            'slot_futuresight',
            'course_outdaterange',
        ] as $configkey) {
            unset_config($configkey, 'local_lbplanner');
        }

        upgrade_plugin_savepoint(true, 202607260000, 'local', 'lbplanner');
    }

    return true;
}
