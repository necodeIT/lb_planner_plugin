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
 * Defines some translation strings in english.
 *
 * @package local_lbplanner
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$string['capability_deprecated_unnecessary'] = 'This capability was removed because of internal changes making it unnecessary';
$string['cf_description'] = 'Tracks whether the task is GK/EK/TEST/M';
$string['cf_name'] = 'LB Planer Task Type';
$string['err_accessdenied'] = 'Access denied';
$string['err_cf_multidata'] = 'Found multiple data for module ID {$a->cmid} in category ID {$a->catid}';
$string['err_cf_nocatid'] = 'Couldn\'t find custom fields category ID';
$string['err_cf_nodata'] = 'Couldn\'t find any instance data for module ID {$a->cmid} in category ID {$a->catid}';
$string['err_color_nonhexadecimal'] = 'Incorrect color format - found non-hexadecimal character in color "{$a}"';
$string['err_color_wrongformat'] = 'Incorrect color format - must be either #RGB or #RRGGBB, got "{$a}" instead';
$string['err_color_wronglength'] = 'Incorrect color format - got incorrect length of {$a}';
$string['err_course_shortnamelength'] = 'Shortname length must be <=5 and >0, but is {$a} instead';
$string['err_dateformat'] = 'Invalid date formatting: got \'{$a}\', must be YYYY-MM-DD';
$string['err_doublechacheset'] = 'Tried to set cached {$a} twice';
$string['err_enum_capability_none'] =
    '0 means the absence of capabilities, and thus cannot be converted to a capability';
$string['err_enum_casevaluetype_unimp'] = 'Unimplemented case value type for Enum::format()';
$string['err_enum_namemissing'] = 'Name {$a->name} doesn\'t exist in {$a->classname}';
$string['err_invite_alr'] = 'Invite already {$a}';
$string['err_invite_alrinvited'] = 'Cannot invite user who is already been invited';
$string['err_invite_alrmember'] = 'Cannot invite user who is already a member';
$string['err_invite_notfound'] = 'Invitation does not exist';
$string['err_invite_yourself'] = 'Cannot invite yourself';
$string['err_mod_assnocm'] = 'Couldn\'t get course module with assignid {$a->assignid} and courseid {$a->courseid}';
$string['err_mod_assnocmid'] = 'Requested assignid, but no cmid is set';
$string['err_mod_cmidnoass'] = 'Requested cmid, but no assignid is set';
$string['err_mod_cmidnocm'] = 'Couldn\'t get course module with cmid {$a}';
$string['err_mod_nocmidnorass'] = 'Invalid module model: neither cmid nor assignid defined';
$string['err_notif_notfound'] = 'Notification does not exist';
$string['err_panic'] = 'PANIC';
$string['err_plan_cantleave_empty'] = 'Cannot Leave Plan: Plan must have at least one other member';
$string['err_plan_cantremove_owner'] = 'Cannot remove owner';
$string['err_plan_cantremove_userfromother'] = 'Cannot remove user from a plan they aren\'t in';
$string['err_plan_cantremove_yourself'] = 'Cannot remove yourself';
$string['err_plan_changeaccess_inval'] = 'Access type not valid';
$string['err_plan_changeaccess_ofowner'] = 'Cannot change permissions for the plan owner';
$string['err_plan_changeaccess_self'] = 'Cannot change own permissions';
$string['err_plan_changeaccess_toowner'] = 'Cannot change permission to owner';
$string['err_reserv_current'] = 'Schon laufende Slots können nicht reserviert werden';
$string['err_reserv_past'] = 'Can\'t reserve date in the past';
$string['err_reserv_slotfull'] = 'Slot is already full';
$string['err_reserv_studentalrin'] = 'Student already has a reservation for this slot';
$string['err_reserv_studentnoaccess'] = 'Student does not have access to this slot';
$string['err_reserv_toofuture'] = 'Date is past allowed date ({$a} days in the future)';
$string['err_reserv_unreserv_alrended'] = 'You can\'t unbook this reservation because it has already ended';
$string['err_reserv_unreserv_alrstarted'] = 'You can\'t unbook this reservation because it has already started';
$string['err_reserv_unreserv_alrstartedorforce'] =
    'Students can\'t unbook reservations that have already started. If you want to unbook this reservation regardless, force it.';
$string['err_sentry_transactcoll'] = 'Tried to start a new sentry transaction when there\'s already a span set';
$string['err_sentry_webservfalse'] = 'Webservice override: call_user_func_array returned with false at {$a}';
$string['err_slot_calcdatetime'] = 'Could not calculate slot datetime';
$string['err_slot_durationtoolarge'] = 'Slot start unit plus duration must be <={$a}';
$string['err_slot_durationtoosmall'] = 'Slot duration must be >=1';
$string['err_slot_overfull'] = 'Slot is now overfull';
$string['err_slot_reservnoexist'] = 'Reservation {$a} does not exist';
$string['err_slot_roomnametoolong'] = 'Room name has to be {$a} characters long or shorter';
$string['err_slot_roomnametooshort'] = 'Room name has to be at least 2 characters long';
$string['err_slot_roomsizetoosmall'] = 'Room size must be >=0';
$string['err_slot_startunittoolarge'] = 'Slot start unit must be <={$a}';
$string['err_slot_startunittoosmall'] = 'Slot start unit must be >=1';
$string['err_slot_urnotsupervisor'] = 'Insufficient Permission: you\'re not supervisor of this slot';
$string['err_slotfilter_bothnull'] = 'Courseid and vintage can\'t both be null';
$string['err_user_notfound'] = 'User is not registered in Eduplanner';
$string['invite_state_accepted'] = 'accepted';
$string['invite_state_declined'] = 'declined';
$string['invite_state_expired'] = 'expired';
$string['invite_state_pending'] = 'pending';
$string['lb_planner:admin'] = 'LB Planner Admin';
$string['lb_planner:manager'] = 'LB Planner Manager';
$string['lb_planner:slotmaster'] = 'LB Planner Slotmaster';
$string['lb_planner:student'] = 'LB Planner Student';
$string['lb_planner:teacher'] = 'LB Planner Teacher';
$string['plan_defaultname'] = 'Plan for {$a}';
$string['pluginname'] = 'LB Planner';
$string['sett_futuresight_desc'] = 'Maximum number of days in advance students can reserve slots. (0 = same day only)';
$string['sett_futuresight_title'] = 'Students\' reservation range';
$string['sett_outdaterange_desc'] = 'The maximum duration a course remains visible in EduPlanner after it ends.';
$string['sett_outdaterange_title'] = 'Courses\' outdated range';
$string['sett_panic_desc'] =
    'Turns API off - only use in emergencies. No data loss, but total loss of EduPlanner services until box is unchecked.';
$string['sett_panic_title'] = 'PANIC SWITCH';
$string['sett_sentrydsn_desc'] = 'For where to send error debugging info to. (Please ask the Pallasys team for a key)';
$string['sett_sentrydsn_title'] = 'Sentry DSN';
$string['unit_day'] = 'Day';
$string['unit_day_pl'] = 'Days';
