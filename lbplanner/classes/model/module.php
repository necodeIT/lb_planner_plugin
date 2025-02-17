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
 * Model for a module
 *
 * @package local_lbplanner
 * @subpackage model
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;

use core_external\{external_single_structure, external_value};
use local_lbplanner\enums\{MODULE_GRADE, MODULE_STATUS, MODULE_TYPE};
use local_lbplanner\helpers\modules_helper;
use local_lbplanner\helpers\plan_helper;

/**
 * Model class for model
 */
class module {
    /**
     * @var ?int $cmid the course module ID
     */
    private ?int $cmid;
    /**
     * @var int $assignid the assignment ID. Old code might refer to this ID as the "module ID", but this terminology is deprecated.
     */
    private ?int $assignid;
    /**
     * @var ?int $type the module type
     */
    private ?int $type;
    /**
     * @var ?\stdClass $assignobj the DB object for the associated assignment
     */
    private ?\stdClass $assignobj;
    /**
     * @var ?\stdClass $cmobj the DB object for the associated course module
     */
    private ?\stdClass $cmobj;
    /**
     * @var int[int] $status map of the status of the module for a specific user's ID
     * @see \local_lbplanner\enums\MODULE_STATUS
     */
    private array $status;
    /**
     * @var \stdClass[int] $deadline cached deadlines per-planid
     */
    private array $deadlines;
    /**
     * @var int[int] $grades cached grades per-userid
     */
    private array $grades;

    /**
     * Constructs a new course
     * This is an internal function that prepares the object for initialization via a ::from_*() function.
     */
    private function __construct() {
        $this->cmid = null;
        $this->assignid = null;
        $this->type = null;
        $this->assignobj = null;
        $this->cmobj = null;
        $this->status = [];
        $this->deadlines = [];
        $this->grades = [];
    }

    /**
     * Creates a module object from the assignment ID.
     * @param int $id the assignment ID
     * @return module a module object with filled-in assignment ID
     */
    public static function from_assignid(int $id): self {
        $obj = new self();
        $obj->assignid = $id;
        return $obj;
    }

    /**
     * Creates a module object from the assignment ID.
     * @param \stdClass $assignobj the assignment object from moodle's DB
     * @return module a module object with filled-in assignment ID
     */
    public static function from_assignobj(\stdClass $assignobj): self {
        $obj = new self();
        $obj->assignobj = $assignobj;
        $obj->assignid = $assignobj->id;
        return $obj;
    }

    /**
     * Fetches the necessary caches and returns the assignment ID
     * @return int assign ID
     */
    public function get_assignid(): int {
        if ($this->assignid === null) {
            if ($this->cmid !== null) {
                $this->assignid = $this->get_cmobj()['instance'];
            } else {
                throw new \coding_exception('requested assignid, but no assignid');
            }
        }
        return $this->assignid;
    }

    /**
     * Fetches the necessary caches and returns the assignment ID
     * @return int assign ID
     */
    public function get_cmid(): int {
        if ($this->cmid === null) {
            $cm = $this->get_cmobj();
            $this->cmid = $cm->id;
        }
        return $this->cmid;
    }

    /**
     * Fetches the necessary caches and returns the assignment object
     * @return \stdClass assignobj
     */
    public function get_assignobj(): \stdClass {
        global $DB;
        if ($this->assignobj === null) {
            $this->assignobj = $DB->get_record(
                modules_helper::ASSIGN_TABLE,
                ['id' => $this->get_assignid()]
            );
        }
        return $this->assignobj;
    }

    /**
     * Fetches the necessary caches and returns the course module object
     * @return \stdClass cmobj
     */
    public function get_cmobj(): \stdClass {
        global $DB;
        if ($this->cmobj === null) {
            if ($this->cmid !== null) {
                $res = $DB->get_record(
                    modules_helper::COURSE_MODULES_TABLE,
                    ['id' => $this->cmid]
                );
                if ($res === false) {
                    throw new \moodle_exception("couldn't get course module with cmid {$this->cmid}");
                }
            } else {
                if ($this->assignid === null) {
                    throw new \coding_exception('tried to query cmid on a module object without assignid');
                }
                $courseid = $this->get_courseid();
                $res = $DB->get_record(
                    modules_helper::COURSE_MODULES_TABLE,
                    [
                        'course' => $courseid,
                        'instance' => $this->assignid,
                        'module' => 1,
                    ]
                );
                if ($res === false) {
                    throw new \moodle_exception("couldn't get course module with assignid {$this->assignid} and courseid {$courseid}");
                }
            }
            $this->cmobj = $res;
        }
        return $this->cmobj;
    }

    /**
     * Fetches the necessary caches and returns the name.
     * @return string name
     */
    public function get_name(): string {
        return $this->get_assignobj()->name;
    }

    /**
     * Fetches the necessary caches and returns the teacher-defined due date for this module.
     * If the module doesn't have any duedate, returns null.
     * @return ?int duedate
     */
    public function get_duedate(): ?int {
        $assignobj = $this->get_assignobj();
        return $assignobj->duedate > 0 ? $assignobj->duedate : null;
    }

    /**
     * Fetches the necessary caches and returns the course ID.
     * @return int course ID
     */
    public function get_courseid(): int {
        $viacm = false;
        // Try to take path of least cache misses to get course ID.
        if ($this->assignobj === null) {
            if ($this->cmobj !== null) {
                $viacm = true;
            } else {
                if ($this->cmid !== null) {
                    $viacm = true;
                } else if ($this->cmid === null) {
                    throw new \coding_exception('invalid module model: neither cmid nor assignid defined');
                }
            }
        }
        if ($viacm) {
            return intval($this->get_cmobj()->course);
        } else {
            return intval($this->get_assignobj()->course);
        }
    }

    /**
     * Fetches the necessary caches and returns the module status.
     * @param int $userid ID of user to request status for
     * @param ?int $planid the planid of the user or null (param exists purely to deduplicate DB calls)
     * @return int status
     * @see \local_lbplanner\enums\MODULE_STATUS
     */
    public function get_status(int $userid, ?int $planid = null): int {
        if (!array_key_exists($userid, $this->status)) {
            $this->status[$userid] = modules_helper::get_module_status($this, $userid, $planid);
        }
        return $this->status[$userid];
    }

    /**
     * Fetches the necessary caches and returns the module type.
     * @return int module type
     */
    public function get_type(): int {
        if ($this->type === null) {
            $this->type = modules_helper::determine_type($this->get_cmid());
        }
        return $this->type;
    }

    /**
     * Fetches the necessary caches and returns the deadline.
     * @param int $planid ID of plan to request deadline for
     * @return ?\stdClass deadline object
     */
    public function get_deadline(int $planid): ?\stdClass {
        global $DB;
        if (!array_key_exists($planid, $this->deadlines)) {
            $deadline = $DB->get_record(plan_helper::DEADLINES_TABLE, ['planid' => $planid, 'moduleid' => $this->get_assignid()]);
            $this->deadlines[$planid] = $deadline !== false ? $deadline : null;
        }
        return $this->deadlines[$planid];
    }

    /**
     * Fetches the necessary caches and returns the grade.
     * @param int $userid ID of the user to request grade for
     * @return ?int grade
     * @see \local_lbplanner\enums\MODULE_GRADE
     */
    public function get_grade(int $userid): ?int {
        global $DB;
        if (!array_key_exists($userid, $this->grades)) {
            $grade = null;
            $assignid = $this->get_assignid();

            if ($DB->record_exists(modules_helper::GRADES_TABLE, ['assignment' => $assignid, 'userid' => $userid])) {
                $moduleboundaries = $DB->get_record(modules_helper::GRADE_ITEMS_TABLE, ['iteminstance' => $assignid]);

                $mdlgrades = $DB->get_records(
                    modules_helper::GRADES_TABLE,
                    ['assignment' => $assignid, 'userid' => $userid]
                );

                $mdlgrade = end($mdlgrades);

                if ($mdlgrade->grade > 0) {

                    $grade  = modules_helper::determine_uinified_grade(
                        $mdlgrade->grade,
                        $moduleboundaries->grademax,
                        $moduleboundaries->gradepass
                    );
                }
            }

            $this->grades[$userid] = $grade;
        }

        return $this->grades[$userid];
    }

    /**
     * Prepares full user-specific data for the API endpoint.
     * @param int $userid ID of the user to see this module in context of
     * @param ?int $planid the planid of the user or null (param exists purely to deduplicate DB calls)
     * @return array a shortened representation of this user and its data
     */
    public function prepare_for_api_personal(int $userid, ?int $planid = null): array {
        return [
            'assignid' => $this->get_assignid(),
            'cmid' => $this->get_cmid(),
            'name' => $this->get_name(),
            'courseid' => $this->get_courseid(),
            'status' => $this->get_status($userid, $planid),
            'type' => $this->get_type(),
            'grade' => $this->get_grade($userid),
            'duedate' => $this->get_duedate(),
        ];
    }

    /**
     * Returns the full user-specific data structure for the API.
     *
     * @return external_single_structure The full user-specific data structure for the API.
     */
    public static function api_structure_personal(): external_single_structure {
        return new external_single_structure(
            [
                'assignid' => new external_value(PARAM_INT, 'Assignment ID (formerly "module ID")'),
                'cmid' => new external_value(PARAM_INT, 'Course module ID'),
                'name' => new external_value(PARAM_TEXT, 'Shortened module name (max. 5 chars)'),
                'courseid' => new external_value(PARAM_INT, 'Course ID'),
                'status' => new external_value(PARAM_INT, 'Module status '.MODULE_STATUS::format()),
                'type' => new external_value(PARAM_INT, 'Module type '.MODULE_TYPE::format()),
                'grade' => new external_value(PARAM_INT, 'The grade of the module '.MODULE_GRADE::format()),
                'duedate' => new external_value(PARAM_INT, 'The deadline of the module set by the teacher'),
            ]
        );
    }
}
