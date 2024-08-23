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
 * Model for a course
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\model;

use external_single_structure;
use external_value;

/**
 * Model class for course
 */
class course {
    /**
     * @var int $id course ID
     */
    public int $id;
    /**
     * @var int $courseid the moodle-internal ID of the course
     */
    public int $courseid;
    /**
     * @var int $userid the user for whom these course settings are for
     */
    public int $userid;
    /**
     * @var string $shortname the short name of this course for this user
     * maximum size: 5 chars
     */
    public string $shortname;
    /**
     * @var string $color the color for this course
     * maximum size: 10 chars
     * TODO: what is the format of this ?? RRGGBB is just 6 chars
     */
    public string $color;
    /**
     * @var bool $enabled whether the user wants to see this course
     */
    public bool $enabled;

    /**
     * Constructs a new course
     * @param int $id ID of course
     * @param int $courseid ID of the moodle course
     * @param int $userid ID of the user these settings are for
     * @param string $shortname the short name for this course
     * @param string $color the color for this course
     * @param bool $enabled whether the course is enabled
     */
    public function __construct(int $id, int $courseid, int $userid, string $shortname, string $color, bool $enabled) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->userid = $userid;
        assert(strlen($shortname) <= 5);
        assert(strlen($shortname) > 0);
        $this->shortname = $shortname;
        assert(strlen($color) <= 10);
        // TODO: check color format.
        $this->color = $color;
        $this->enabled = $enabled;
    }

    /**
     * Takes data from DB and makes a new Course out of it
     *
     * @return object a representation of this course and its data
     */
    public static function from_db(object $obj): self {
        assert($obj->enabled === 0 || $obj->enabled === 1);
        return new self($obj->id, $obj->courseid, $obj->userid, $obj->shortname, $obj->color, (bool) $obj->enabled);
    }

    /**
     * Prepares data for the DB endpoint.
     *
     * @return object a representation of this course and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->id = $this->id;
        $obj->courseid = $this->courseid;
        $obj->userid = $this->userid;
        $obj->shortname = $this->shortname;
        $obj->color = $this->color;
        $obj->enabled = $this->enabled ? 1:0; // The DB uses int instead of bool here.

        return $obj;
    }

    /**
     * Prepares data for the API endpoint.
     *
     * @return array a representation of this course and its data
     */
    public function prepare_for_api(): array {
        return [
            'id' => $this->id,
            'courseid' => $this->courseid,
            'userid' => $this->userid,
            'shortname' => $this->shortname,
            'color' => $this->color,
            'enabled' => $this->enabled ? 1:0, // Moodle's API uses int instead of bool.
        ];
    }

    /**
     * Returns the data structure of a course for the API.
     *
     * @return external_single_structure The data structure of a course for the API.
     */
    public static function api_structure(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'course ID'),
                'courseid' => new external_value(PARAM_INT, 'the moodle-internal ID of the course'),
                'userid' => new external_value(PARAM_INT, 'the user for whom these course settings are for'),
                'shortname' => new external_value(PARAM_TEXT, 'the short name of this course for this user (maximum size: 5 chars)'),
                'color' => new external_value(PARAM_TEXT, 'the color for this course (maximum size: 10 chars)'), // TODO: describe format
                'enabled' => new external_value(PARAM_BOOL, 'whether the user wants to see this course'),
            ]
        );
    }
}
