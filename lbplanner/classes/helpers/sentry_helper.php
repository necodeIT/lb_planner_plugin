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

use Throwable;
use local_lbplanner\enums\{ENVIRONMENT, SETTINGS};
use local_lbplanner\helpers\config_helper;
use Sentry\SentrySdk;
use Sentry\Tracing\{Span, SpanContext, Transaction, TransactionContext};

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/local/lbplanner/vendor/autoload.php');

/**
 * Provides helper methods for sentry logging
 *
 * @package local_lbplanner
 * @subpackage helpers
 * @copyright 2025 necodeIT
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
class sentry_helper {
    /**
     * @var array $spans Remembers what spans have been in use.
     */
    private static array $spans = [];

    /**
     * This cache is needed because the value is read fairly often and determining it is somewhat expensive.
     * @var bool $isenabled cache for enabled/disabled state of sentry reporting.
     */
    private static ?bool $isenabled = null;

    /**
     * Checks if moodle plugin is set to report exceptions to sentry
     * @return bool whether sentry is to be used
     */
    public static function enabled(): bool {
        if (self::$isenabled === null) {
            self::$isenabled = strlen(config_helper::get_sentry_dsn()) > 0;
        }
        return self::$isenabled;
    }
    /**
     * Initializes the sentry library for future use.
     */
    public static function init(): void {
        if (self::enabled()) {
            $env = get_config('local_lbplanner', SETTINGS::SENTRY_ENV);
            $release = get_config('local_lbplanner', SETTINGS::V_RELEASE);
            if ($env === ENVIRONMENT::DEV) {
                $release .= '.' . get_config('local_lbplanner', SETTINGS::V_FULLNUM);
            }
            $cfg = [
                "dsn" => config_helper::get_sentry_dsn(),
                'in_app_include' => [realpath(__DIR__ . '/../..')],
                'in_app_exclude' => [realpath(__DIR__ . '/../../vendor'), '/'],
                "enable_tracing" => true,
                "traces_sample_rate" => 0.2,
                "attach_stacktrace" => true,
                "release" => 'lbplanner@' . $release,
                "environment" => $env,
            ];
            \Sentry\init($cfg);
        }
    }
    /**
     * Does a bunch of setup for measuring transaction duration.
     * @param string $name name of the transaction to start
     * @param string $op the operation this transaction is for
     * @param ?array $data an assocarr of data to record for this transaction, or null
     * @return ?Transaction the transaction that got started, or null if disabled
     */
    public static function transaction_start(string $name, string $op, ?array $data = null): ?Transaction {
        if (self::enabled()) {
            if (SentrySdk::getCurrentHub()->getSpan() !== null) {
                throw new \coding_exception(get_string('err_sentry_transactcoll', 'local_lbplanner'));
            }
            $ctx = TransactionContext::make()
                ->setName($name)
                ->setOp($op);
            if ($data !== null) {
                $ctx = $ctx->setData($data);
            }
            $transaction = \Sentry\startTransaction($ctx);
            SentrySdk::getCurrentHub()->setSpan($transaction);
            self::$spans[(string)$transaction->getSpanId()] = $transaction;
            return $transaction;
        }
        return null;
    }
    /**
     * Marks a transaction as ended
     * @param ?Transaction $transaction the transaction to end
     */
    public static function transaction_end(?Transaction $transaction): void {
        self::span_end($transaction); // Transactions are just special spans.
    }
    /**
     * Does a bunch of setup for measuring span duration.
     * @param string $op the operation this span is for
     * @param ?array $data an assocarr of data to record for this span, or null
     * @return ?Span the span that got started, or null if disabled
     */
    public static function span_start(string $op, ?array $data = null): ?Span {
        if (self::enabled()) {
            $ctx = SpanContext::make()
                ->setOp($op);
            if ($data !== null) {
                $ctx = $ctx->setData($data);
            }
            $parent = SentrySdk::getCurrentHub()->getSpan();
            $span = $parent->startChild($ctx);
            self::$spans[(string)$span->getSpanId()] = $span;
            return $span;
        }
        return null;
    }
    /**
     * Marks a span as ended
     * @param ?Span $span the span to end
     */
    public static function span_end(?Span $span): void {
        if ($span !== null) {
            $span->finish();
            $parentid = $span->getParentSpanId();
            if ($parentid === null) {
                // Probably a transaction. No parent, thus no new active span.
                $parent = null;
            } else {
                // Set currently active span to parent span (may be the transaction).
                $parent = self::$spans[(string)$parentid];
            }
            SentrySdk::getCurrentHub()->setSpan($parent);
        }
    }
    /**
     * Checks if any errors happened since init() and reports the most recent one to sentry if so.
     * @return whether any errors were found
     */
    public static function report_last_err(): bool {
        if (self::enabled()) {
            return \Sentry\captureLastError() !== null;
        }
        return false;
    }
    /**
     * Reports an error to sentry
     * @param Throwable $err the error
     */
    public static function report_err(Throwable $err): void {
        if (self::enabled()) {
            \Sentry\captureException($err);
        }
    }
}
