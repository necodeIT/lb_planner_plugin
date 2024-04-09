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
 * Provides helper classes for any tables related with the slot booking function of the app
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2024 NecodeIT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\helpers;

use local_lbplanner\model\{slot, reservation, slot_filter};

/**
 * Provides helper methods for any tables related with the planning function of the app
 */
class slot_helper {
    /**
     * local_lbplanner_slots table.
     */
    const TABLE_SLOTS = 'local_lbplanner_slots';
    /**
     * local_lbplanner_reservations table.
     */
    const TABLE_RESERVATIONS = 'local_lbplanner_reservations';
    /**
     * local_lbplanner_slot_courses table.
     */
    const TABLE_SLOT_FILTERS = 'local_lbplanner_slot_courses';
    /**
     * local_lbplanner_supervisors table.
     */
    const TABLE_SUPERVISORS = 'local_lbplanner_supervisors';

    /**
     * Returns a list of all slots.
     *
     * @return slot[] An array of the slots.
     */
    public static function get_all_slots(): array {
        global $DB;
        $slots = $DB->get_records(self::TABLE_SLOTS, []);

        $slots_obj = [];
        foreach($slots as $slot){
            array_push($slots_obj, new slot(...$slot));
        }

        return $slots_obj;
    }

    /**
     * Returns a singular slot.
     *
     * @return slot the requested slot
     */
    public static function get_slot(int $slotid): slot {
        global $DB;
        $slot = $DB->get_record(self::TABLE_SLOTS, ['id'=>$slotid]);

        return new slot(...$slot);
    }

    /**
     * Returns reservations for a slot.
     *
     * @return reservation[] the requested reservations
     */
    public static function get_reservations_for_slot(int $slotid): array {
        global $DB;
        $reservations = $DB->get_records(self::TABLE_RESERVATIONS, ['slotid'=>$slotid]);

        $reservations_obj = [];
        foreach($reservations as $reservation){
            $reservation['date'] = new \DateTime($reservation['date']);
            array_push($reservations_obj, new reservation(...$reservation));
        }

        return $reservations_obj;
    }

    /**
     * Returns filters for a slot.
     *
     * @return slot_filter[] the requested filters
     */
    public static function get_filters_for_slot(int $slotid): array {
        global $DB;
        $filters = $DB->get_records(self::TABLE_SLOT_FILTERS, ['slotid'=>$slotid]);

        $filters_obj = [];
        foreach($filters as $filter){
            array_push($filters_obj, new slot_filter(...$filter));
        }

        return $filters_obj;
    }
}
