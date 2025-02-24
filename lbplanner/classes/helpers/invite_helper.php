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

namespace local_lbplanner\helpers;

use core_external\{external_single_structure, external_value};
use local_lbplanner\enums\PLAN_INVITE_STATE;

/**
 * Helper class for plan invites
 *
 * @package    local_lbplanner
 * @subpackage helpers
 * @copyright  2024 NecodeIT
 * @license    https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class invite_helper {

    /**
     * Returns the data structure of an invite.
     *
     * @return external_single_structure The data structure of an invite.
     */
    public static function structure(): external_single_structure {
        return new external_single_structure(
            [
                'id' => new external_value(PARAM_INT, 'invite ID'),
                'inviterid' => new external_value(PARAM_INT, 'ID of the user who issued the invitation'),
                'inviteeid' => new external_value(PARAM_INT, 'ID of the user who got invited'),
                'planid' => new external_value(PARAM_INT, 'plan ID'),
                'status' => new external_value(PARAM_INT, 'status of the invitation'),
                'timestamp' => new external_value(PARAM_INT, 'time when the invitation was send'),
            ]
        );
    }

    /**
     * Throw error if the invite status isn't pending
     * @param int $status the invite status
     * @throws moodle_exception
     */
    public static function assert_invite_pending(int $status) {
        if ($status !== PLAN_INVITE_STATE::PENDING) {
            $name = PLAN_INVITE_STATE::name_from($status);
            throw new \moodle_exception('Invite already '.$name);
        }
    }
}
