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
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\helpers;

use core\context\system as context_system;
use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use local_lbplanner\enums\{CAPABILITY, WEEKDAY};
use local_lbplanner\model\{slot, reservation, slot_filter};

/**
 * Provides helper methods for any tables related with the planning function of the app
 */
class slot_helper {
    /**
     * how far into the future a supervisor can reserve a slot for a user
     */
    const RESERVATION_RANGE_SUPERVISOR = 7;
    /**
     * how long the room names can be in characters
     * unicode characters might count as multiple characters
     */
    const ROOM_MAXLENGTH = 7; // TODO: increase to 255 or sumn.
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
        '17:00', // All units after this point are 45min long instead of the usual 50.
        '17:45', // We will assume 50min anyway because it's easier that way.
        '18:45',
        '19:30',
        '20:15',
        '21:00',
    ];
    /**
     * maximum school unit
     *
     * NOTE: const cannot use count() in php<8
     */
    const SCHOOL_UNIT_MAX = 16;
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
            array_push($slotsobj, slot::from_db($slot));
        }

        return $slotsobj;
    }

    /**
     * Returns a list of all slots relevant for a vintage.
     *
     * @param string $vintage the vintage to filter for
     * @return slot[] An array of the slots.
     */
    public static function get_vintage_slots(string $vintage): array {
        global $DB;
        $slots = $DB->get_records_sql(
            'SELECT slot.* FROM {' . self::TABLE_SLOTS . '} as slot ' .
            'INNER JOIN {'. self::TABLE_SLOT_FILTERS . '} as filter ON slot.id=filter.slotid ' .
            'WHERE filter.vintage=? OR filter.vintage=NULL',
            [$vintage]
        );

        $slotsobj = [];
        foreach ($slots as $slot) {
            array_push($slotsobj, slot::from_db($slot));
        }

        return $slotsobj;
    }

    /**
     * Returns a list of all slots belonging to a supervisor.
     * @param int $supervisorid userid of the supervisor in question
     *
     * @return slot[] An array of the slots.
     */
    public static function get_supervisor_slots(int $supervisorid): array {
        global $DB;

        $slots = $DB->get_records_sql(
            'SELECT slot.* FROM {' . self::TABLE_SLOTS . '} as slot ' .
            'INNER JOIN {' . self::TABLE_SUPERVISORS . '} as supervisor ON supervisor.slotid=slot.id ' .
            'WHERE supervisor.userid=?',
            [$supervisorid]
        );

        $slotsobj = [];
        foreach ($slots as $slot) {
            array_push($slotsobj, slot::from_db($slot));
        }

        return $slotsobj;
    }

    /**
     * Returns slots associated with a specific room.
     * @param string $room the room name to look for
     *
     * @return slot[] An array of the slots.
     */
    public static function get_slots_by_room(string $room): array {
        global $DB;
        $slots = $DB->get_records(self::TABLE_SLOTS, ['room' => $room]);

        $slotsobj = [];
        foreach ($slots as $slot) {
            array_push($slotsobj, slot::from_db($slot));
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

        return slot::from_db($slot);
    }

    /**
     * Returns a singular reservation.
     * @param int $reservationid ID of the reservation
     *
     * @return reservation the requested reservation
     */
    public static function get_reservation(int $reservationid): reservation {
        global $DB;
        $reservation = $DB->get_record(self::TABLE_RESERVATIONS, ['id' => $reservationid]);

        if ($reservation === false) {
            throw new \moodle_exception('requested reservation does not exist');
        }

        return reservation::from_obj($reservation);
    }

    /**
     * Returns a singular slot filter.
     * @param int $filterid ID of the filter
     *
     * @return slot_filter the requested filter
     */
    public static function get_slot_filter(int $filterid): slot_filter {
        global $DB;
        $filter = $DB->get_record(self::TABLE_SLOT_FILTERS, ['id' => $filterid]);

        return slot_filter::from_db($filter);
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
            array_push($reservationsobj, reservation::from_obj($reservation));
        }

        return self::filter_reservations_for_recency($reservationsobj);
    }

    /**
     * Returns reservations for a user.
     * @param int $userid ID of the user
     *
     * @return reservation[] the requested reservations
     */
    public static function get_reservations_for_user(int $userid): array {
        global $DB;
        $reservations = $DB->get_records(self::TABLE_RESERVATIONS, ['userid' => $userid]);

        $reservationsobj = [];
        foreach ($reservations as $reservation) {
            array_push($reservationsobj, reservation::from_obj($reservation));
        }

        return $reservationsobj;
    }

    /**
     * Validates reservation recency and removes reservations that are outdated
     * @param reservation[] $reservations input reservations
     *
     * @return reservation[] reservations that pass
     */
    public static function filter_reservations_for_recency(array $reservations): array {
        $goodeggs = [];
        foreach ($reservations as $reservation) {
            if (!$reservation->is_outdated()) {
                array_push($goodeggs, $reservation);
            }
        }

        return $goodeggs;
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
            array_push($filtersobj, slot_filter::from_db($filter));
        }

        return $filtersobj;
    }

    /**
     * Filters an array of slots for the slots that the user can theoretically reserve
     * NOTE: not taking into account time or fullness, only filters i.e. users' class and courses
     * TODO: replace $user with $vintage
     * @param slot[] $allslots the slots to filter
     * @param \stdClass $user a user object - e.g. $USER or a user object from the database
     * @return slot[] the filtered slot array
     */
    public static function filter_slots_for_user(array $allslots, \stdClass $user): array {
        $mycourses = course_helper::get_eduplanner_courses(true);
        $mycourseids = [];
        foreach ($mycourses as $course) {
            array_push($mycourseids, $course->courseid);
        }

        $slots = [];
        foreach ($allslots as $slot) {
            $filters = $slot->get_filters();
            foreach ($filters as $filter) {
                // Checking for course ID.
                if (!in_array($filter->courseid, $mycourseids)) {
                    continue;
                }
                // TODO: replace address with cohorts.
                // Checking for vintage.
                if ($user->address !== $filter->vintage) {
                    continue;
                }
                // If all filters passed, add slot to my slots and break.
                array_push($slots, $slot);
                break;
            }
        }
        return $slots;
    }

    /**
     * Filters an array of slots for a timerange around now.
     * @param slot[] $allslots the slots to filter
     * @param int $range how many days in the future the slot is allowed to be
     * @return slot[] the filtered slot array
     */
    public static function filter_slots_for_time(array $allslots, int $range): array {
        $utctz = new DateTimeZone('UTC');
        $now = new DateTimeImmutable('now', $utctz);
        $slots = [];
        // Calculate date and time each slot happens next, and add it to the return list if within reach from today.
        foreach ($allslots as $slot) {
            $slotdatetime = self::calculate_slot_datetime($slot, $now);

            // Compare only date difference, ignoring time.
            if ($now->setTime(0, 0, 0)->diff($slotdatetime->setTime(0, 0, 0))->days < $range) {
                array_push($slots, $slot);
            }
        }
        return $slots;
    }

    /**
     * calculates when a slot is to happen next
     * @param slot $slot the slot
     * @param DateTimeImmutable $now the point in time representing now
     * @return DateTimeImmutable the next time this slot will occur
     */
    public static function calculate_slot_datetime(slot $slot, DateTimeImmutable $now): DateTimeImmutable {
        $slotdaytime = self::SCHOOL_UNITS[$slot->startunit];
        // Move to next day this weekday occurs (doesn't move if it's the same as today).
        $slotdatetime = $now->modify('this ' . WEEKDAY::name_from($slot->weekday) . " {$slotdaytime}");
        if ($slotdatetime === false) {
            throw new \coding_exception('error while calculating slot datetime');
        }

        // Check if slot is before now (because time of day and such) and move it a week into the future if so.
        if ($now->diff($slotdatetime)->invert === 1) {
            $slotdatetime = $slotdatetime->modify('+1 week');
        }

        return DateTimeImmutable::createFromInterface($slotdatetime);
    }

    /**
     * Amends a date with time of day using the units system
     * @param int $unit the unit to use
     * @param DateTimeInterface $date the date (time of day will be ignored)
     *
     * @return DateTimeImmutable the new date with time of day filled in
     * @link slot_helper::SCHOOL_UNITS
     */
    public static function amend_date_with_unit_time(int $unit, DateTimeInterface $date): DateTimeImmutable {
        $utctz = new DateTimeZone('UTC');
        $daytime = self::SCHOOL_UNITS[$unit];

        return DateTimeImmutable::createFromFormat('Y-m-d G:i', $date->format('Y-m-d ') . $daytime, $utctz);
    }

    /**
     * Checks whether a user has supervisor-level permissions for a specific slot.
     * NOTE: The user in question does not necessarily literally be the slot's supervisor, just have sufficient permissions!
     * @param int $supervisorid userid of the supervisor in question
     * @param int $slotid the slot to check
     *
     * @return bool Whether this user has perms for this slot
     */
    public static function check_slot_supervisor(int $supervisorid, int $slotid): bool {
        global $DB;

        $context = context_system::instance();
        if (has_capability(CAPABILITY::SLOTMASTER, $context, $supervisorid)) {
            return true;
        }

        return $DB->record_exists(self::TABLE_SUPERVISORS, ['userid' => $supervisorid, 'slotid' => $slotid]);
    }

    /**
     * Same as {@see check_slot_supervisor()}, except it throws an error if permissions are insufficient.
     * @param int $supervisorid userid of the supervisor in question
     * @param int $slotid the slot to check
     *
     * @throws \moodle_exception if the user has insufficient permissions
     */
    public static function assert_slot_supervisor(int $supervisorid, int $slotid): void {
        if (!self::check_slot_supervisor($supervisorid, $slotid)) {
            throw new \moodle_exception('Insufficient Permission: you\'re not supervisor of this slot');
        }
    }
}
