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
use local_lbplanner\helpers\plan_helper;
use local_lbplanner\helpers\notifications_helper;
use local_lbplanner\helpers\PLAN_ACCESS_TYPE;
use local_lbplanner\helpers\PLAN_INVITE_STATE;

/**
 * THIS METHOD IS NOT USED ANYMORE. JUST TO KEEP OLD CODE FOR REFERENCE.
 * TODO: delete
 */
class plan_update_invite extends external_api {
    public static function update_invite_parameters() {
        return new external_function_parameters([
        'planid' => new external_value(PARAM_INT, 'The id of the plan', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        'status' => new external_value(PARAM_INT, 'The status of the invite', VALUE_REQUIRED, null, NULL_NOT_ALLOWED),
        ]);
    }

    public static function update_invite($planid, $status) {
        global $DB, $USER;

        self::validate_parameters(self::update_invite_parameters(), [
        'planid' => $planid,
        'status' => $status,
        ]);

        $statusobj = PLAN_INVITE_STATE::tryFrom($status);

        if ($statusobj === null) {
            throw new \moodle_exception('Invalid status');
        }

        $invite = $DB->get_record(plan_helper::INVITES_TABLE,
        [
            'planid' => $planid,
            'inviteeid' => $USER->id,
        ],
        '*',
        MUST_EXIST
        );

        if ($invite->status != PLAN_INVITE_STATE::PENDING->value) {
            throw new \moodle_exception('Can\'t update non-pending status');
        }

        $invite->status = $status;

        $DB->update_record(plan_helper::INVITES_TABLE, $invite);

        $trigger = $statusobj === PLAN_INVITE_STATE::ACCEPTED ?
        notifications_helper::TRIGGER_INVITE_ACCEPTED
        : notifications_helper::TRIGGER_INVITE_DECLINED;

        notifications_helper::notify_user($invite->inviterid, $USER->id , $trigger);

        // TODO: Change plan access and delete old plan if inivite is accepted.

        if ($statusobj == PLAN_INVITE_STATE::ACCEPTED) {
            $oldplanid = plan_helper::get_plan_id($USER->id);

            if (plan_helper::get_owner($oldplanid) === $USER->id) {

                foreach (plan_helper::get_plan_members($oldplanid) as $member) {
                    if ($member->userid !== $USER->id) {
                        plan_leave_plan::leave_plan($member->userid, $oldplanid);
                    }
                }
                self::call_external_function('local_lbplanner_plan_clear_plan',  [$USER->id, $oldplanid]);

                $DB->delete_records(plan_helper::TABLE, ['id' => $oldplanid]);
            }

            $planaccess = $DB->get_record(
                plan_helper::ACCESS_TABLE,
                [
                    'planid' => $oldplanid,
                    'userid' => $USER->id,
                ],
                '*',
                MUST_EXIST
            );

            $planaccess->accesstype = PLAN_ACCESS_TYPE::READ->value;
            $planaccess->planid = $planid;

            $DB->update_record(plan_helper::ACCESS_TABLE, $planaccess);

            $DB->delete_records(plan_helper::INVITES_TABLE, ['id' => $invite->id]);
        }
        return [
            'inviterid' => $invite->inviterid,
            'inviteeid' => $invite->inviteeid,
            'planid' => $invite->planid,
            'status' => $invite->status,
            'timestamp' => $invite->timestamp,
        ];
    }


    public static function update_invite_returns() {
        return new external_single_structure(
            [
                'inviterid' => new external_value(PARAM_INT, 'The id of the owner user'),
                'inviteeid' => new external_value(PARAM_INT, 'The id of the invited user'),
                'planid' => new external_value(PARAM_INT, 'The id of the plan'),
                'status' => new external_value(PARAM_INT, 'The Status of the invitation'),
                'timestamp' => new external_value(PARAM_INT, 'The time when the invitation was send'),
            ]
        );
    }
}
