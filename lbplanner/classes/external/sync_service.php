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

namespace local_lbplanner\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use local_lbplanner\sync\contract;

/**
 * Shared security and return structures for sync external functions.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
abstract class sync_service extends external_api {
    /**
     * All contract-v1 functions take no parameters.
     *
     * @return external_function_parameters Empty parameter structure.
     */
    public static function no_parameters(): external_function_parameters {
        return new external_function_parameters([]);
    }

    /**
     * Validate token-owner access.
     *
     * @return void
     */
    public static function require_access(): void {
        $context = \context_system::instance();
        self::validate_context($context);
        if (!isloggedin() || isguestuser()) {
            throw new \moodle_exception('err_sync_unauthorized', 'local_lbplanner');
        }
        require_capability(contract::CAPABILITY, $context);
    }

    /**
     * Validate a Moodle resource context against web-service restrictions.
     *
     * @param \context $context Resource context.
     * @return void
     */
    public static function validate_resource_context(\context $context): void {
        self::validate_context($context);
    }

    /**
     * Define the identity return structure.
     *
     * @return external_single_structure Identity return structure.
     */
    public static function identity_structure(): external_single_structure {
        return new external_single_structure([
            'siteUrl' => new external_value(PARAM_URL, 'Canonical HTTPS Moodle site URL'),
            'remoteUserId' => new external_value(PARAM_TEXT, 'Token owner Moodle ID encoded as a decimal string'),
            'displayLabel' => new external_value(PARAM_TEXT, 'Safe token-owner display label'),
            'pluginRelease' => new external_value(PARAM_TEXT, 'Installed plugin release'),
            'contractVersion' => new external_value(PARAM_INT, 'Sync contract version'),
            'supportedResources' => new external_multiple_structure(
                new external_value(PARAM_ALPHA, 'Supported sync resource')
            ),
        ]);
    }

    /**
     * Define the course-array return structure.
     *
     * @return external_multiple_structure Course-array return structure.
     */
    public static function courses_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'externalId' => new external_value(PARAM_TEXT, 'Moodle course ID encoded as a decimal string'),
            'name' => new external_value(PARAM_TEXT, 'Course name'),
            'createdAt' => new external_value(PARAM_INT, 'Course creation time as UTC Unix seconds'),
            'startDate' => new external_value(PARAM_INT, 'Course start time as UTC Unix seconds'),
            'endDate' => new external_value(
                PARAM_INT,
                'Course end time as UTC Unix seconds, or null',
                VALUE_REQUIRED,
                null,
                NULL_ALLOWED
            ),
            'url' => new external_value(PARAM_URL, 'Absolute Moodle course URL'),
            'groups' => new external_multiple_structure(new external_single_structure([
                'externalId' => new external_value(PARAM_TEXT, 'Moodle group ID encoded as a decimal string'),
                'name' => new external_value(PARAM_TEXT, 'Group name'),
            ])),
        ]));
    }

    /**
     * Define the assignment-or-quiz-array return structure.
     *
     * @return external_multiple_structure Activity-array return structure.
     */
    public static function activities_structure(): external_multiple_structure {
        return new external_multiple_structure(new external_single_structure([
            'externalId' => new external_value(PARAM_TEXT, 'Activity instance ID encoded as a decimal string'),
            'courseExternalId' => new external_value(PARAM_TEXT, 'Course ID encoded as a decimal string'),
            'courseModuleId' => new external_value(PARAM_TEXT, 'Course-module ID encoded as a decimal string'),
            'name' => new external_value(PARAM_TEXT, 'Activity name'),
            'description' => new external_value(PARAM_RAW, 'Sanitized Moodle-formatted HTML description'),
            'deadline' => new external_value(
                PARAM_INT,
                'Teacher deadline as UTC Unix seconds, or null',
                VALUE_REQUIRED,
                null,
                NULL_ALLOWED
            ),
            'url' => new external_value(PARAM_URL, 'Absolute Moodle activity URL'),
            'completionState' => new external_value(PARAM_INT, 'Moodle completion state'),
            'submitted' => new external_value(PARAM_BOOL, 'Whether the assignment is submitted'),
            'finishedAttempt' => new external_value(PARAM_BOOL, 'Whether the quiz has a finished attempt'),
            'classificationKey' => new external_value(
                PARAM_ALPHANUM,
                'GK, EK, TEST, M, or null for no selection',
                VALUE_REQUIRED,
                null,
                NULL_ALLOWED
            ),
        ]));
    }
}
