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
 * capability flag
 *
 * @package local_lbplanner
 * @subpackage enums
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\enums;

// TODO: revert to native enums once we migrate to php8.

use local_lbplanner\enums\{CAPABILITY, CAPABILITY_FLAG};

/**
 * Bitmappable flags for capabilities a user can have.
 * Also includes 0 for the lack of any capabilities.
 */
class CAPABILITY_FLAG_ORNONE extends CAPABILITY_FLAG {
    /**
     * Absence of any CAPABILITY.
     */
    const NONE = 0;

    /**
     * matches a flag to its capability string
     * @param int $num the bitmappable flag
     * @return string the capability string
     * @throws \coding_exception if $num === 0 (not an actual capability)
     * @link CAPABILITY
     */
    public static function to_capability(int $num): string {
        if ($num === 0) {
            throw new \coding_exception(get_string('err_enum_capability_none', 'local_lbplanner'));
        }
        return parent::to_capability($num);
    }
}
