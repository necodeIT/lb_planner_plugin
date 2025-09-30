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

namespace local_lbplanner_services;

use DateTimeImmutable;

use core_user;
use core_external\{external_api, external_function_parameters, external_single_structure, external_value};
use DateTimeZone;
use local_lbplanner\enums\NOTIF_TRIGGER;
use local_lbplanner\helpers\config_helper;
use local_lbplanner\helpers\notifications_helper;
use local_lbplanner\helpers\slot_helper;
use local_lbplanner\model\reservation;

/**
 * Books a reservation for the user.
 * Will unbook any overlapping reservations the user may already have.
 *
 * @package local_lbplanner
 * @subpackage services_slots
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class slots_book_reservation extends external_api {
    /**
     * Parameters for book_reservation.
     * @return external_function_parameters
     */
    public static function book_reservation_parameters(): external_function_parameters {
        global $USER;
        return new external_function_parameters([
            'slotid' => new external_value(
                PARAM_INT,
                'ID of the slot for which a reservation is being requested',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'date' => new external_value(
                PARAM_TEXT,
                'date of the reservation in YYYY-MM-DD (as per ISO-8601)',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'userid' => new external_value(
                PARAM_INT,
                'the user to reserve this slot for',
                VALUE_DEFAULT,
                $USER->id,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Books a reservation
     * @param int $slotid the slot to book a reservation for
     * @param string $date the day this reservation should take place
     * @param int $userid the user to reserve for
     */
    public static function book_reservation(int $slotid, string $date, int $userid): array {
        global $USER, $DB;
        self::validate_parameters(
            self::book_reservation_parameters(),
            [
                'slotid' => $slotid,
                'date' => $date,
                'userid' => $userid,
            ]
        );

        $utctz = new DateTimeZone('UTC');
        $now = new DateTimeImmutable('today', $utctz);
        $dateobj = DateTimeImmutable::createFromFormat("Y-m-d", $date, $utctz);
        if ($dateobj === false) {
            throw new \moodle_exception("invalid date formatting: got '{$date}', must be YYYY-MM-DD");
        }
        $td = $now->diff($dateobj);

        if ($td->invert === 1) {
            throw new \moodle_exception('Can\'t reserve date in the past');
        }

        $maxdays = null;
        $student = null;

        $curuserid = intval($USER->id);

        if ($userid === $curuserid) {
            // Student reserving slot for themself.

            $maxdays = config_helper::get_slot_futuresight();
            $student = $USER;
        } else {
            // Supervisor reserving slot for student.
            slot_helper::assert_slot_supervisor($curuserid, $slotid);

            $maxdays = slot_helper::RESERVATION_RANGE_SUPERVISOR;
            $student = core_user::get_user($userid, '*', MUST_EXIST);
        }

        if ($td->days > $maxdays) {
            throw new \moodle_exception("Date is past allowed date ({$maxdays} days in the future)");
        }

        $slot = slot_helper::get_slot($slotid);

        // Check if user has access to slot.
        if (count(slot_helper::filter_slots_for_user([$slot], $student)) === 0) {
            throw new \moodle_exception('Student does not have access to this slot');
        }

        // Check if user is already in slot.
        foreach (slot_helper::get_reservations_for_slot($slotid) as $tmpreservation) {
            if ($tmpreservation->userid === $userid) {
                throw new \moodle_exception('Student is already in slot');
            }
        }

        // Check if slot is full.
        if ($slot->get_fullness() >= $slot->size) {
            throw new \moodle_exception('Slot is already full');
        }

        $reservation = new reservation(0, $slotid, $dateobj, $userid, $curuserid);
        $reservation->set_slot($slot);

        // Check if user is already in a different slot at the same time.
        $overlapreservations = [];
        $existingreservations = slot_helper::get_reservations_for_user($userid);
        foreach ($existingreservations as $exres) {
            if ($reservation->check_overlaps($exres)) {
                array_push($overlapreservations, $exres);
            }
        }

        // Save new reservation.
        $id = $DB->insert_record(slot_helper::TABLE_RESERVATIONS, $reservation->prepare_for_db());
        $reservation->set_fresh($id, $slot);

        // If this is a supervisor reserving for a student, notify the student.
        if ($userid !== $curuserid) {
            notifications_helper::notify_user($userid, $reservation->id, NOTIF_TRIGGER::BOOK_FORCED);

            // Remove user from each overlapping reservation
            foreach ($overlapreservations as $overlapres) {
                $DB->delete_records(
                    slot_helper::TABLE_RESERVATIONS,
                    ['id' => $overlapres->id]
                );
            }
        }

        return $reservation->prepare_for_api();
    }

    /**
     * Returns the structure of the reservation
     * @return external_single_structure
     */
    public static function book_reservation_returns(): external_single_structure {
        return reservation::api_structure();
    }
}
