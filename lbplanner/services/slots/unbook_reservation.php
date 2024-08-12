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

use external_api;
use external_function_parameters;
use external_value;

use local_lbplanner\helpers\slot_helper;
use local_lbplanner\helpers\notifications_helper;
use local_lbplanner\model\reservation;
use local_lbplanner\enums\NOTIF_TRIGGER;

/**
 * Returns all slots the user can theoretically reserve.
 * This does not include times the user has already reserved a slot for.
 *
 * @package local_lbplanner
 * @subpackage services_plan
 * @copyright 2024 necodeIT
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class slots_unbook_reservation extends external_api {
    /**
     * Parameters for unbook_reservation.
     * @return external_function_parameters
     */
    public static function unbook_reservation_parameters(): external_function_parameters {
        return new external_function_parameters([
            'reservationid' => new external_value(
                PARAM_INT,
                'ID of the reservation for which unbooking is being requested',
                VALUE_REQUIRED,
                null,
                NULL_NOT_ALLOWED
            ),
            'nice' => new external_value(
                PARAM_BOOL,
                'whether to ask the student nicely to unbook themself via a notification',
                VALUE_OPTIONAL,
                true,
                NULL_NOT_ALLOWED
            ),
        ]);
    }

    /**
     * Tries to request unbooking
     */
    public static function unbook_reservation(int $reservationid, bool $nice): void {
        global $USER, $DB;

        $reservation = slot_helper::get_reservation($reservationid);
        $now = new DateTimeImmutable();

        $endpast = $now->diff($reservation->get_datetime_end())->invert === 1;
        $startpast = $endpast or ($now->diff($reservation->get_datetime())->invert === 1);

        if ($USER->id === $reservation->userid){
            if ($startpast) {
                throw new \moodle_exception('You can\'t unbook this reservation because it has already started');
            }
        } else if(slot_helper::check_slot_supervisor($USER->id, $reservation->slotid)) {
            if ($endpast) {
                throw new \moodle_exception('You can\'t unbook this reservation because it has already ended');
            }
            if ($nice) {
                if ($startpast) {
                    throw new \moodle_exception('Students can\'t unbook reservations that have already started. If you want to unbook this reservation regardless, force it.');
                }
                notifications_helper::notify_user($reservation->userid, $reservation->id, NOTIF_TRIGGER::UNBOOK_REQUESTED);
                return;
            } else {
                notifications_helper::notify_user($reservation->userid, $reservation->id, NOTIF_TRIGGER::UNBOOK_FORCED);
            }
        } else {
            throw new \moodle_exception('insufficient permission to unbook this reservation');
        }

        $DB->delete_records(
            slot_helper::TABLE_RESERVATIONS,
            ['id' => $reservation->id]
        );
    }

    /**
     * Returns nothing at all
     * @return null
     */
    public static function unbook_reservation_returns(): null {
        return null;
    }
}