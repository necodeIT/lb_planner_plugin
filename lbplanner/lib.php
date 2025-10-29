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
 * Defines callbacks for moodle
 *
 * @package local_lbplanner
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

use local_lbplanner\helpers\sentry_helper;

/**
 * Callback for any webservices that get called by external actors.
 * We use this to catch whenever anything from us is being called, and do sentry setup and error reporting.
 * @param stdClass $externalfunctioninfo external function info {@see external_api::external_function_info()}
 * @param array $params the raw(ish) parameters that are going to get passed to the function implementing the API call
 * @return mixed Either whatever the API call returned, or false if we don't wish to override anything.
 */
function local_lbplanner_override_webservice_execution(stdClass $externalfunctioninfo, array $params): mixed {
    // Only override calling our own functions.
    if ($externalfunctioninfo->component === 'local_lbplanner') {
        sentry_helper::init();
        // Actually calling the function (since we're overriding this part, duh).
        try {
            $callable = [$externalfunctioninfo->classname, $externalfunctioninfo->methodname];
            $transaction = sentry_helper::transaction_start(...$callable);
            $result = call_user_func_array($callable, $params);
            sentry_helper::transaction_end($transaction);

            // Report if call_user_func_array itself had some kind of issue.
            if ($result === false) {
                $paramsstring = var_export($params, true);
                throw new \coding_exception(
                    "webservice override: call_user_func_array returned with false at "
                    .$externalfunctioninfo->classname."::".$externalfunctioninfo->methodname."(".$paramsstring.");"
                );
            }

            return $result;
        } catch (\Throwable $e) {
            sentry_helper::report_err($e);
            throw $e;
        }
    }

    return false;
}
