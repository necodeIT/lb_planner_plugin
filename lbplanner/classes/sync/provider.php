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

namespace local_lbplanner\sync;

use local_lbplanner\external\sync_service;

/**
 * Read-only Moodle data provider for the EduPlanner sync contract.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
final class provider {
    /**
     * Return identity data for the token owner.
     *
     * @return array Contract-v1 identity.
     */
    public static function get_identity(): array {
        global $USER;

        $userid = self::current_user_id();
        $usercontext = \context_user::instance($userid);
        sync_service::validate_resource_context($usercontext);

        $label = trim(external_format_string(fullname($USER), $usercontext->id));
        if ($label === '') {
            $label = trim(clean_param((string) $USER->username, PARAM_TEXT));
        }
        if ($label === '') {
            $label = (string) $userid;
        }

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('local_lbplanner');
        if ($plugininfo === null || trim((string) $plugininfo->release) === '') {
            throw new \moodle_exception('err_sync_invalidreference', 'local_lbplanner');
        }

        return [
            'siteUrl' => contract::site_url(),
            'remoteUserId' => contract::decimal_id($userid),
            'displayLabel' => $label,
            'pluginRelease' => (string) $plugininfo->release,
            'contractVersion' => contract::VERSION,
            'supportedResources' => contract::SUPPORTED_RESOURCES,
        ];
    }

    /**
     * Return all accessible enrolled courses and group memberships.
     *
     * @return array Contract-v1 course rows.
     */
    public static function get_courses(): array {
        return self::load_courses()['rows'];
    }

    /**
     * Return all visible assignments.
     *
     * @return array Contract-v1 assignment rows.
     */
    public static function get_assignments(): array {
        return self::get_activities('assign');
    }

    /**
     * Return all visible quizzes.
     *
     * @return array Contract-v1 quiz rows.
     */
    public static function get_quizzes(): array {
        return self::get_activities('quiz');
    }

    /**
     * Load the current user's complete course snapshot.
     *
     * @return array Rows plus Moodle course records and their string identities.
     */
    private static function load_courses(): array {
        global $CFG;

        require_once($CFG->dirroot . '/group/lib.php');

        $userid = self::current_user_id();
        $courses = enrol_get_my_courses(['id', 'fullname', 'timecreated', 'startdate', 'enddate']);
        $rows = [];
        $records = [];
        $seen = [];

        foreach ($courses as $course) {
            $externalid = contract::decimal_id($course->id);
            contract::assert_new_identity($seen, $externalid);

            $coursecontext = \context_course::instance($course->id);
            sync_service::validate_resource_context($coursecontext);
            $fullcourse = get_course($course->id);

            $groups = [];
            $seengroups = [];
            foreach (groups_get_all_groups($course->id, $userid) as $group) {
                $groupexternalid = contract::decimal_id($group->id);
                contract::assert_new_identity($seengroups, $groupexternalid);
                $groups[] = [
                    'externalId' => $groupexternalid,
                    'name' => external_format_string($group->name, $coursecontext->id),
                ];
            }
            usort(
                $groups,
                fn(array $left, array $right): int => contract::compare_ids($left['externalId'], $right['externalId'])
            );

            $rows[] = [
                'externalId' => $externalid,
                'name' => external_format_string($fullcourse->fullname, $coursecontext->id),
                'createdAt' => (int) $fullcourse->timecreated,
                'startDate' => (int) $fullcourse->startdate,
                'endDate' => contract::nullable_timestamp($fullcourse->enddate),
                'url' => contract::moodle_url('/course/view.php', ['id' => $fullcourse->id]),
                'groups' => $groups,
            ];
            $records[] = [
                'externalId' => $externalid,
                'course' => $fullcourse,
            ];
        }

        usort(
            $rows,
            fn(array $left, array $right): int => contract::compare_ids($left['externalId'], $right['externalId'])
        );
        return ['rows' => $rows, 'records' => $records];
    }

    /**
     * Return assignment or quiz resource rows.
     *
     * @param string $modulename assign or quiz.
     * @return array Contract-v1 activity rows.
     */
    private static function get_activities(string $modulename): array {
        global $CFG, $DB;

        if (!in_array($modulename, ['assign', 'quiz'], true)) {
            throw new \coding_exception('Unsupported sync activity module.');
        }
        if ($modulename === 'assign') {
            require_once($CFG->dirroot . '/mod/assign/locallib.php');
        }

        $userid = self::current_user_id();
        $courses = self::load_courses()['records'];
        $rows = [];
        $seen = [];

        foreach ($courses as $courseentry) {
            $courseexternalid = $courseentry['externalId'];
            $course = $courseentry['course'];
            $coursecontext = \context_course::instance($course->id);
            sync_service::validate_resource_context($coursecontext);
            $completion = new \completion_info($course);
            $modinfo = get_fast_modinfo($course, $userid);

            foreach ($modinfo->get_instances_of($modulename) as $instanceid => $coursemodule) {
                if (!$coursemodule->uservisible) {
                    continue;
                }

                $externalid = contract::decimal_id($instanceid);
                contract::assert_new_identity($seen, $externalid);
                if (
                    (int) $coursemodule->course !== (int) $course->id ||
                    (int) $coursemodule->instance !== (int) $instanceid
                ) {
                    throw new \moodle_exception('err_sync_invalidreference', 'local_lbplanner');
                }

                $modulecontext = \context_module::instance($coursemodule->id);
                sync_service::validate_resource_context($modulecontext);
                $instance = $DB->get_record($modulename, ['id' => $instanceid, 'course' => $course->id], '*', MUST_EXIST);

                [$description] = external_format_text(
                    $instance->intro,
                    $instance->introformat,
                    $modulecontext->id,
                    'mod_' . $modulename,
                    'intro',
                    null
                );

                $completionstate = 0;
                if ($completion->is_enabled($coursemodule)) {
                    $completionstate = (int) $completion->get_data($coursemodule, false, $userid)->completionstate;
                }

                $submitted = false;
                $finishedattempt = false;
                if ($modulename === 'assign') {
                    $submitted = self::assignment_is_submitted(
                        $instance,
                        $course,
                        $coursemodule,
                        $modulecontext,
                        $userid
                    );
                    $deadline = contract::nullable_timestamp($instance->duedate);
                } else {
                    $finishedattempt = $DB->record_exists('quiz_attempts', [
                        'quiz' => $instance->id,
                        'userid' => $userid,
                        'state' => 'finished',
                        'preview' => 0,
                    ]);
                    $deadline = contract::nullable_timestamp($instance->timeclose);
                }

                $rows[] = [
                    'externalId' => $externalid,
                    'courseExternalId' => $courseexternalid,
                    'courseModuleId' => contract::decimal_id($coursemodule->id),
                    'name' => external_format_string($instance->name, $modulecontext->id),
                    'description' => $description,
                    'deadline' => $deadline,
                    'url' => contract::moodle_url(
                        '/mod/' . $modulename . '/view.php',
                        ['id' => $coursemodule->id]
                    ),
                    'completionState' => $completionstate,
                    'submitted' => $submitted,
                    'finishedAttempt' => $finishedattempt,
                    'classificationKey' => classification::get_key($coursemodule->id),
                ];
            }
        }

        usort($rows, function (array $left, array $right): int {
            $coursecomparison = contract::compare_ids($left['courseExternalId'], $right['courseExternalId']);
            return $coursecomparison !== 0
                ? $coursecomparison
                : contract::compare_ids($left['externalId'], $right['externalId']);
        });
        return $rows;
    }

    /**
     * Determine the latest effective individual or team submission state.
     *
     * @param \stdClass $instance Assignment record.
     * @param \stdClass $course Course record.
     * @param \cm_info $coursemodule Course-module information.
     * @param \context_module $context Module context.
     * @param int $userid Current token-owner ID.
     * @return bool Whether the effective submission is submitted.
     */
    private static function assignment_is_submitted(
        \stdClass $instance,
        \stdClass $course,
        \cm_info $coursemodule,
        \context_module $context,
        int $userid
    ): bool {
        $assignment = new \assign($context, $coursemodule, $course);
        if (!empty($instance->teamsubmission)) {
            $submission = $assignment->get_group_submission($userid, 0, false);
        } else {
            $submission = $assignment->get_user_submission($userid, false);
        }
        return $submission !== false && $submission->status === ASSIGN_SUBMISSION_STATUS_SUBMITTED;
    }

    /**
     * Return the only user identity permitted by the sync contract.
     *
     * @return int Current token-owner ID.
     */
    private static function current_user_id(): int {
        global $USER;

        $userid = (int) ($USER->id ?? 0);
        if ($userid <= 0) {
            throw new \moodle_exception('err_sync_unauthorized', 'local_lbplanner');
        }
        return $userid;
    }
}
