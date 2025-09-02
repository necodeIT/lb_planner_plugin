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
 * Defines versioning
 *
 * @package local_lbplanner
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->requires = 2024042200.00; // Require Moodle >=4.4.0.
$plugin->maturity = MATURITY_BETA;
$plugin->component = 'local_lbplanner';
$plugin->release = '1.0.2';
$plugin->version = 202509020000;
$plugin->dependencies = [
    // Depend upon version 2023110600 of local_modcustomfields.
    'local_modcustomfields' => 2023110600,
];

set_config('release', $plugin->release, 'local_lbplanner');
