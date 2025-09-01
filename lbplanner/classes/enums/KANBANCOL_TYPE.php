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
 * enum for columns on the kanban board
 *
 * @package local_lbplanner
 * @subpackage enums
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\enums;

// TODO: revert to native enums once we migrate to php8.

use local_lbplanner\polyfill\Enum;

/**
 * The types of columns in the kanban board
 */
class KANBANCOL_TYPE extends Enum {
    /**
     * column "backlog"
     */
    const BACKLOG = 'backlog';
    /**
     * column "in progress"
     */
    const INPROGRESS = 'inprogress';
    /**
     * column "todo"
     */
    const TODO = 'todo';
    /**
     * column "done"
     */
    const DONE = 'done';
}
