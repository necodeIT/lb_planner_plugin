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

use external_api;
use external_function_parameters;
use external_single_structure;
use external_value;
use local_lbplanner\helpers\user_helper;
use local_lbplanner\helpers\plan_helper;
use local_lbplanner\helpers\notifications_helper;
use local_lbplanner\helpers\PLAN_INVITE_STATE;

/**
 * Update a invite from the plan.
 */
class plan_decline_invite extends external_api {
    public static function decline_invite_parameters() {
        return new external_function_parameters(array(
        'inviteid' => new external_value(PARAM_INT, 'The inviteid of the plan', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        'userid' => new external_value(PARAM_INT, 'The id of the invited user', VALUE_REQUIRED, null, NULL_NOT_ALLOWED)
        ));
    }

    public static function decline_invite($inviteid, $userid) {
        global $DB;

        self::validate_parameters(self::decline_invite_parameters(), array(
        'inviteid' => $inviteid,
        'userid' => $userid,
        ));

        user_helper::assert_access($userid);

        if (!$DB->record_exists(plan_helper::INVITES_TABLE, array('id' => $inviteid, 'inviteeid' => $userid))) {
            throw new \moodle_exception('Invite not found');
        }
        if (!$DB->record_exists(plan_helper::INVITES_TABLE,
        array('id' => $inviteid, 'inviteeid' => $userid, 'status' => PLAN_INVITE_STATE::PENDING->value))) {
            throw new \moodle_exception('Invite already accepted or declined');
        }

        $invite = $DB->get_record(plan_helper::INVITES_TABLE,
        array(
            'id' => $inviteid,
            'inviteeid' => $userid,
            'status' => PLAN_INVITE_STATE::PENDING->value,
        ),
        '*',
        MUST_EXIST
        );

        // Notify the user that invite has been declined.
        notifications_helper::notify_user(
            $invite->inviterid,
            $invite->id,
            notifications_helper::TRIGGER_INVITE_DECLINED
        );

        $invite->status = PLAN_INVITE_STATE::DECLINED->value;

        $DB->update_record(plan_helper::INVITES_TABLE, $invite);

        return array(
        'id' => $invite->id,
        'inviterid' => $invite->inviterid,
        'inviteeid' => $invite->inviteeid,
        'planid' => $invite->planid,
        'status' => $invite->status,
        'timestamp' => $invite->timestamp,
        );
    }


    public static function decline_invite_returns() {
        return new external_single_structure(
            array(
                'id' => new external_value(PARAM_INT, 'The id of the invite'),
                'inviterid' => new external_value(PARAM_INT, 'The id of the owner user'),
                'inviteeid' => new external_value(PARAM_INT, 'The id of the invited user'),
                'planid' => new external_value(PARAM_INT, 'The id of the plan'),
                'status' => new external_value(PARAM_INT, 'The Status of the invitation'),
                'timestamp' => new external_value(PARAM_INT, 'The time when the invitation was send'),
            )
        );
    }
}
