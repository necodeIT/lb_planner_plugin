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
 * capability string
 *
 * @package local_lbplanner
 * @subpackage enums
 * @copyright 2024 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\enums;

// TODO: revert to native enums once we migrate to php8.

use local_lbplanner\polyfill\Enum;
use local_lbplanner\enums\CAPABILITY;

/**
 * Bitmappable flags for capabilities a user can have
 */
class CAPABILITY_FLAG extends Enum {
    /**
     * Flag of the teacher CAPABILITY.
     */
    const TEACHER = 4;

    /**
     * Flag of the student CAPABILITY.
     */
    const STUDENT = 8;

    /**
     * Flag of the slotmaster CAPABILITY.
     */
    const SLOTMASTER = 16;

    /**
     * matches a flag to its capability string
     * @param int $num the bitmappable flag
     * @return string the capability string
     * @link CAPABILITY
     */
    public static function to_capability(int $num): string {
        return CAPABILITY::get(self::name_from($num));
    }
}
