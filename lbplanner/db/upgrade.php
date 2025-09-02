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
 * for upgrading the db
 *
 * @package local_lbplanner
 * @subpackage db
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\helpers\config_helper;

/**
 * Upgrades the DB version
 *
 * @param mixed $oldversion the previous version to upgrade from
 * @return bool true
 */
function xmldb_local_lbplanner_upgrade($oldversion): bool {
    global $DB;
    if ($oldversion < 202502110011) {
        config_helper::remove_customfield();
        config_helper::add_customfield();
    }
    if ($oldversion < 202509020000) {
        $dbman = $DB->get_manager();

        $table = new xmldb_table('local_lbplanner_users');
        $f1 = new xmldb_field('showcolumncolors', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, false, 1, 'ekenabled');
        $f2 = new xmldb_field('automovecompletedtasks', XMLDB_TYPE_TEXT, null, null, null, null, null, 'showcolumncolors');
        $f3 = new xmldb_field('automovesubmittedtasks', XMLDB_TYPE_TEXT, null, null, null, null, null, 'automovecompletedtasks');
        $f4 = new xmldb_field('automoveoverduetasks', XMLDB_TYPE_TEXT, null, null, null, null, null, 'automovesubmittedtasks');

        $dbman->add_field($table, $f1);
        $dbman->add_field($table, $f2);
        $dbman->add_field($table, $f3);
        $dbman->add_field($table, $f4);
        upgrade_plugin_savepoint(true, 202509020000, 'local', 'lbplanner');
    }
    return true;
}
