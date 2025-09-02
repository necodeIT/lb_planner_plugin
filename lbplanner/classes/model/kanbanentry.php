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
 * Model for a kanban entry
 *
 * @package local_lbplanner
 * @subpackage model
 * @copyright 2025 NecodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

namespace local_lbplanner\model;


use core_external\{external_single_structure, external_value};
use local_lbplanner\enums\{KANBANCOL_TYPE, KANBANCOL_TYPE_NUMERIC};

/**
 * Model class for a kanban board entry
 */
class kanbanentry {
    /**
     * @var int $id ID of kbbe
     */
    public int $id;
    /**
     * @var int $userid ID of user
     */
    public int $userid;
    /**
     * @var int $cmid ID of course-module
     */
    public int $cmid;
    /**
     * @var int $column column number
     */
    public int $column;

    /**
     * Constructs a kbbe
     * @param int $id ID of kbbe
     * @param int $userid ID of user
     * @param int $cmid ID of course-module
     * @param int $column column number
     */
    public function __construct(int $id, int $userid, int $cmid, int $column) {
        $this->id = $id;
        $this->userid = $userid;
        $this->cmid = $cmid;
        $this->column = $column;
    }

    /**
     * Initializes object from a DB object
     * @param \stdClass $obj the DB obj
     * @return kanbanentry the kanbanentry obj
     */
    public static function from_obj(\stdClass $obj): self {
        return new self($obj->id, $obj->userid, $obj->cmid, $obj->column);
    }

    /**
     * Mark the object as freshly created and sets the new ID
     * @param int $id the new ID after insertint into the DB
     */
    public function set_fresh(int $id) {
        assert($this->id === 0);
        assert($id !== 0);
        $this->id = $id;
    }

    /**
     * Prepares data for the API endpoint.
     *
     * @return array a representation of this kbbe and its data
     */
    public function prepare_for_api(): array {
        return [
            'id' => $this->id,
            'userid' => $this->userid,
            'cmid' => $this->cmid,
            'column' => KANBANCOL_TYPE_NUMERIC::to_named($this->column),
        ];
    }

    /**
     * Returns the data structure of an kanban board entry.
     *
     * @return external_single_structure The data structure of a kbbe.
     */
    public static function api_structure(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'kanban board ID'),
                'userid' => new external_value(PARAM_INT, 'ID of the owner of this entry'),
                'cmid' => new external_value(PARAM_INT, 'ID of the course-module'),
                'column' => new external_value(PARAM_TEXT, 'which column this module is in '.KANBANCOL_TYPE::format()),
            ]
        );
    }
}
