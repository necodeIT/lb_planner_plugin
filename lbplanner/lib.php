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
 * Runtime hooks for LB Planner observability.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\observability\sentry;

/**
 * Instrument every LB Planner external function with Sentry.
 *
 * @param stdClass $externalfunctioninfo Moodle external-function metadata.
 * @param array $params Validated external-function arguments.
 * @return mixed Function result, or false when this hook does not handle the component.
 */
function local_lbplanner_override_webservice_execution(stdClass $externalfunctioninfo, array $params): mixed {
    if (($externalfunctioninfo->component ?? null) !== 'local_lbplanner') {
        return false;
    }

    $callable = [$externalfunctioninfo->classname, $externalfunctioninfo->methodname];
    $transactionname = $externalfunctioninfo->classname . '::' . $externalfunctioninfo->methodname;
    return sentry::run(
        $transactionname,
        'webservice.server',
        static fn(): mixed => call_user_func_array($callable, $params)
    );
}
