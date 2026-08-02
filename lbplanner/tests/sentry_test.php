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

use local_lbplanner\observability\sentry;

/**
 * Tests for the optional Sentry web-service instrumentation.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 * @covers \local_lbplanner\observability\sentry
 */
final class sentry_test extends \advanced_testcase {
    /** Disabled observability executes the callback without requiring the SDK. */
    public function test_disabled_sentry_is_transparent(): void {
        $this->resetAfterTest();
        unset_config(sentry::CONFIG_DSN, 'local_lbplanner');

        $this->assertFalse(sentry::enabled());
        $this->assertSame('result', sentry::run('test', 'webservice.server', static fn(): string => 'result'));

        set_config(sentry::CONFIG_DSN, '   ', 'local_lbplanner');
        $this->assertFalse(sentry::enabled());

        set_config(sentry::CONFIG_DSN, 'https://public@example.invalid/1', 'local_lbplanner');
        $this->assertTrue(sentry::enabled());
    }

    /** Disabled observability preserves the exact callback exception. */
    public function test_disabled_sentry_preserves_exception(): void {
        $this->resetAfterTest();
        unset_config(sentry::CONFIG_DSN, 'local_lbplanner');
        $expected = new \moodle_exception('invalidparameter');

        try {
            sentry::run('test', 'webservice.server', static function () use ($expected): void {
                throw $expected;
            });
            $this->fail('The callback exception was not thrown.');
        } catch (\Throwable $actual) {
            $this->assertSame($expected, $actual);
        }
    }

    /** The Moodle hook instruments only this plugin and invokes its callable exactly once. */
    public function test_webservice_hook_scope(): void {
        global $CFG;

        $this->resetAfterTest();
        unset_config(sentry::CONFIG_DSN, 'local_lbplanner');
        require_once($CFG->dirroot . '/local/lbplanner/lib.php');

        $other = (object) [
            'component' => 'core',
            'classname' => self::class,
            'methodname' => 'fixture_callback',
        ];
        $this->assertFalse(\local_lbplanner_override_webservice_execution($other, []));

        $own = clone $other;
        $own->component = 'local_lbplanner';
        $this->assertSame('called', \local_lbplanner_override_webservice_execution($own, []));
    }

    /**
     * Callable fixture for the web-service override test.
     *
     * @return string Stable result.
     */
    public static function fixture_callback(): string {
        return 'called';
    }
}
