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
 * English strings for local_lbplanner.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cf_description'] = 'Classifies the activity as GK, EK, TEST, or M for EduPlanner sync';
$string['cf_name'] = 'LB Planner activity type';
$string['err_sentry_internal'] = 'LB Planner could not send observability data to Sentry';
$string['err_sentry_missingvendor'] = 'Sentry is configured, but the bundled Composer dependencies are missing';
$string['err_sync_classificationconfig'] = 'The LB Planner activity classification field is not configured correctly';
$string['err_sync_duplicateidentity'] = 'The Moodle sync snapshot contains a duplicate external identity';
$string['err_sync_invalidreference'] = 'The Moodle sync snapshot contains an invalid reference';
$string['err_sync_invalidsiteurl'] = 'The canonical Moodle site URL must be a valid HTTPS URL';
$string['err_sync_unauthorized'] = 'A logged-in, non-guest user is required for Moodle sync';
$string['err_sync_unsupportedclassification'] = 'An activity has an unsupported LB Planner classification';
$string['lb_planner:sync'] = 'Synchronize my Moodle data with EduPlanner';
$string['pluginname'] = 'LB Planner';
$string['settings_sentry_dsn'] = 'Sentry DSN';
$string['settings_sentry_dsn_desc'] = 'Leave empty to disable Sentry error and performance reporting.';
$string['settings_sentry_environment'] = 'Sentry environment';
$string['settings_sentry_environment_desc'] = 'Environment attached to LB Planner Sentry events and transactions.';
$string['settings_sentry_environment_development'] = 'Development';
$string['settings_sentry_environment_production'] = 'Production';
$string['settings_sentry_traces_sample_rate'] = 'Sentry trace sample rate';
$string['settings_sentry_traces_sample_rate_desc'] = 'Percentage of LB Planner sync requests sent as performance traces.';
$string['settings_title'] = 'LB Planner Sync API';
