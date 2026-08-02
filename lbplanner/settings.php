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
 * Sentry observability settings.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\observability\sentry;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_lbplanner', get_string('settings_title', 'local_lbplanner'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_lbplanner/' . sentry::CONFIG_DSN,
        get_string('settings_sentry_dsn', 'local_lbplanner'),
        get_string('settings_sentry_dsn_desc', 'local_lbplanner'),
        '',
        PARAM_RAW_TRIMMED
    ));
    $settings->add(new admin_setting_configselect(
        'local_lbplanner/' . sentry::CONFIG_ENVIRONMENT,
        get_string('settings_sentry_environment', 'local_lbplanner'),
        get_string('settings_sentry_environment_desc', 'local_lbplanner'),
        'production',
        [
            'production' => get_string('settings_sentry_environment_production', 'local_lbplanner'),
            'development' => get_string('settings_sentry_environment_development', 'local_lbplanner'),
        ]
    ));
    $settings->add(new admin_setting_configselect(
        'local_lbplanner/' . sentry::CONFIG_TRACES_SAMPLE_RATE,
        get_string('settings_sentry_traces_sample_rate', 'local_lbplanner'),
        get_string('settings_sentry_traces_sample_rate_desc', 'local_lbplanner'),
        '0.2',
        [
            '0' => '0%',
            '0.05' => '5%',
            '0.1' => '10%',
            '0.2' => '20%',
            '0.5' => '50%',
            '1' => '100%',
        ]
    ));
}
