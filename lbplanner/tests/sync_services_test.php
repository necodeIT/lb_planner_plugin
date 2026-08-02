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

namespace local_lbplanner;

use core_customfield\category_controller;
use local_lbplanner\sync\classification;
use local_lbplanner\sync\contract;
use local_lbplanner\sync\provider;

/**
 * Contract tests for the sync-only external service.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 * @covers \local_lbplanner\sync\provider
 * @covers \local_lbplanner\sync\classification
 */
final class sync_services_test extends \advanced_testcase {
    /** Original Moodle site URL. */
    private string $originalwwwroot;

    /** Load service classes and use a contract-compatible site URL. */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->resetAfterTest();
        $this->originalwwwroot = $CFG->wwwroot;
        $CFG->wwwroot = 'https://school.example/moodle';
        classification::ensure_configuration();

        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_identity.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_courses.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_assignments.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_quizzes.php');
    }

    /** Restore the global site URL. */
    protected function tearDown(): void {
        global $CFG;

        $CFG->wwwroot = $this->originalwwwroot;
        parent::tearDown();
    }

    /** The installed service exposes exactly the four parameterless read functions. */
    public function test_service_definition_is_exact(): void {
        global $CFG;

        $functions = [];
        $services = [];
        require($CFG->dirroot . '/local/lbplanner/db/services.php');

        $expected = [
            'local_lbplanner_sync_get_identity',
            'local_lbplanner_sync_get_courses',
            'local_lbplanner_sync_get_assignments',
            'local_lbplanner_sync_get_quizzes',
        ];
        $this->assertSame($expected, array_keys($functions));
        $this->assertSame($expected, $services['LB Planner Sync API']['functions']);
        $this->assertSame(contract::CAPABILITY, $services['LB Planner Sync API']['requiredcapability']);
        foreach ($functions as $definition) {
            $this->assertSame('read', $definition['type']);
            $this->assertFalse($definition['ajax']);
            $this->assertSame(contract::CAPABILITY, $definition['capabilities']);
        }
        $this->assertSame([], \local_lbplanner_services\sync_get_identity::get_identity_parameters()->keys);
        $this->assertSame([], \local_lbplanner_services\sync_get_courses::get_courses_parameters()->keys);
        $this->assertSame([], \local_lbplanner_services\sync_get_assignments::get_assignments_parameters()->keys);
        $this->assertSame([], \local_lbplanner_services\sync_get_quizzes::get_quizzes_parameters()->keys);
        foreach (['get_identity', 'get_courses', 'get_assignments', 'get_quizzes'] as $methodname) {
            $this->assertSame(0, (new \ReflectionMethod(provider::class, $methodname))->getNumberOfParameters());
        }

        $capabilities = [];
        require($CFG->dirroot . '/local/lbplanner/db/access.php');
        $this->assertSame([contract::CAPABILITY], array_keys($capabilities));
        $this->assertSame('read', $capabilities[contract::CAPABILITY]['captype']);
        $this->assertSame(CONTEXT_SYSTEM, $capabilities[contract::CAPABILITY]['contextlevel']);
        $this->assertSame(CAP_ALLOW, $capabilities[contract::CAPABILITY]['archetypes']['user']);
        $this->assertFileDoesNotExist($CFG->dirroot . '/local/lbplanner/db/install.xml');
    }

    /** Identity is token-scoped, deterministic, and rejects non-HTTPS configuration. */
    public function test_identity_contract_and_https_validation(): void {
        global $CFG;

        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Ada',
            'lastname' => 'Student',
        ]);
        $this->setUser($user);

        $identity = \local_lbplanner_services\sync_get_identity::get_identity();
        $this->assertSame('https://school.example/moodle', $identity['siteUrl']);
        $this->assertSame((string) $user->id, $identity['remoteUserId']);
        $this->assertSame('Ada Student', $identity['displayLabel']);
        $this->assertSame('2.0.0', $identity['pluginRelease']);
        $this->assertSame(1, $identity['contractVersion']);
        $this->assertSame(['assignments', 'courses', 'groups', 'quizzes'], $identity['supportedResources']);

        $cleaned = \core_external\external_api::clean_returnvalue(
            \local_lbplanner_services\sync_get_identity::get_identity_returns(),
            $identity
        );
        $this->assertSame($identity, $cleaned);

        $CFG->wwwroot = 'http://school.example/moodle';
        $this->expectException(\moodle_exception::class);
        provider::get_identity();
    }

    /** Empty formatted names safely fall back to the token owner's username. */
    public function test_identity_safe_label_fallback(): void {
        global $USER;

        $user = $this->getDataGenerator()->create_user(['username' => 'safe-label']);
        $this->setUser($user);
        $USER->firstname = '';
        $USER->lastname = '';
        $USER->middlename = '';
        $USER->alternatename = '';

        $this->assertSame('safe-label', provider::get_identity()['displayLabel']);
    }

    /** Guests cannot invoke sync functions. */
    public function test_guest_is_rejected(): void {
        $this->setGuestUser();
        $this->expectException(\moodle_exception::class);
        \local_lbplanner_services\sync_get_identity::get_identity();
    }

    /** A system-level capability prohibition denies every sync function. */
    public function test_sync_capability_is_required(): void {
        $user = $this->getDataGenerator()->create_user();
        $roles = get_archetype_roles('user');
        $role = reset($roles);
        assign_capability(
            contract::CAPABILITY,
            CAP_PROHIBIT,
            $role->id,
            \context_system::instance()->id,
            true
        );
        accesslib_clear_all_caches_for_unit_testing();
        $this->setUser($user);

        $this->expectException(\required_capability_exception::class);
        \local_lbplanner_services\sync_get_courses::get_courses();
    }

    /** Lexicographic ordering and duplicate rejection are contract-wide behavior. */
    public function test_contract_ordering_and_duplicate_detection(): void {
        $this->assertLessThan(0, contract::compare_ids('10', '2'));
        $assignmentseen = [];
        $quizseen = [];
        contract::assert_new_identity($assignmentseen, '10');
        contract::assert_new_identity($quizseen, '10');
        $this->assertSame($assignmentseen, $quizseen);

        $this->expectException(\moodle_exception::class);
        contract::assert_new_identity($assignmentseen, '10');
    }

    /** Course and nested group snapshots are complete, isolated, and lexicographically sorted. */
    public function test_course_and_group_snapshot(): void {
        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $coursea = $this->getDataGenerator()->create_course([
            'fullname' => 'Course A',
            'startdate' => 1714521600,
            'enddate' => 0,
        ]);
        $courseb = $this->getDataGenerator()->create_course(['fullname' => 'Course B']);
        $unenrolledcourse = $this->getDataGenerator()->create_course(['fullname' => 'Not enrolled']);
        $hiddencourse = $this->getDataGenerator()->create_course([
            'fullname' => 'Accessible hidden course',
            'visible' => 0,
        ]);
        $this->getDataGenerator()->enrol_user($user->id, $coursea->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $courseb->id, 'student');
        $this->getDataGenerator()->enrol_user($user->id, $hiddencourse->id, 'editingteacher');

        $groupb = $this->getDataGenerator()->create_group(['courseid' => $coursea->id, 'name' => 'Group B']);
        $groupa = $this->getDataGenerator()->create_group(['courseid' => $coursea->id, 'name' => 'Group A']);
        $othergroup = $this->getDataGenerator()->create_group(['courseid' => $coursea->id, 'name' => 'Other']);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupb->id, 'userid' => $user->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $groupa->id, 'userid' => $user->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $othergroup->id, 'userid' => $otheruser->id]);

        $this->setUser($user);
        $courses = \local_lbplanner_services\sync_get_courses::get_courses();
        $courseids = array_column($courses, 'externalId');
        foreach ($courses as $courserow) {
            $this->assertIsString($courserow['externalId']);
            foreach ($courserow['groups'] as $grouprow) {
                $this->assertIsString($grouprow['externalId']);
            }
        }
        $sortedcourseids = $courseids;
        usort($sortedcourseids, 'strcmp');
        $this->assertSame($sortedcourseids, $courseids);
        $this->assertNotContains((string) $unenrolledcourse->id, $courseids);
        $this->assertContains((string) $hiddencourse->id, $courseids);

        $coursearow = $this->find_row($courses, (string) $coursea->id);
        $this->assertNull($coursearow['endDate']);
        $this->assertSame(1714521600, $coursearow['startDate']);
        $expectedgroupids = [(string) $groupa->id, (string) $groupb->id];
        usort($expectedgroupids, 'strcmp');
        $this->assertSame($expectedgroupids, array_column($coursearow['groups'], 'externalId'));
        $this->assertStringStartsWith('https://school.example/moodle/course/view.php', $coursearow['url']);

        $cleaned = \core_external\external_api::clean_returnvalue(
            \local_lbplanner_services\sync_get_courses::get_courses_returns(),
            $courses
        );
        $this->assertSame($courses, $cleaned);
    }

    /** Assignment and quiz resources retain separate identity and per-user state. */
    public function test_assignment_and_quiz_snapshots(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course([
            'enablecompletion' => 1,
        ]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($otheruser->id, $course->id, 'student');

        $assign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Chapter exercise',
            'intro' => '<script>alert("unsafe")</script><p><strong>Read this</strong></p>',
            'introformat' => FORMAT_HTML,
            'duedate' => 1770000000,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $hiddenassign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Hidden assignment',
            'visible' => 0,
        ]);
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'name' => 'Practice quiz',
            'intro' => '<p>Quiz description</p>',
            'introformat' => FORMAT_HTML,
            'timeclose' => 0,
            'completion' => COMPLETION_TRACKING_MANUAL,
        ]);
        $assigncmid = $this->course_module_id('assign', $assign->id, $course->id);
        $hiddencmid = $this->course_module_id('assign', $hiddenassign->id, $course->id);
        $quizcmid = $this->course_module_id('quiz', $quiz->id, $course->id);
        $this->set_classification($assigncmid, 'TEST');
        $this->set_classification($hiddencmid, 'EK');
        $this->set_classification($quizcmid, 'GK');

        $now = time();
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $assign->id,
            'userid' => $user->id,
            'timecreated' => $now,
            'timemodified' => $now,
            'status' => 'submitted',
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
        ]);
        $DB->insert_record('course_modules_completion', (object) [
            'coursemoduleid' => $assigncmid,
            'userid' => $user->id,
            'completionstate' => COMPLETION_COMPLETE,
            'viewed' => 0,
            'overrideby' => null,
            'timemodified' => $now,
        ]);
        $this->create_quiz_attempt($quiz->id, $quizcmid, $otheruser->id, 'finished');
        $this->create_quiz_attempt($quiz->id, $quizcmid, $user->id, 'inprogress');

        rebuild_course_cache($course->id, true);
        $this->setUser($user);
        $assignments = \local_lbplanner_services\sync_get_assignments::get_assignments();
        $quizzes = \local_lbplanner_services\sync_get_quizzes::get_quizzes();

        $this->assertCount(1, $assignments);
        $this->assertCount(1, $quizzes);
        foreach ([$assignments[0], $quizzes[0]] as $activityrow) {
            $this->assertIsString($activityrow['externalId']);
            $this->assertIsString($activityrow['courseExternalId']);
            $this->assertIsString($activityrow['courseModuleId']);
        }
        $this->assertSame((string) $assign->id, $assignments[0]['externalId']);
        $this->assertSame('TEST', $assignments[0]['classificationKey']);
        $this->assertTrue($assignments[0]['submitted']);
        $this->assertFalse($assignments[0]['finishedAttempt']);
        $this->assertSame(COMPLETION_COMPLETE, $assignments[0]['completionState']);
        $this->assertStringContainsString('<strong>Read this</strong>', $assignments[0]['description']);
        $this->assertStringNotContainsString('<script', $assignments[0]['description']);
        $this->assertSame(1770000000, $assignments[0]['deadline']);
        $this->assertSame('GK', $quizzes[0]['classificationKey']);
        $this->assertFalse($quizzes[0]['submitted']);
        $this->assertFalse($quizzes[0]['finishedAttempt']);
        $this->assertNull($quizzes[0]['deadline']);
        $courseids = array_column(\local_lbplanner_services\sync_get_courses::get_courses(), 'externalId');
        $this->assertContains($assignments[0]['courseExternalId'], $courseids);
        $this->assertContains($quizzes[0]['courseExternalId'], $courseids);

        $this->assertSame(
            $assignments,
            \core_external\external_api::clean_returnvalue(
                \local_lbplanner_services\sync_get_assignments::get_assignments_returns(),
                $assignments
            )
        );
        $this->assertSame(
            $quizzes,
            \core_external\external_api::clean_returnvalue(
                \local_lbplanner_services\sync_get_quizzes::get_quizzes_returns(),
                $quizzes
            )
        );
    }

    /** Finished current-user quiz attempts and effective assignment attempts are resolved exactly. */
    public function test_effective_submission_and_attempt_states(): void {
        global $DB;

        $user = $this->getDataGenerator()->create_user();
        $otheruser = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course(['groupmode' => SEPARATEGROUPS]);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $this->getDataGenerator()->enrol_user($otheruser->id, $course->id, 'student');
        $group = $this->getDataGenerator()->create_group(['courseid' => $course->id]);
        $this->getDataGenerator()->create_group_member(['groupid' => $group->id, 'userid' => $user->id]);

        $teamassign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Team assignment',
            'teamsubmission' => 1,
        ]);
        $individualassign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Reopened assignment',
        ]);
        $otherassign = $this->getDataGenerator()->create_module('assign', [
            'course' => $course->id,
            'name' => 'Other user assignment',
        ]);
        $quiz = $this->getDataGenerator()->create_module('quiz', [
            'course' => $course->id,
            'name' => 'Finished quiz',
            'timeclose' => 1771000000,
        ]);
        $teamcmid = $this->course_module_id('assign', $teamassign->id, $course->id);
        $individualcmid = $this->course_module_id('assign', $individualassign->id, $course->id);
        $othercmid = $this->course_module_id('assign', $otherassign->id, $course->id);
        $quizcmid = $this->course_module_id('quiz', $quiz->id, $course->id);
        $this->set_classification($teamcmid, 'M');
        $this->set_classification($individualcmid, 'EK');
        $this->set_classification($othercmid, 'GK');
        $this->set_classification($quizcmid, 'GK');

        $now = time();
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $teamassign->id,
            'userid' => 0,
            'groupid' => $group->id,
            'attemptnumber' => 0,
            'latest' => 1,
            'status' => 'submitted',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        foreach ([0 => 'submitted', 1 => 'draft'] as $attemptnumber => $status) {
            $DB->insert_record('assign_submission', (object) [
                'assignment' => $individualassign->id,
                'userid' => $user->id,
                'groupid' => 0,
                'attemptnumber' => $attemptnumber,
                'latest' => (int) ($attemptnumber === 1),
                'status' => $status,
                'timecreated' => $now,
                'timemodified' => $now,
            ]);
        }
        $DB->insert_record('assign_submission', (object) [
            'assignment' => $otherassign->id,
            'userid' => $otheruser->id,
            'groupid' => 0,
            'attemptnumber' => 0,
            'latest' => 1,
            'status' => 'submitted',
            'timecreated' => $now,
            'timemodified' => $now,
        ]);
        $this->create_quiz_attempt($quiz->id, $quizcmid, $user->id, 'finished');

        rebuild_course_cache($course->id, true);
        $this->setUser($user);
        $assignments = \local_lbplanner_services\sync_get_assignments::get_assignments();
        $quizzes = \local_lbplanner_services\sync_get_quizzes::get_quizzes();

        $this->assertTrue($this->find_row($assignments, (string) $teamassign->id)['submitted']);
        $this->assertFalse($this->find_row($assignments, (string) $individualassign->id)['submitted']);
        $this->assertFalse($this->find_row($assignments, (string) $otherassign->id)['submitted']);
        $quizrow = $this->find_row($quizzes, (string) $quiz->id);
        $this->assertTrue($quizrow['finishedAttempt']);
        $this->assertSame(1771000000, $quizrow['deadline']);
    }

    /** Hidden and unavailable modules are excluded, while accessible stealth modules are included. */
    public function test_activity_visibility_and_availability(): void {
        global $CFG;

        $CFG->enableavailability = true;
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $futureavailability = json_encode([
            'op' => '&',
            'c' => [['type' => 'date', 'd' => '>=', 't' => time() + DAYSECS]],
            'showc' => [false],
        ]);

        foreach (['assign', 'quiz'] as $modulename) {
            $this->getDataGenerator()->create_module($modulename, [
                'course' => $course->id,
                'name' => $modulename . ' visible',
            ]);
            $this->getDataGenerator()->create_module($modulename, [
                'course' => $course->id,
                'name' => $modulename . ' stealth',
                'visible' => 1,
                'visibleoncoursepage' => 0,
            ]);
            $this->getDataGenerator()->create_module($modulename, [
                'course' => $course->id,
                'name' => $modulename . ' hidden',
                'visible' => 0,
            ]);
            $this->getDataGenerator()->create_module($modulename, [
                'course' => $course->id,
                'name' => $modulename . ' unavailable',
                'availability' => $futureavailability,
            ]);
        }

        rebuild_course_cache($course->id, true);
        $this->setUser($user);
        $assignments = \local_lbplanner_services\sync_get_assignments::get_assignments();
        $quizzes = \local_lbplanner_services\sync_get_quizzes::get_quizzes();

        $this->assertEqualsCanonicalizing(
            ['assign visible', 'assign stealth'],
            array_column($assignments, 'name')
        );
        $this->assertEqualsCanonicalizing(
            ['quiz visible', 'quiz stealth'],
            array_column($quizzes, 'name')
        );
    }

    /** Every supported raw classification key is returned without module-type inference. */
    public function test_all_supported_classifications(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');

        foreach (contract::CLASSIFICATION_KEYS as $key) {
            $assignment = $this->getDataGenerator()->create_module('assign', [
                'course' => $course->id,
                'name' => $key,
            ]);
            $cmid = $this->course_module_id('assign', $assignment->id, $course->id);
            $this->set_classification($cmid, $key);
        }

        rebuild_course_cache($course->id, true);
        $this->setUser($user);
        $actual = array_column(
            \local_lbplanner_services\sync_get_assignments::get_assignments(),
            'classificationKey',
            'name'
        );
        foreach (contract::CLASSIFICATION_KEYS as $key) {
            $this->assertSame($key, $actual[$key]);
        }
    }

    /** Empty classifications return null while malformed stored values fail the whole resource. */
    public function test_missing_and_unsupported_classification(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $assign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $cmid = $this->course_module_id('assign', $assign->id, $course->id);
        $validassign = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $validcmid = $this->course_module_id('assign', $validassign->id, $course->id);
        $this->set_classification($validcmid, 'GK');
        $this->setUser($user);

        $assignments = \local_lbplanner_services\sync_get_assignments::get_assignments();
        $this->assertNull($this->find_row($assignments, (string) $assign->id)['classificationKey']);
        $this->set_classification_raw($cmid, 99);
        rebuild_course_cache($course->id, true);

        $this->expectException(\moodle_exception::class);
        \local_lbplanner_services\sync_get_assignments::get_assignments();
    }

    /** Missing field configuration is a hard failure. */
    public function test_missing_classification_configuration(): void {
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $coursemoduleid = $this->course_module_id('assign', $assignment->id, $course->id);
        unset_config(classification::CONFIG_CATEGORY_ID, 'local_lbplanner');

        $this->expectException(\moodle_exception::class);
        classification::get_key($coursemoduleid);
    }

    /** Orphan module references are hard failures. */
    public function test_orphan_module_reference(): void {
        $this->expectException(\dml_missing_record_exception::class);
        classification::get_key(999999999);
    }

    /**
     * Return the row with the requested identity.
     *
     * @param array $rows Resource rows.
     * @param string $externalid Requested identity.
     * @return array Matching row.
     */
    private function find_row(array $rows, string $externalid): array {
        foreach ($rows as $row) {
            if ($row['externalId'] === $externalid) {
                return $row;
            }
        }
        $this->fail('Expected external identity was not present.');
    }

    /**
     * Resolve a course-module ID from an activity instance.
     *
     * @param string $modulename Module name.
     * @param int $instanceid Activity instance ID.
     * @param int $courseid Course ID.
     * @return int Course-module ID.
     */
    private function course_module_id(string $modulename, int $instanceid, int $courseid): int {
        return (int) get_coursemodule_from_instance($modulename, $instanceid, $courseid, false, MUST_EXIST)->id;
    }

    /**
     * Create a structurally valid quiz attempt without requiring quiz questions.
     *
     * @param int $quizid Quiz instance ID.
     * @param int $coursemoduleid Course-module ID.
     * @param int $userid Attempt owner ID.
     * @param string $state Moodle quiz-attempt state.
     * @return void
     */
    private function create_quiz_attempt(int $quizid, int $coursemoduleid, int $userid, string $state): void {
        global $CFG, $DB;

        require_once($CFG->dirroot . '/question/engine/lib.php');
        $context = \context_module::instance($coursemoduleid);
        $usage = \question_engine::make_questions_usage_by_activity('mod_quiz', $context);
        $usage->set_preferred_behaviour('deferredfeedback');
        \question_engine::save_questions_usage_by_activity($usage);
        $now = time();
        $DB->insert_record('quiz_attempts', (object) [
            'quiz' => $quizid,
            'userid' => $userid,
            'attempt' => 1,
            'uniqueid' => $usage->get_id(),
            'layout' => '',
            'currentpage' => 0,
            'preview' => 0,
            'state' => $state,
            'timestart' => $now,
            'timefinish' => $state === 'finished' ? $now : 0,
            'timemodified' => $now,
            'timemodifiedoffline' => 0,
            'timecheckstate' => null,
            'sumgrades' => null,
        ]);
    }

    /**
     * Store a supported classification selection.
     *
     * @param int $coursemoduleid Course-module ID.
     * @param string $key Supported key.
     * @return void
     */
    private function set_classification(int $coursemoduleid, string $key): void {
        $category = category_controller::create((int) get_config('local_lbplanner', classification::CONFIG_CATEGORY_ID));
        foreach ($category->get_handler()->get_instance_data($coursemoduleid, true) as $data) {
            if ($data->get_field()->get('shortname') === classification::FIELD_SHORTNAME) {
                $value = $data->get_field()->parse_value($key);
                $data->set($data->datafield(), $value);
                $data->save();
                return;
            }
        }
        $this->fail('Classification custom field was not found.');
    }

    /**
     * Store an arbitrary raw select index for failure tests.
     *
     * @param int $coursemoduleid Course-module ID.
     * @param int $value Stored select index.
     * @return void
     */
    private function set_classification_raw(int $coursemoduleid, int $value): void {
        $category = category_controller::create((int) get_config('local_lbplanner', classification::CONFIG_CATEGORY_ID));
        foreach ($category->get_handler()->get_instance_data($coursemoduleid, true) as $data) {
            if ($data->get_field()->get('shortname') === classification::FIELD_SHORTNAME) {
                $data->set($data->datafield(), $value);
                $data->save();
                return;
            }
        }
        $this->fail('Classification custom field was not found.');
    }
}
