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

namespace local_lbplanner\sync;

use core_component;
use core_customfield\category_controller;
use customfield_select\field_controller;
use local_modcustomfields\customfield\mod_handler;

/**
 * Installation and lookup logic for the activity classification field.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
final class classification {
    /** Plugin configuration key containing the custom-field category ID. */
    public const CONFIG_CATEGORY_ID = 'categoryid';

    /** Stable custom-field shortname. */
    public const FIELD_SHORTNAME = 'lb_planer_gk_ek';

    /**
     * Ensure the activity classification category and field exist.
     *
     * @return void
     */
    public static function ensure_configuration(): void {
        global $DB;

        if (!array_key_exists('modcustomfields', core_component::get_plugin_list('local'))) {
            throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
        }

        $handler = mod_handler::create();
        $categoryid = self::get_category_id();
        if ($categoryid !== null) {
            if (!$DB->record_exists('customfield_category', ['id' => $categoryid])) {
                throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
            }
            $fields = $DB->get_records('customfield_field', [
                'categoryid' => $categoryid,
                'shortname' => self::FIELD_SHORTNAME,
            ]);
            if (count($fields) > 1) {
                throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
            }
            if (count($fields) === 1) {
                $field = reset($fields);
                if ($field->type !== 'select') {
                    throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
                }
                return;
            }
        } else {
            $categoryid = $handler->create_category('LB Planner');
            set_config(self::CONFIG_CATEGORY_ID, $categoryid, 'local_lbplanner');
        }

        $category = category_controller::create($categoryid, null, $handler);
        $category->save();
        self::create_field($category);
    }

    /**
     * Add the select field to an existing classification category.
     *
     * @param category_controller $category Classification category.
     * @return void
     */
    private static function create_field(category_controller $category): void {
        $record = (object) ['type' => 'select'];
        $field = field_controller::create(0, $record, $category);
        $field->set('name', get_string('cf_name', 'local_lbplanner'));
        $field->set('description', get_string('cf_description', 'local_lbplanner'));
        $field->set('type', 'select');
        $field->set('configdata', json_encode([
            'required' => '0',
            'uniquevalues' => '0',
            'options' => "GK\r\nEK\r\nTEST\r\nM",
            'defaultvalue' => '',
            'locked' => '0',
            'visibility' => '2',
        ]));
        $field->set('shortname', self::FIELD_SHORTNAME);
        $field->save();
    }

    /**
     * Remove the activity classification configuration during uninstall.
     *
     * @return void
     */
    public static function remove_configuration(): void {
        global $DB;

        $categoryid = self::get_category_id();
        unset_config(self::CONFIG_CATEGORY_ID, 'local_lbplanner');
        if ($categoryid === null || !$DB->record_exists('customfield_category', ['id' => $categoryid])) {
            return;
        }

        $handler = mod_handler::create();
        $category = category_controller::create($categoryid, null, $handler);
        $handler->delete_category($category);
    }

    /**
     * Return the configured classification for an assignment or quiz course module.
     *
     * @param int $coursemoduleid Course-module ID.
     * @return string|null Classification key, or null for an empty selection.
     */
    public static function get_key(int $coursemoduleid): ?string {
        global $DB;

        $modulename = $DB->get_field_sql(
            'SELECT m.name
               FROM {course_modules} cm
               JOIN {modules} m ON m.id = cm.module
              WHERE cm.id = :cmid',
            ['cmid' => $coursemoduleid],
            MUST_EXIST
        );
        if (!in_array($modulename, ['assign', 'quiz'], true)) {
            throw new \moodle_exception('err_sync_invalidreference', 'local_lbplanner');
        }

        $categoryid = self::get_category_id();
        if ($categoryid === null) {
            throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
        }

        $fieldrecords = $DB->get_records('customfield_field', [
            'categoryid' => $categoryid,
            'shortname' => self::FIELD_SHORTNAME,
        ]);
        if (count($fieldrecords) !== 1 || reset($fieldrecords)->type !== 'select') {
            throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
        }

        $category = category_controller::create($categoryid);
        $matchingdata = [];
        foreach ($category->get_handler()->get_instance_data($coursemoduleid) as $data) {
            if ($data->get_field()->get('shortname') === self::FIELD_SHORTNAME) {
                $matchingdata[] = $data;
            }
        }

        if (count($matchingdata) !== 1) {
            throw new \moodle_exception('err_sync_classificationconfig', 'local_lbplanner');
        }

        $data = reset($matchingdata);
        $storedvalue = $data->get_value();
        if ($storedvalue === null || $storedvalue === '' || (string) $storedvalue === '0') {
            return null;
        }

        $key = $data->export_value();
        if (!is_string($key) || !in_array($key, contract::CLASSIFICATION_KEYS, true)) {
            throw new \moodle_exception('err_sync_unsupportedclassification', 'local_lbplanner');
        }

        return $key;
    }

    /**
     * Return the configured category ID.
     *
     * @return int|null Category ID, or null when it is not configured.
     */
    private static function get_category_id(): ?int {
        $categoryid = get_config('local_lbplanner', self::CONFIG_CATEGORY_ID);
        if ($categoryid === false || (int) $categoryid <= 0) {
            return null;
        }
        return (int) $categoryid;
    }
}
