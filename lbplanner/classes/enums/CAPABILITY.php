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
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_lbplanner\enums;

// TODO: revert to native enums once we migrate to php8.

use local_lbplanner\polyfill\Enum;
use local_lbplanner\enums\CAPABILITY_FLAG;

/**
 * Capabilities a user can have
 */
class CAPABILITY extends Enum {
    /**
     * Shortname of the admin CAPABILITY.
     */
    const ADMIN = 'local/lb_planner:admin';

    /**
     * Shortname of the manager CAPABILITY.
     */
    const MANAGER = 'local/lb_planner:manager';

    /**
     * Shortname of the teacher CAPABILITY.
     */
    const TEACHER = 'local/lb_planner:teacher';

    /**
     * Shortname of the student CAPABILITY.
     */
    const STUDENT = 'local/lb_planner:student';

    /**
     * Matches a capability string to its bitmappable flag
     * @param string $str the capability string
     * @return int the bitmappable flag
     * @link CAPABILITY_FLAG
     */
    public static function to_capability(string $str): int {
        $name = self::name_from($str);
        return constant('CAPABILITY_FLAG::'.$name);
    }
}
