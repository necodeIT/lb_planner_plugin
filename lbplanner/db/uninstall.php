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
 * Contains some stuff for when the plugin gets uninstalled.
 * Upgrading the plugin shouldn't trigger this code.
 *
 * @package local_lbplanner
 * @subpackage db
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\helpers\{config_helper, course_helper};

/**
 * Runs when plugin is uninstalled
 */
function xmldb_local_lbplanner_uninstall() {
    config_helper::remove_customfield();

    $tag = core_tag_tag::get_by_name(core_tag_collection::get_default(), course_helper::EDUPLANNER_TAG, strictness:MUST_EXIST);
    core_tag_tag::delete_tags($tag->id);
}
