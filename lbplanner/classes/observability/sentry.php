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

namespace local_lbplanner\observability;

use Sentry\SentrySdk;
use Sentry\State\Scope;
use Sentry\Tracing\Transaction;
use Sentry\Tracing\TransactionContext;

/**
 * Sentry error and performance instrumentation for plugin entry points.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
final class sentry {
    /** Plugin configuration key for the Sentry DSN. */
    public const CONFIG_DSN = 'sentry_dsn';

    /** Plugin configuration key for the Sentry environment. */
    public const CONFIG_ENVIRONMENT = 'sentry_environment';

    /** Plugin configuration key for the performance trace sample rate. */
    public const CONFIG_TRACES_SAMPLE_RATE = 'sentry_traces_sample_rate';

    /** Whether the SDK has been initialized during this request. */
    private static bool $initialized = false;

    /**
     * Return whether Sentry has a configured DSN.
     *
     * @return bool Whether observability is enabled.
     */
    public static function enabled(): bool {
        return trim((string) get_config('local_lbplanner', self::CONFIG_DSN)) !== '';
    }

    /**
     * Run plugin code inside a Sentry transaction and capture uncaught errors.
     *
     * Telemetry failures are deliberately isolated so they cannot replace the
     * original function result or exception.
     *
     * @param string $name Transaction name.
     * @param string $operation Sentry operation name.
     * @param callable $callback Plugin code to execute.
     * @return mixed Callback result.
     */
    public static function run(string $name, string $operation, callable $callback): mixed {
        if (!self::enabled()) {
            return $callback();
        }

        $transaction = null;
        try {
            self::init();
            $context = TransactionContext::make()
                ->setName($name)
                ->setOp($operation);
            $transaction = \Sentry\startTransaction($context);
            SentrySdk::getCurrentHub()->setSpan($transaction);
        } catch (\Throwable) {
            self::report_internal_failure();
        }

        try {
            return $callback();
        } catch (\Throwable $error) {
            if (self::$initialized) {
                try {
                    \Sentry\captureException($error);
                } catch (\Throwable) {
                    self::report_internal_failure();
                }
            }
            throw $error;
        } finally {
            self::finish_transaction($transaction);
        }
    }

    /**
     * Initialize the Sentry SDK from Moodle configuration.
     *
     * @return void
     */
    private static function init(): void {
        global $USER;

        if (self::$initialized) {
            return;
        }

        $plugindir = dirname(__DIR__, 2);
        $autoload = $plugindir . '/vendor/autoload.php';
        if (!is_readable($autoload)) {
            throw new \coding_exception(get_string('err_sentry_missingvendor', 'local_lbplanner'));
        }
        require_once($autoload);

        $plugininfo = \core_plugin_manager::instance()->get_plugin_info('local_lbplanner');
        $release = $plugininfo === null ? 'unknown' : (string) $plugininfo->release;
        $environment = (string) get_config('local_lbplanner', self::CONFIG_ENVIRONMENT);
        if (!in_array($environment, ['development', 'production'], true)) {
            $environment = 'production';
        }

        $samplerateconfig = get_config('local_lbplanner', self::CONFIG_TRACES_SAMPLE_RATE);
        $samplerate = $samplerateconfig === false || $samplerateconfig === '' ? 0.2 : (float) $samplerateconfig;
        if ($samplerate < 0 || $samplerate > 1) {
            $samplerate = 0.2;
        }

        \Sentry\init([
            'dsn' => trim((string) get_config('local_lbplanner', self::CONFIG_DSN)),
            'environment' => $environment,
            'release' => 'local_lbplanner@' . $release,
            'in_app_include' => [$plugindir],
            'in_app_exclude' => [$plugindir . '/vendor'],
            'enable_tracing' => true,
            'traces_sample_rate' => $samplerate,
            'attach_stacktrace' => true,
            'send_default_pii' => false,
            'max_request_body_size' => 'never',
        ]);
        self::$initialized = true;

        if (isloggedin() && !isguestuser()) {
            \Sentry\configureScope(static function (Scope $scope) use ($USER): void {
                $scope->setUser(['id' => (string) $USER->id]);
            });
        }
    }

    /**
     * Finish a transaction without allowing telemetry to affect the API.
     *
     * @param Transaction|null $transaction Transaction to finish.
     * @return void
     */
    private static function finish_transaction(?Transaction $transaction): void {
        if ($transaction === null) {
            return;
        }
        try {
            $transaction->finish();
        } catch (\Throwable) {
            self::report_internal_failure();
        }
        try {
            SentrySdk::getCurrentHub()->setSpan(null);
        } catch (\Throwable) {
            self::report_internal_failure();
        }
    }

    /**
     * Surface an SDK problem only to developer-level Moodle debugging.
     *
     * @return void
     */
    private static function report_internal_failure(): void {
        try {
            debugging(get_string('err_sentry_internal', 'local_lbplanner'), DEBUG_DEVELOPER);
        } catch (\Throwable) {
            // Observability must never alter the plugin result or original error.
        }
    }
}
