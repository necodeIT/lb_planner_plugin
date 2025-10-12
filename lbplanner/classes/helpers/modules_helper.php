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
 * Collection of helper classes for handling modules
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\helpers;

use coding_exception;
use core_customfield\category_controller;
use DateTimeImmutable;
use DateTimeZone;
use local_lbplanner\enums\{MODULE_STATUS, MODULE_GRADE, MODULE_TYPE};
use local_lbplanner\model\module;

/**
 * Contains helper functions for working with modules.
 */
class modules_helper {
    /**
     * Table where modules are stored.
     */
    const ASSIGN_TABLE = 'assign';

    /**
     * Table where max. and min. grades of the modules are stored.
     */
    const GRADE_ITEMS_TABLE = 'grade_items';

    /**
     * Table where course modules are stored.
     */
    const COURSE_MODULES_TABLE = 'course_modules';

    /**
     * Table where grades of the modules are stored.
     */
    const GRADES_TABLE = 'assign_grades';

    /**
     * Table where grading scales are stored.
     */
    const SCALE_TABLE = 'scale';

    /**
     * Table where submissions of the modules are stored.
     */
    const SUBMISSIONS_TABLE = 'assign_submission';

    /**
     * Submitted status name of a submission.
     */
    const SUBMISSION_STATUS_SUBMITTED = 'submitted';

    /**
     * Determins the enum value for a grade.
     * TODO: this is bullshit.
     *
     * @param int $grade The grade of the module.
     * @param int $maxgrade The max. grade of the module.
     * @param int $gradepass The grade to pass the module.
     * @return integer The enum value for the grade.
     */
    public static function determine_uinified_grade(int $grade, int $maxgrade, int $gradepass): int {
        if ($maxgrade <= $gradepass) {
            return ($grade >= $gradepass) ? MODULE_GRADE::GKV : MODULE_GRADE::RIP;
        }

        $p = ($grade - $gradepass) / ($maxgrade - $gradepass);

        if ($p >= 0.75) {
            return MODULE_GRADE::EKV;
        } else if ($p >= 0.50) {
            return MODULE_GRADE::EK;
        } else if ($p >= 0.25) {
            return MODULE_GRADE::GKV;
        } else if ($p >= 0) {
            return MODULE_GRADE::GK;
        } else {
            return MODULE_GRADE::RIP;
        }
    }

    /**
     * Checks what type the module is.
     *
     * @param int $cmid The course module ID associated with the module.
     * @return int The enum value for the module type.
     * @throws \moodle_exception
     */
    public static function determine_type(int $cmid): int {
        $catid = config_helper::get_category_id();
        if ($catid === -1) {
            throw new \moodle_exception('couldn\'t find custom fields category ID');
        }
        $categorycontroller = category_controller::create($catid);
        $instancedata = $categorycontroller->get_handler()->get_instance_data($cmid);
        if (count($instancedata) === 0) {
            throw new \moodle_exception("couldn't find any instance data for module ID {$cmid} in category ID {$catid}");
        } else if (count($instancedata) > 1) {
            throw new \moodle_exception("found multiple data for module ID {$cmid} in category ID {$catid}");
        }
        $type = intval($instancedata[array_key_last($instancedata)]->get_value()) - 1;
        if ($type === -1) {
            $type = MODULE_TYPE::GK; // Default is GK if nothing is selected.
        }
        MODULE_TYPE::name_from($type); // Basically asserting that this value exists as a module type.
        return $type;
    }

    /**
     * Returns status of a module
     * @param module $module the module to query for
     * @param int $userid the userid to see this in context of
     * @param ?int $planid the planid of the user or null (param exists purely to deduplicate DB calls)
     * @return int the module status
     * @see \local_lbplanner\enums\MODULE_STATUS
     */
    public static function get_module_status(module $module, int $userid, ?int $planid = null): int {
        global $DB;

        $grade = $module->get_grade($userid);
        if ($grade !== null && $grade !== MODULE_GRADE::RIP) {
            return MODULE_STATUS::DONE;
        }

        // Getting some necessary data.
        $assignid = $module->get_assignid();

        // Check if there are any submissions or feedbacks for this module.

        if ($DB->record_exists(self::SUBMISSIONS_TABLE, ['assignment' => $assignid, 'userid' => $userid])) {
            $submission = $DB->get_record(
                self::SUBMISSIONS_TABLE,
                ['assignment' => $assignid, 'userid' => $userid]
            );

            if (strval($submission->status) === self::SUBMISSION_STATUS_SUBMITTED) {
                return MODULE_STATUS::UPLOADED;
            }
        }

        // Check if the module is late.

        if ($planid === null) {
            $planid = plan_helper::get_plan_id($userid);
        }

        $deadline = $module->get_deadline($planid);

        if ($deadline !== null) {
            $utctz = new DateTimeZone('UTC');
            $now = (new DateTimeImmutable('yesterday', $utctz));
            // Take timestamp and remove time from it.
            $deadlineend = $now->setTimestamp(intval($deadline->deadlineend))->setTime(0, 0, 0, 0);
            if ($now->diff($deadlineend)->invert === 1) {
                return MODULE_STATUS::LATE;
            }
        }

        return MODULE_STATUS::PENDING;
    }

    /**
     * Returns all modules for the given course id.
     *
     * @param int $courseid The id of the course.
     * @param bool $ekenabled Whether EK modules should be included.
     * @return module[] The modules.
     */
    public static function get_all_modules_by_course(int $courseid, bool $ekenabled): array {
        global $DB;

        $assignments = $DB->get_records(self::ASSIGN_TABLE, ['course' => $courseid]);

        $modules = [];

        foreach ($assignments as $assign) {
            if ($assign === null) {
                throw new coding_exception("what the fuck? 1 {$courseid} {$ekenabled}");
            }
            $module = module::from_assignobj($assign);
            if ((!$ekenabled) && $module->get_type() === MODULE_TYPE::EK) {
                continue;
            }
            array_push($modules, $module);
        }

        return $modules;
    }
}
