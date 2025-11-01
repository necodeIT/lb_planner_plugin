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

use core_component;
use core_customfield\category_controller;
use customfield_select\field_controller;
use local_modcustomfields\customfield\mod_handler;
use local_lbplanner\enums\SETTINGS;

/**
 * Helper class for config
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class config_helper {
    /**
     * Adds a customfield to moodle for each activity where teachers can select GK EK Test or M.
     * Default value is empty.
     */
    public static function add_customfield(): void {
        // Check if the category is already created and only create it if it doesn't exist.
        // Check if plugin "modcustomfields" is installed and create the category and the custom field.
        if (self::get_category_id() === -1) {
            if (array_key_exists('modcustomfields', core_component::get_plugin_list('local'))) {
                $handler = mod_handler::create();
                $categoryid = $handler->create_category('LB Planer');

                set_config(SETTINGS::CF_CATID, $categoryid, 'local_lbplanner');
                $categorycontroller = category_controller::create($categoryid, null, $handler);
                $categorycontroller->save();

                // Dont ask me why but moodle doesnt allow me to just insert the String "select" into the type field.
                $record = new \stdClass();
                $record->type = 'select';

                $fieldcontroller = field_controller::create(0, $record, $categorycontroller);
                // Added the default attributes for the custom field.
                $fieldcontroller->set('name', 'LB Planer Task Type');
                $fieldcontroller->set('description', 'Tracks whether the task is GK/EK/TEST/M');
                $fieldcontroller->set('type', 'select');
                // Because moodle wants me to save the configdata as a json string, I have to do this.
                // I don't know why moodle does this, but it does. I don't like it. but I have to do it. so I do it.
                $fieldcontroller->set(
                    'configdata',
                    '{"required":"0","uniquevalues":"0","options":"GK\r\nEK\r\nTEST\r\nM",
                "defaultvalue":"","locked":"0","visibility":"2"}'
                );
                $fieldcontroller->set('shortname', 'lb_planer_gk_ek');
                $fieldcontroller->save();
            }
        }
    }
    /**
     * Removes a customfield from moodle for each activity where teachers can select GK EK or both.
     *
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public static function remove_customfield(): void {
        $handler = mod_handler::create();
        $catid = self::get_category_id();
        unset_config(SETTINGS::CF_CATID, 'local_lbplanner');
        if ($catid !== -1) {
            $catcontroller = category_controller::create($catid, null, $handler);
            $handler->delete_category($catcontroller);
        }
    }

    /**
     * Get the category id from the config
     * @return int the category id if it is set, -1 otherwise
     */
    public static function get_category_id(): int {
        $catid = get_config('local_lbplanner', SETTINGS::CF_CATID);
        if ($catid === false) {
            return -1;
        } else {
            return intval($catid);
        }
    }

    /**
     * Get the setting for how far into the future students should be able to reserve slots.
     * @return int futuresight in number of days (0-6)
     */
    public static function get_slot_futuresight(): int {
        return get_config('local_lbplanner', SETTINGS::SLOT_FUTURESIGHT);
    }

    /**
     * Get the setting for how long after a course ends it should start being hidden in Eduplanner.
     * @return int outdated range in number of seconds (>=0)
     */
    public static function get_course_outdatedrange(): int {
        return get_config('local_lbplanner', SETTINGS::COURSE_OUTDATERANGE);
    }

    /**
     * Get the Sentry DSN - for where to send error debugging info to.
     * @return string the sentry DSN
     */
    public static function get_sentry_dsn(): string {
        return get_config('local_lbplanner', SETTINGS::SENTRY_DSN);
    }
}
