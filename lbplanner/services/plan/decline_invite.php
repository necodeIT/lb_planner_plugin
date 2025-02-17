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

use core_external\{external_api, external_function_parameters, external_value};
use local_lbplanner\helpers\{plan_helper, notifications_helper, invite_helper};
use local_lbplanner\enums\{NOTIF_TRIGGER, PLAN_INVITE_STATE};

/**
 * Decline an invite from the plan.
 *
 * @package local_lbplanner
 * @subpackage services_plan
 * @copyright 2024 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class plan_decline_invite extends external_api {
    /**
     * Parameters for decline_invite.
     * @return external_function_parameters
     */
    public static function decline_invite_parameters(): external_function_parameters {
        return new external_function_parameters([
        'inviteid' => new external_value(PARAM_INT, 'the ID of the invite to be declined', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    /**
     * Decline an invite.
     *
     * @param int $inviteid the ID of the invite to be declined
     * @return void
     * @throws \moodle_exception when invite not found, already accepted or declined
     */
    public static function decline_invite(int $inviteid) {
        global $DB, $USER;

        self::validate_parameters(self::decline_invite_parameters(), [
        'inviteid' => $inviteid,
        ]);

        $invite = $DB->get_record(
            plan_helper::INVITES_TABLE,
            ['id' => $inviteid],
        );

        if ($invite === false) {
            throw new \moodle_exception('Invite not found');
        }
        invite_helper::assert_invite_pending($invite->status);

        // Notify the user that invite has been declined.
        notifications_helper::notify_user(
            $invite->inviterid,
            $invite->id,
            NOTIF_TRIGGER::INVITE_DECLINED
        );

        $invite->status = PLAN_INVITE_STATE::DECLINED;

        $DB->update_record(plan_helper::INVITES_TABLE, $invite);
    }

    /**
     * Returns the structure of nothing.
     * @return null
     */
    public static function decline_invite_returns() {
        return null;
    }
}
