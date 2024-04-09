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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\model;

/**
 * Model class for a filter for slots
 */
class slot_filter {
    public int $id;
    public int $slotid;
    public ?int $courseid;
    public ?string $vintage;

    public function __construct(int $id, int $slotid, ?int $courseid, ?string $vintage) {
        $this->id = $id;
        $this->slotid = $slotid;
        $this->courseid = $courseid;
        if(!is_null($vintage))
            assert(strlen($vintage) <= 7);
        $this->vintage = $vintage;
    }
}