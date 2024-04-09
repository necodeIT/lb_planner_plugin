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
 * Model for a reservation
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\model;

use local_lbplanner\model\slot;
use local_lbplanner\helpers\slot_helper;

/**
 * Model class for reservation
 */
class reservation {
    public int $id;
    public int $slotid;
    public \DateTimeImmutable $date;
    public int $userid;
    public int $reserverid;
    private ?slot $slot;

    public function __construct(int $id, int $slotid, \DateTimeImmutable $date, int $userid, int $reserverid) {
        $this->id = $id;
        $this->slotid = $slotid;
        $this->date = $date;
        $this->userid = $userid;
        $this->reserverid = $reserverid;
        $this->slot = null;
    }

    /**
     * Returns the associated slot.
     *
     * @returns slot the associated slot
     */
    public function get_slot(): slot {
        if(is_null($this->slot)){
            $this->slot = slot_helper::get_slot($this->slotid);
        }

        return $this->slot;
    }
}
