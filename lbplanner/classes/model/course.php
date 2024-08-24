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
use local_lbplanner\helpers\course_helper;

/**
 * Model class for course
 */
class course {
    /**
     * @var int $id course ID
     */
    private int $id;
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
     * @var string $fullname the full name of this course
     */
    private ?string $fullname;
    /**
     * @var string $color the color for this course as #RRGGBB
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
     * @param string $color the color for this course as #RRGGBB
     * @param bool $enabled whether the course is enabled
     */
    public function __construct(int $id, int $courseid, int $userid, string $shortname, string $color, bool $enabled) {
        $this->id = $id;
        $this->courseid = $courseid;
        $this->userid = $userid;
        $this->set_shortname($shortname);
        $this->set_color($color);
        $this->enabled = $enabled;
        $this->fullname = null;
    }

    /**
     * Takes data from DB and makes a new Course out of it
     *
     * @param object $obj the DB object to get data from
     * @return object a representation of this course and its data
     * @throws \AssertionError
     */
    public static function from_db(object $obj): self {
        assert($obj->enabled === 0 || $obj->enabled === 1);
        return new self($obj->id, $obj->courseid, $obj->userid, $obj->shortname, $obj->color, (bool) $obj->enabled);
    }

    /**
     * Mark the object as freshly created and sets the new ID
     * @param int $id the new ID after inserting into the DB
     * @throws \AssertionError
     */
    public function set_fresh(int $id) {
        assert($this->id === 0);
        assert($id !== 0);
        $this->id = $id;
    }

    /**
     * sets the color as #RRGGBB
     * @param string $color the color
     * @throws \AssertionError
     */
    public function set_color(string $color) {
        // TODO: check color format.
        assert(strlen($color) <= 10);
        $this->color = $color;
    }

    /**
     * sets the shortname
     * @param string $shortname the shortname
     * @throws \AssertionError
     */
    public function set_shortname(string $shortname) {
        assert(strlen($shortname) <= 5);
        assert(strlen($shortname) > 0);
        $this->shortname = $shortname;
    }

    /**
     * sets whether the course is enabled
     * @param bool $enabled whether to enable the course
     */
    public function set_enabled(bool $enabled) {
        $this->enabled = $enabled;
    }

    /**
     * sets the cached fullname (mainly for deduplicating DB requests)
     * @param string $fullname the cached fullname
     */
    public function set_fullname(string $fullname) {
        // If we already have a fullname stored and it's different, then we've found data inconsistency.
        assert($this->fullname === null || $this->fullname === $fullname);
        $this->fullname = $fullname;
    }

    /**
     * get the cached fullname
     * @return string the cached fullname
     */
    public function get_fullname(): string {
        if ($this->fullname === null) {
            $this->fullname = course_helper::get_fullname($this->courseid);
        }

        return $this->fullname;
    }

    /**
     * Prepares a string to be eligible for shortname
     * @param string $shortname the shortname to be prepared
     * @return string the prepared shortname
     */
    public static function prepare_shortname(string $shortname): string {
        if (strpos($shortname, ' ') !== false) {
            $shortname = substr($shortname, 0, strpos($shortname, ' '));
        }
        if (strlen($shortname) >= 5) {
            $shortname = substr($shortname, 0, 5);
        }
        return strtoupper($shortname);
    }

    /**
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this course and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->courseid = $this->courseid;
        $obj->userid = $this->userid;
        $obj->shortname = $this->shortname;
        $obj->color = $this->color;
        $obj->enabled = $this->enabled ? 1 : 0; // The DB uses int instead of bool here.

        if ($this->id !== 0) {
            $obj->id = $this->id;
        }

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
            'name' => $this->get_fullname(),
            'shortname' => $this->shortname,
            'color' => $this->color,
            'enabled' => $this->enabled ? 1 : 0, // Moodle's API uses int instead of bool.
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
                'courseid' => new external_value(PARAM_INT, 'moodle-internal course ID'),
                'userid' => new external_value(PARAM_INT, 'The user for whom these course settings are for'),
                'name' => new external_value(PARAM_TEXT, 'Full name of this course'),
                'shortname' => new external_value(PARAM_TEXT, 'Short name of this course for this user (maximum size: 5 chars)'),
                'color' => new external_value(PARAM_TEXT, 'Color for this course as #RRGGBB'),
                'enabled' => new external_value(PARAM_BOOL, 'Whether the user wants to see this course'),
            ]
        );
    }
}
