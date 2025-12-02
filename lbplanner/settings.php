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
 * Defines some settings
 *
 * @package local_lbplanner
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\enums\SETTINGS;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lbplanner', 'EduPlanner');
    $ADMIN->add('localplugins', $settings);

    $daysingular = get_string('unit_day', 'local_lbplanner');
    $dayplural = get_string('unit_day_pl', 'local_lbplanner');

    $futuresightsett = new admin_setting_configselect(
        'local_lbplanner/' . SETTINGS::SLOT_FUTURESIGHT,
        get_string('sett_futuresight_title', 'local_lbplanner'),
        get_string('sett_futuresight_desc', 'local_lbplanner'),
        3,
        [
            0 => "0 {$dayplural}",
            1 => "1 {$daysingular}",
            2 => "2 {$dayplural}",
            3 => "3 {$dayplural}",
            4 => "4 {$dayplural}",
            5 => "5 {$dayplural}",
            6 => "6 {$dayplural}",
            7 => "7 {$dayplural}",
        ],
    );
    $settings->add($futuresightsett);

    $outdaterangesett = new admin_setting_configduration(
        'local_lbplanner/' . SETTINGS::COURSE_OUTDATERANGE,
        get_string('sett_outdaterange_title', 'local_lbplanner'),
        get_string('sett_outdaterange_desc', 'local_lbplanner'),
        31536000, // 1 non-leap year.
        86400, // In days.
    );
    $outdaterangesett->set_min_duration(0);
    $settings->add($outdaterangesett);

    $sentrydsnsett = new admin_setting_configtext(
        'local_lbplanner/' . SETTINGS::SENTRY_DSN,
        get_string('sett_sentrydsn_title', 'local_lbplanner'),
        get_string('sett_sentrydsn_desc', 'local_lbplanner'),
        '',
        PARAM_TEXT
    );
    $settings->add($sentrydsnsett);

    $panicsett = new admin_setting_configcheckbox(
        'local_lbplanner/' . SETTINGS::PANIC,
        get_string('sett_panic_title', 'local_lbplanner'),
        get_string('sett_panic_desc', 'local_lbplanner'),
        '0',
    );
    $settings->add($panicsett);
}
