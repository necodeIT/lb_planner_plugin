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
     * school units according to untis, in H:i format
     */
    const SCHOOL_UNITS = [
        null,
        '08:00',
        '08:50',
        '09:50',
        '10:40',
        '11:30',
        '12:30',
        '13:20',
        '14:10',
        '15:10',
        '16:00',
        '17:00',  // All units after this point are 45min long instead of the usual 50.
        '17:45',  // We will assume 50min anyway because it's easier that way.
        '18:45',
        '19:30',
        '20:15',
        '21:00',
    ];
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

        $slotsobj = [];
        foreach ($slots as $slot) {
            array_push($slotsobj, new slot(...$slot));
        }

        return $slotsobj;
    }

    /**
     * Returns a singular slot.
     * @param int $slotid ID of the slot
     *
     * @return slot the requested slot
     */
    public static function get_slot(int $slotid): slot {
        global $DB;
        $slot = $DB->get_record(self::TABLE_SLOTS, ['id' => $slotid]);

        return new slot(...$slot);
    }

    /**
     * Returns reservations for a slot.
     * @param int $slotid ID of the slot
     *
     * @return reservation[] the requested reservations
     */
    public static function get_reservations_for_slot(int $slotid): array {
        global $DB;
        $reservations = $DB->get_records(self::TABLE_RESERVATIONS, ['slotid' => $slotid]);

        $reservationsobj = [];
        foreach ($reservations as $reservation) {
            $reservation['date'] = new \DateTime($reservation['date']);
            array_push($reservationsObj, new reservation(...$reservation));
        }

        return $reservationsobj;
    }

    /**
     * Returns filters for a slot.
     * @param int $slotid ID of the slot
     *
     * @return slot_filter[] the requested filters
     */
    public static function get_filters_for_slot(int $slotid): array {
        global $DB;
        $filters = $DB->get_records(self::TABLE_SLOT_FILTERS, ['slotid' => $slotid]);

        $filtersobj = [];
        foreach ($filters as $filter) {
            array_push($filtersobj, new slot_filter(...$filter));
        }

        return $filtersobj;
    }
}
