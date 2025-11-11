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

use core\context\course as context_course;
use core_tag_collection;
use core_tag_tag;
use DateTimeImmutable;
use dml_exception;
use dml_write_exception;
use local_lbplanner\model\course;

/**
 * Helper class for courses
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2025 Pallasys
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class course_helper {
    /**
     * The course table used by the LP
     */
    const EDUPLANNER_COURSE_TABLE = 'local_lbplanner_courses';

    /**
     * The tag name to identify courses as "should show up in eduplanner"
     */
    const EDUPLANNER_TAG = 'eduplanner';

    /**
     * A list of nice colors to choose from :)
     */
    const COLORS
        = [
            "#f50057",
            "#536dfe",
            "#f9a826",
            "#00bfa6",
            "#9b59b6",
            "#37bbca",
            "#e67e22",
            "#37CA48",
            "#CA3737",
            "#B5CA37",
            "#37CA9E",
            "#3792CA",
            "#376ECA",
            "#8B37CA",
            "#CA37B9",
        ];

    /**
     * Get course from lbpanner DB
     *
     * @param int $courseid id of the course in Eduplanner
     * @param int $userid   id of the user
     *
     * @return course course from Eduplanner
     * @throws dml_exception
     */
    public static function get_eduplanner_course(int $courseid, int $userid): course {
        global $DB;
        return course::from_db($DB->get_record(self::EDUPLANNER_COURSE_TABLE, ['courseid' => $courseid, 'userid' => $userid]));
    }

    /**
     * Get current eduplanner-enabled courses.
     * @param bool $onlyenrolled whether to include only courses in which the current user is enrolled in
     * @return course[] all courses of the current year
     */
    public static function get_eduplanner_courses(bool $onlyenrolled): array {
        global $DB, $USER;
        $userid = $USER->id;

        $sentryspan = sentry_helper::span_start(__FUNCTION__, ['onlyenrolled' => $onlyenrolled]);

        // TODO: rewrite this where it asks the DB for all lbp courses, and then for any mdlcourses that aren't in lbpc.

        $lbptag = core_tag_tag::get_by_name(core_tag_collection::get_default(), self::EDUPLANNER_TAG, strictness:MUST_EXIST);
        $courseexpireseconds = config_helper::get_course_outdatedrange();
        $courseexpiredate = (new DateTimeImmutable("{$courseexpireseconds} seconds ago"))->getTimestamp();
        $now = time();

        /* NOTE: We could use enrol_get_my_courses() and get_courses() here.
                 But their perf is so abysmal that we have to roll our own function.
                 The code is largely leaned on how these functions work internally, optimized for our purposes. */
        if ($onlyenrolled) {
            $mdlcourses = $DB->get_records_sql(
                "SELECT c.* FROM {course} c
                INNER JOIN {enrol} e ON e.courseid = c.id
                INNER JOIN {user_enrolments} ue ON (ue.enrolid = e.id AND ue.userid = :userid)
                INNER JOIN {tag_instance} ti ON (ti.itemid = c.id)
                WHERE
                    ue.status = :active
                AND e.status = :enabled
                AND ue.timestart <= :nowa
                AND (
                       ue.timeend >= :nowb
                    OR ue.timeend = 0
                )
                AND (
                       c.enddate > :courseexpiredate
                    OR c.enddate = 0
                )
                AND ti.tagid = :lbptagid
                AND ti.itemtype = 'course'",
                [
                    "userid" => $userid,
                    "active" => ENROL_USER_ACTIVE,
                    "enabled" => ENROL_INSTANCE_ENABLED,
                    "nowa" => $now,
                    "nowb" => $now,
                    "courseexpiredate" => $courseexpiredate,
                    "lbptagid" => $lbptag->id,
                ]
            );
        } else {
            $mdlcourses = $DB->get_records_sql(
                "SELECT c.* FROM {course} c
                INNER JOIN {tag_instance} ti ON (ti.itemid = c.id)
                WHERE (
                       c.enddate > :courseexpiredate
                    OR c.enddate = 0
                )
                AND ti.tagid = :lbptagid
                AND ti.itemtype = 'course'",
                [
                    "courseexpiredate" => $courseexpiredate,
                    "lbptagid" => $lbptag->id,
                ]
            );
        }
        // Remove Duplicates.
        $mdlcourses = array_unique($mdlcourses, SORT_REGULAR);
        $results = [];

        foreach ($mdlcourses as $mdlcourse) {
            $courseid = $mdlcourse->id;
            // Check if the course is already in the Eduplanner database.
            if ($DB->record_exists(self::EDUPLANNER_COURSE_TABLE, ['courseid' => $courseid, 'userid' => $userid])) {
                $fetchedcourse = self::get_eduplanner_course($courseid, $userid);
            } else {
                // IF not create an Object to be put into the Eduplanner database.
                $fetchedcourse = new course(
                    0,
                    $courseid,
                    $userid,
                    course::prepare_shortname($mdlcourse->shortname),
                    self::COLORS[array_rand(self::COLORS)],
                    false,
                );
                try {
                    $fetchedcourse->set_fresh(
                        $DB->insert_record(
                            self::EDUPLANNER_COURSE_TABLE,
                            $fetchedcourse->prepare_for_db()
                        )
                    );
                } catch (dml_write_exception $e) {
                    var_dump($fetchedcourse->prepare_for_db());
                    throw $e;
                }
            }
            // Add mdlcourse to fetched Course.
            $fetchedcourse->set_mdlcourse($mdlcourse);
            array_push($results, $fetchedcourse);
        }

        $sentryspan->setData(['count_out' => count($results)]);
        sentry_helper::span_end($sentryspan);
        return $results;
    }

    /**
     * Check if the user is enrolled in the course
     *
     * @param int $courseid course id
     * @param int $userid   user id
     *
     * @return bool true if the user is enrolled
     */
    public static function check_access(int $courseid, int $userid): bool {
        $context = context_course::instance($courseid);
        if ($context === false) {
            return false;
        }
        return is_enrolled($context, $userid, '', true);
    }
}
