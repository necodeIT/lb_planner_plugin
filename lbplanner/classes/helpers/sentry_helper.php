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

use Throwable;

use local_lbplanner\helpers\config_helper;

require_once($CFG->dirroot.'/local/lbplanner/vendor/autoload.php');

/**
 * Provides helper methods for sentry logging
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class sentry_helper {
    /**
     * Checks if moodle plugin is set to report exceptions to sentry
     * @return bool whether sentry is to be used
     */
    public static function enabled(): bool {
        return strlen(config_helper::get_sentry_dsn()) > 0;
    }
    /**
     * Initializes the sentry library for future use.
     */
    public static function init(): void {
        if (self::enabled()) {
            $cfg = [ // TODO: look at all the config options
                "dsn" => config_helper::get_sentry_dsn(),
                "enable_tracing" => true,
                "attach_stacktrace" => true,
            ];
            \Sentry\init($cfg);
        }
    }
    /**
     * Checks if any errors happened since init() and reports the most recent one to sentry if so.
     * @return whether any errors were found
     */
    public static function report_last_err(): bool {
        if (self::enabled()) {
            return \Sentry\captureLastError() !== null;
        }
        return false;
    }
    /**
     * Reports an error to sentry
     * @param Throwable $err the error
     */
    public static function report_err(Throwable $err): void {
        if (self::enabled()) {
            \Sentry\captureException($err);
        }
    }
}