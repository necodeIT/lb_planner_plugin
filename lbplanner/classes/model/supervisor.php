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
 * Model class for a filter for slots
 *
 * @package local_lbplanner
 * @subpackage model
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;

/**
 * Model class for a filter for slots
 */
class supervisor {
    /**
     * @var int $id ID of filter
     */
    public int $id;
    /**
     * @var int $slotid ID of linked slot
     */
    public int $slotid;
    /**
     * @var int $userid ID of user
     */
    public int $userid;

    /**
     * Constructs new slot_filter
     * @param int $id ID of filter
     * @param int $slotid ID of linked slot
     * @param int $userid ID of user
     */
    public function __construct(int $id, int $slotid, int $userid) {
        $this->id = $id;
        $this->slotid = $slotid;
        $this->userid = $userid;
    }

    /**
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this supervisor and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->id = $this->id;
        $obj->slotid = $this->slotid;
        $obj->userid = $this->userid;

        if ($this->id !== 0) {
            $obj->id = $this->id;
        }

        return $obj;
    }
}
