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

/**
 * Tests for the destructive sync-only upgrade.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 * @coversNothing
 */
final class upgrade_test extends \advanced_testcase {
    /** Original Moodle site URL. */
    private string $originalwwwroot;

    /** Legacy tables removed by the 2.0 upgrade. */
    private const LEGACY_TABLES = [
        'local_lbplanner_kanbanentries',
        'local_lbplanner_notification',
        'local_lbplanner_plan_invites',
        'local_lbplanner_deadlines',
        'local_lbplanner_plan_access',
        'local_lbplanner_courses',
        'local_lbplanner_reservations',
        'local_lbplanner_slot_courses',
        'local_lbplanner_supervisors',
        'local_lbplanner_slots',
        'local_lbplanner_plans',
        'local_lbplanner_users',
    ];

    /** Remember global state changed for strict HTTPS contract calls. */
    protected function setUp(): void {
        global $CFG;

        parent::setUp();
        $this->originalwwwroot = $CFG->wwwroot;
    }

    /** Restore global site URL state. */
    protected function tearDown(): void {
        global $CFG;

        $CFG->wwwroot = $this->originalwwwroot;
        parent::tearDown();
    }

    /** The upgrade removes every application table while preserving classification config. */
    public function test_destructive_upgrade_removes_legacy_schema(): void {
        global $CFG, $DB;

        $this->resetAfterTest();
        $CFG->wwwroot = 'https://school.example/moodle';
        $dbman = $DB->get_manager();
        foreach (self::LEGACY_TABLES as $tablename) {
            $table = new \xmldb_table($tablename);
            $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
            $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
            $dbman->create_table($table);
        }

        classification::ensure_configuration();
        $categoryid = get_config('local_lbplanner', classification::CONFIG_CATEGORY_ID);
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'student');
        $assignment = $this->getDataGenerator()->create_module('assign', ['course' => $course->id]);
        $coursemoduleid = (int) get_coursemodule_from_instance(
            'assign',
            $assignment->id,
            $course->id,
            false,
            MUST_EXIST
        )->id;
        $this->set_classification($coursemoduleid, 'TEST');
        foreach ([
            'release',
            'release_fullnum',
            'panic',
            'slot_futuresight',
            'course_outdaterange',
        ] as $configkey) {
            set_config($configkey, 'legacy', 'local_lbplanner');
        }
        set_config('sentry_dsn', 'https://public@example.invalid/1', 'local_lbplanner');
        set_config('sentry_environment', 'development', 'local_lbplanner');
        set_config('sentry_traces_sample_rate', '0.5', 'local_lbplanner');

        require_once($CFG->dirroot . '/local/lbplanner/db/upgrade.php');
        xmldb_local_lbplanner_upgrade(202511210000);

        foreach (self::LEGACY_TABLES as $tablename) {
            $this->assertFalse($dbman->table_exists(new \xmldb_table($tablename)));
        }
        $this->assertSame($categoryid, get_config('local_lbplanner', classification::CONFIG_CATEGORY_ID));
        $this->assertSame('TEST', classification::get_key($coursemoduleid));
        foreach ([
            'release',
            'release_fullnum',
            'panic',
            'slot_futuresight',
            'course_outdaterange',
        ] as $configkey) {
            $this->assertFalse(get_config('local_lbplanner', $configkey));
        }
        $this->assertSame(
            'https://public@example.invalid/1',
            get_config('local_lbplanner', 'sentry_dsn')
        );
        $this->assertSame('development', get_config('local_lbplanner', 'sentry_environment'));
        $this->assertSame('0.5', get_config('local_lbplanner', 'sentry_traces_sample_rate'));

        $functions = [];
        $services = [];
        require($CFG->dirroot . '/local/lbplanner/db/services.php');
        $this->assertSame([
            'local_lbplanner_sync_get_identity',
            'local_lbplanner_sync_get_courses',
            'local_lbplanner_sync_get_assignments',
            'local_lbplanner_sync_get_quizzes',
        ], array_keys($functions));

        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_identity.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_courses.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_assignments.php');
        require_once($CFG->dirroot . '/local/lbplanner/services/sync/get_quizzes.php');
        $this->setUser($user);
        $this->assertNotEmpty(\local_lbplanner_services\sync_get_identity::get_identity());
        $this->assertCount(1, \local_lbplanner_services\sync_get_courses::get_courses());
        $this->assertCount(1, \local_lbplanner_services\sync_get_assignments::get_assignments());
        $this->assertSame([], \local_lbplanner_services\sync_get_quizzes::get_quizzes());
    }

    /**
     * Store a classification value whose core custom-field data must survive the upgrade.
     *
     * @param int $coursemoduleid Course-module ID.
     * @param string $key Supported classification key.
     * @return void
     */
    private function set_classification(int $coursemoduleid, string $key): void {
        $category = category_controller::create((int) get_config(
            'local_lbplanner',
            classification::CONFIG_CATEGORY_ID
        ));
        foreach ($category->get_handler()->get_instance_data($coursemoduleid, true) as $data) {
            if ($data->get_field()->get('shortname') === classification::FIELD_SHORTNAME) {
                $data->set($data->datafield(), $data->get_field()->parse_value($key));
                $data->save();
                return;
            }
        }
        $this->fail('Classification custom field was not found.');
    }
}
