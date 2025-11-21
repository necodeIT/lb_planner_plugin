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

/**
 * enum for setting keys
 *
 * @package local_lbplanner
 * @subpackage enums
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\enums;

// TODO: revert to native enums once we migrate to php8.

use local_lbplanner\polyfill\Enum;

/**
 * The keys for plugin settings/configs
 */
class SETTINGS extends Enum {
    /**
     * Key for the release version.
     * NOTE: This is a constant! Do not set outside version.php under ANY circumstances!
     */
    const V_RELEASE = 'release';
    /**
     * Key for the full version number in $plugin->version.
     * NOTE: This is a constant! Do not set outside version.php under ANY circumstances!
     */
    const V_FULLNUM = 'release_fullnum';
    /**
     * Key for the panic / fulloff button.
     */
    const PANIC = 'panic';
    /**
     * Key for the setting for how many days into the future a student should be able to reserve a slot.
     */
    const SLOT_FUTURESIGHT = 'slot_futuresight';
    /**
     * Key for the setting for how long a course should be expired for until it counts as outdated and gets culled.
     */
    const COURSE_OUTDATERANGE = 'course_outdaterange';
    /**
     * Key for the setting for where sentry events should be sent to.
     */
    const SENTRY_DSN = 'sentry_dsn';
    /**
     * Key for the sentry environment.
     */
    const SENTRY_ENV = 'sentry_environment';
    /**
     * Key for the custom field category ID.
     */
    const CF_CATID = 'categoryid';
}
