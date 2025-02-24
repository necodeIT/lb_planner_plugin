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
 * Model for a slot
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2024 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;

use local_lbplanner\enums\WEEKDAY;
use local_lbplanner\helpers\slot_helper;

use core_external\{external_multiple_structure, external_single_structure, external_value};
use moodle_exception;

/**
 * Model class for slot
 */
class slot {
    /**
     * @var int $id ID of slot
     */
    public int $id;
    /**
     * @var int $startunit Unit this slot starts in
     */
    public int $startunit;
    /**
     * @var int $duration duration of slot in units
     */
    public int $duration;
    /**
     * @var int $weekday weekday this slot occurs in
     */
    public int $weekday;
    /**
     * @var string $room room this slot is for
     */
    public string $room;
    /**
     * @var int $size how many pupils fit in this slot
     */
    public int $size;
    /**
     * @var ?int $fullness how many pupils have already reserved this slot (gets filled in by helper functions)
     */
    private ?int $fullness;
    /**
     * @var ?bool $forcuruser whether the current user has reserved this slot (gets filled in by helper functions)
     */
    private ?bool $forcuruser;
    /**
     * @var ?int[] $supervisors list of supervisors for this slot
     */
    private ?array $supervisors;
    /**
     * @var ?slot_filter[] $filters list of filters for this slot
     */
    private ?array $filters;

    /**
     * Constructs a new Slot
     * @param int $id ID of slot
     * @param int $startunit Unit this slot starts in
     * @param int $duration duration of slot in units
     * @param int $weekday weekday this slot occurs in
     * @param string $room room this slot is for
     * @param int $size how many pupils fit in this slot
     * @link slot_helper::SCHOOL_UNITS
     * @link WEEKDAY
     */
    public function __construct(int $id, int $startunit, int $duration, int $weekday, string $room, int $size) {
        $this->id = $id;
        if ($startunit <= 0 || $startunit > slot_helper::SCHOOL_UNIT_MAX) {
            throw new \moodle_exception("slot's startunit must be >0 and <=".slot_helper::SCHOOL_UNIT_MAX.", but is {$startunit}");
        }
        if ($duration <= 0 || ($startunit + $duration - 1) > slot_helper::SCHOOL_UNIT_MAX) {
            throw new \moodle_exception(
                "slot's duration must be >0 and can't exceed ".slot_helper::SCHOOL_UNIT_MAX." with startunit, but is {$duration}"
            );
        }
        $this->startunit = $startunit;
        $this->duration = $duration;
        $this->weekday = WEEKDAY::from($weekday);
        if (strlen($room) <= 0 && strlen($room) > slot_helper::ROOM_MAXLENGTH) {
            throw new \moodle_exception(
                "room name's length must be >0 and <=".slot_helper::ROOM_MAXLENGTH.", but is ".strlen($room)
            );
        }
        $this->room = $room;
        if ($size < 0) {
            throw new \moodle_exception('room size must be >0');
        }
        $this->size = $size;
        $this->fullness = null;
        $this->forcuruser = null;
        $this->supervisors = null;
        $this->filters = null;
    }

    /**
     * Creates a slot object from a DB result
     * @param \stdClass $obj the DB object
     * @return slot the resulting slot object
     */
    public static function from_db(\stdClass $obj): self {
        return new slot(
            $obj->id,
            $obj->startunit,
            $obj->duration,
            $obj->weekday,
            $obj->room,
            $obj->size,
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
        $this->fullness = 0;
        $this->forcuruser = false;
        $this->filters = [];
    }

    /**
     * Validate object data
     * @throws moodle_exception
     */
    public function validate(): void {
        static $maxunit = slot_helper::SCHOOL_UNIT_MAX;
         // Validating startunit.
        if ($this->startunit < 1) {
            throw new moodle_exception('can\'t have a start unit smaller than 1');
        } else if ($this->startunit > $maxunit) {
            throw new moodle_exception("can't have a start unit larger than {$maxunit}");
        }
        // Validating duration.
        if ($this->duration < 1) {
            throw new moodle_exception('duration must be at least 1');
        } else if ($this->startunit + $this->duration > $maxunit) {
            throw new moodle_exception("slot goes past the max unit {$maxunit}");
        }
        // Validating weekday.
        WEEKDAY::from($this->weekday);
        // Validating room.
        if (strlen($this->room) <= 1) {
            throw new moodle_exception('room name has to be at least 2 characters long');
        } else if (strlen($this->room) > slot_helper::ROOM_MAXLENGTH) {
            throw new moodle_exception('room name has a maximum of '.slot_helper::ROOM_MAXLENGTH.' characters');
        }
        // Validating size.
        if ($this->size < 0) {
            throw new moodle_exception('can\'t have a negative size for a slot');
        }
    }

    /**
     * Returns how many reservations there are for this slot.
     *
     * @return int fullness
     */
    public function get_fullness(): int {
        if (is_null($this->fullness)) {
            $this->check_reservations();
        }

        return $this->fullness;
    }

    /**
     * Returns whether the current user has a reservation for this slot.
     *
     * @return bool forcuruser
     */
    public function get_forcuruser(): bool {
        if (is_null($this->forcuruser)) {
            $this->check_reservations();
        }

        return $this->forcuruser;
    }

    /**
     * Returns the list of supervisor userIDs
     *
     * @return int[] this slot's supervsisors' userIDs
     */
    public function get_supervisors(): array {
        global $DB;
        if (is_null($this->supervisors)) {
            $this->supervisors = $DB->get_fieldset(
                slot_helper::TABLE_SUPERVISORS,
                'userid',
                ['slotid' => $this->id],
            );
        }

        return $this->supervisors;
    }

    /**
     * Returns filters for this slot.
     *
     * @return slot_filter[] the requested filters
     */
    public function get_filters(): array {
        if (is_null($this->filters)) {
            $this->filters = slot_helper::get_filters_for_slot($this->id);
        }

        return $this->filters;
    }

    /**
     * Returns whether this and $other overlap in their time.
     * @param slot $other the other slot
     * @param bool $checkroom also require overlap in rooms
     *
     * @return bool whether slots overlap
     */
    public function check_overlaps(slot $other, bool $checkroom): bool {
        if ($checkroom && ($this->room !== $other->room)) {
            return false;
        }
        if ($this->weekday !== $other->weekday) {
            return false;
        }
        if ($this->startunit === $other->startunit) {
            return true;
        }
        // Now only three variants are left: one entirely inside the other, or both intersecting partially.
        // In either case, if one of the startunits is inside the other's range, then we know the time ranges overlap.
        // Logically, only the one that starts later can be inside the other's range.
        $thisbeforeother = $this->startunit < $other->startunit;
        $a = $thisbeforeother ? $this : $other;
        $b = $thisbeforeother ? $other : $this;

        return ($a->startunit + $a->duration) > $b->startunit;
    }

    /**
     * Prepares data for the DB endpoint.
     * doesn't set ID if it's 0
     *
     * @return object a representation of this slot and its data
     */
    public function prepare_for_db(): object {
        $obj = new \stdClass();

        $obj->startunit = $this->startunit;
        $obj->duration = $this->duration;
        $obj->weekday = $this->weekday;
        $obj->room = $this->room;
        $obj->size = $this->size;

        if ($this->id !== 0) {
            $obj->id = $this->id;
        }
        return $obj;
    }

    /**
     * Prepares data for the API endpoint.
     *
     * @return array a representation of this slot and its data
     */
    public function prepare_for_api(): array {
        return [
            'id' => $this->id,
            'startunit' => $this->startunit,
            'duration' => $this->duration,
            'weekday' => $this->weekday,
            'room' => $this->room,
            'size' => $this->size,
            'fullness' => $this->get_fullness(),
            'forcuruser' => $this->get_forcuruser(),
            'supervisors' => $this->get_supervisors(),
            'filters' => array_map(fn($f) => $f->prepare_for_api(), $this->get_filters()),
        ];
    }

    /**
     * Returns the data structure of a slot for the API.
     *
     * @return external_single_structure The data structure of a slot for the API.
     */
    public static function api_structure(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'slot ID'),
                'startunit' => new external_value(PARAM_INT, 'unit this slot starts in (8:00 is unit 1)'),
                'duration' => new external_value(PARAM_INT, 'duration of the slot in units'),
                'weekday' => new external_value(PARAM_INT, 'The day this unit repeats weekly: '.WEEKDAY::format()),
                'room' => new external_value(PARAM_TEXT, 'The room this slot is for'),
                'size' => new external_value(PARAM_INT, 'total capacity of the slot'),
                'fullness' => new external_value(PARAM_INT, 'how many people have already reserved this slot'),
                'forcuruser' => new external_value(PARAM_BOOL, 'whether the current user has reserved this slot'),
                'supervisors' => new external_multiple_structure(
                    new external_value(PARAM_INT, 'this slot\'s supervisors\' userIDs')
                ),
                'filters' => new external_multiple_structure(
                    slot_filter::api_structure()
                ),
            ]
        );
    }

    /**
     * Queries reservations for this slot and fills in internal data with that info.
     */
    private function check_reservations(): void {
        global $USER;

        $reservations = slot_helper::get_reservations_for_slot($this->id);
        $reservations = slot_helper::filter_reservations_for_recency($reservations);

        $this->fullness = count($reservations);

        foreach ($reservations as $reservation) {
            if ($reservation->userid === intval($USER->id)) {
                $this->forcuruser = true;
                return;
            }
        }
        $this->forcuruser = false;
    }
}
