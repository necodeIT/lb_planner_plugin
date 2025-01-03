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

use \core\context\course as context_course;
use dml_exception;

use local_lbplanner\model\course;

/**
 * Helper class for courses
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2024 NecodeIT
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class course_helper {

    /**
     * The course table used by the LP
     */
    const LBPLANNER_COURSE_TABLE = 'local_lbplanner_courses';

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
     * Get the current school year from the config
     * Definition of a school year: 2020/2021
     * Check in config_helper.php for more info how the date is set for defaultactiveyear
     *
     * @return string the current year the last 2 digits (20/20)
     * @throws dml_exception
     */
    public static function get_current_year(): string {
        if (strpos(get_config('local_lbplanner', 'activeyear'), '/') !== false) {
            return get_config('local_lbplanner', 'activeyear');
        }
        return get_config('local_lbplanner', 'defaultactiveyear');
    }

    /**
     * Get course from lbpanner DB
     *
     * @param int $courseid id of the course in lbplanner
     * @param int $userid   id of the user
     *
     * @return course course from lbplanner
     * @throws dml_exception
     */
    public static function get_lbplanner_course(int $courseid, int $userid): course {
        global $DB;
        return course::from_db($DB->get_record(self::LBPLANNER_COURSE_TABLE, ['courseid' => $courseid, 'userid' => $userid]));
    }

    /**
     * Get all the courses of the current year.
     * @return course[] all courses of the current year
     */
    public static function get_all_lbplanner_courses(): array {
        global $DB, $USER;
        $userid = $USER->id;

        $mdlcourses = enrol_get_my_courses();
        // Remove Duplicates.
        $mdlcourses = array_unique($mdlcourses, SORT_REGULAR);
        // Check this out: https://www.youtube.com/watch?v=WmdAk2zyQkU .
        $results = [];

        foreach ($mdlcourses as $mdlcourse) {
            $courseid = $mdlcourse->id;
            // Check if the course is from the current year.
            // TODO: pass fullname to function instead of courseid.
            if (!self::check_current_year($courseid)) {
                    continue;
            }
            // Check if the course is already in the LB Planner database.
            if ($DB->record_exists(self::LBPLANNER_COURSE_TABLE, ['courseid' => $courseid, 'userid' => $userid])) {
                $fetchedcourse = self::get_lbplanner_course($courseid, $userid);
            } else {
                // IF not create an Object to be put into the LB Planner database.
                $fetchedcourse = new course(
                    0, $courseid, $userid,
                    course::prepare_shortname($mdlcourse->shortname),
                    self::COLORS[array_rand(self::COLORS)],
                    false,
                );
                $fetchedcourse->set_fresh(
                    $DB->insert_record(
                        self::LBPLANNER_COURSE_TABLE,
                        $fetchedcourse->prepare_for_db()
                    )
                );
            }
            // Add name to fetched Course.
            $fetchedcourse->set_fullname($mdlcourse->fullname);
            array_push($results, $fetchedcourse);
        }
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

    /**
     * gets the fullname from a course
     *
     * @param int $courseid the course id
     *
     * @return string the fullname of the course
     * @throws dml_exception
     */
    public static function get_fullname(int $courseid): string {
        return get_course($courseid)->fullname;
    }

    /**
     * Check if the course is from the current year
     *
     * @param int $courseid the course id
     *
     * @return bool true if the course is from the current year
     * @throws dml_exception
     */
    public static function check_current_year(int $courseid): bool {
        return strpos(self::get_fullname($courseid), self::get_current_year()) !== false;
    }
}
