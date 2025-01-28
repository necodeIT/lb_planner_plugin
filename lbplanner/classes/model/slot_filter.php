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
 * Model for a filter for slots
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;

use core_external\{external_single_structure, external_value};

/**
 * Model class for a filter for slots
 */
class slot_filter {
    /**
     * @var int $id ID of filter
     */
    public int $id;
    /**
     * @var int $id ID of linked slot
     */
    public int $slotid;
    /**
     * @var ?int $id ID of linked course or null if any
     */
    public ?int $courseid;
    /**
     * @var ?string $vintage linked class or null if any
     */
    public ?string $vintage;

    /**
     * Constructs new slot_filter
     * @param int $id ID of filter
     * @param int $slotid ID of linked slot
     * @param ?int $courseid ID of linked course or null if any
     * @param ?string $vintage linked class or null if any
     */
    public function __construct(int $id, int $slotid, ?int $courseid, ?string $vintage) {
        assert(!(is_null($courseid) && is_null($vintage)));
        if (!is_null($vintage)) {
            assert(strlen($vintage) <= 7);
        }

        $this->id = $id;
        $this->slotid = $slotid;
        $this->courseid = $courseid;
        $this->vintage = $vintage;
    }

    /**
     * Creates a slot_filter object from a DB result
     * @param \stdClass $obj the DB object
     * @return slot_filter the resulting slot_filter object
     */
    public static function from_db(\stdClass $obj): self {
        return new slot_filter(
            $obj->id,
            $obj->slotid,
            $obj->courseid,
            $obj->vintage,
        );
    }

    /**
     * Mark the object as freshly created and sets the new ID
     * @param int $id the new ID after inserting into the DB
     */
    public function set_fresh(int $id) {
        assert($this->id === 0);
        assert($id !== 0);
        $this->id = $id;
    }

    /**
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this object and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->slotid = $this->slotid;
        $obj->courseid = $this->courseid;
        $obj->vintage = $this->vintage;

        if ($this->id !== 0) {
            $obj->id = $this->id;
        }
        return $obj;
    }

    /**
     * Prepares data for the API endpoint.
     *
     * @return array a representation of this object and its data
     */
    public function prepare_for_api(): array {
        return [
            'id' => $this->id,
            'slotid' => $this->slotid,
            'courseid' => $this->courseid,
            'vintage' => $this->vintage,
        ];
    }

    /**
     * Returns the data structure of this object for the API.
     *
     * @return external_single_structure The data structure of this object for the API.
     */
    public static function api_structure(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'filter ID'),
                'slotid' => new external_value(PARAM_INT, 'ID of associated slot'),
                'courseid' => new external_value(
                    PARAM_INT,
                    'ID of course to filter for (or null if "any")',
                    VALUE_REQUIRED,
                    null,
                    NULL_ALLOWED
                ),
                'vintage' => new external_value(
                    PARAM_TEXT,
                    'class name to filter for (or null if "any")',
                    VALUE_REQUIRED,
                    null,
                    NULL_ALLOWED
                ),
            ]
        );
    }
}
