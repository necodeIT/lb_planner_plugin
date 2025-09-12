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
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\enums\SETTINGS;

defined('MOODLE_INTERNAL') || die;

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lbplanner', 'Eduplanner');
    $ADMIN->add('localplugins', $settings);

    $futuresightsett = new admin_setting_configselect(
        'local_lbplanner/'.SETTINGS::SLOT_FUTURESIGHT,
        'Slot Futuresight',
        'How many days into the future students can reserve slots',
        3,
        [
            0 => "0 Days",
            1 => "1 Day",
            2 => "2 Days",
            3 => "3 Days",
            4 => "4 Days",
            5 => "5 Days",
            6 => "6 Days",
            7 => "7 Days",
        ],
    );
    $settings->add($futuresightsett);

    $outdaterangesett = new admin_setting_configduration(
        'local_lbplanner/'.SETTINGS::COURSE_OUTDATERANGE,
        'Course Outdaterange',
        'How long after a course ends should it start being hidden in Eduplanner?',
        31536000, // 1 non-leap year.
        86400, // In days.
    );
    $outdaterangesett->set_min_duration(0);
    $settings->add($outdaterangesett);
}
