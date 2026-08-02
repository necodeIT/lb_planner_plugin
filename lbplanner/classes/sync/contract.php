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

/**
 * Shared constants and validation for the EduPlanner sync contract.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */
final class contract {
    /** Current wire-contract version. */
    public const VERSION = 1;

    /** Capability required by all sync functions. */
    public const CAPABILITY = 'local/lb_planner:sync';

    /** Supported resource names, kept in wire-order. */
    public const SUPPORTED_RESOURCES = [
        'assignments',
        'courses',
        'groups',
        'quizzes',
    ];

    /** Supported activity classification keys. */
    public const CLASSIFICATION_KEYS = [
        'GK',
        'EK',
        'TEST',
        'M',
    ];

    /**
     * Return Moodle's canonical HTTPS site URL without a trailing slash.
     *
     * @return string Canonical site URL.
     */
    public static function site_url(): string {
        global $CFG;

        $url = rtrim((string) $CFG->wwwroot, '/');
        $parts = parse_url($url);
        if (
            filter_var($url, FILTER_VALIDATE_URL) === false || $parts === false ||
            strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host']) ||
            isset($parts['user']) || isset($parts['pass']) || isset($parts['query']) || isset($parts['fragment'])
        ) {
            throw new \moodle_exception('err_sync_invalidsiteurl', 'local_lbplanner');
        }
        return $url;
    }

    /**
     * Generate an absolute URL under Moodle's canonical origin and path.
     *
     * @param string $path Moodle-relative path.
     * @param array $parameters URL parameters.
     * @return string Absolute same-origin URL.
     */
    public static function moodle_url(string $path, array $parameters): string {
        $siteurl = self::site_url();
        $url = (new \moodle_url($path, $parameters))->out(false);
        if (!str_starts_with($url, $siteurl . '/')) {
            throw new \moodle_exception('err_sync_invalidsiteurl', 'local_lbplanner');
        }
        return $url;
    }

    /**
     * Encode a positive Moodle identifier as a decimal string.
     *
     * @param int|string $id Identifier.
     * @return string Decimal identifier.
     */
    public static function decimal_id(int|string $id): string {
        $value = (string) $id;
        if (!preg_match('/^[1-9][0-9]*$/', $value)) {
            throw new \moodle_exception('err_sync_invalidreference', 'local_lbplanner');
        }
        return $value;
    }

    /**
     * Convert a zero Moodle timestamp to null.
     *
     * @param int|string|null $timestamp Timestamp.
     * @return int|null Timestamp, or null for an unset Moodle date.
     */
    public static function nullable_timestamp(int|string|null $timestamp): ?int {
        $timestamp = (int) $timestamp;
        return $timestamp === 0 ? null : $timestamp;
    }

    /**
     * Compare decimal-string identities lexicographically.
     *
     * @param string $left Left identity.
     * @param string $right Right identity.
     * @return int Comparison result.
     */
    public static function compare_ids(string $left, string $right): int {
        return strcmp($left, $right);
    }

    /**
     * Assert an identity has not already occurred in one resource response.
     *
     * @param array $seen Seen identities, passed by reference.
     * @param string $externalid New identity.
     * @return void
     */
    public static function assert_new_identity(array &$seen, string $externalid): void {
        if (isset($seen[$externalid])) {
            throw new \moodle_exception('err_sync_duplicateidentity', 'local_lbplanner');
        }
        $seen[$externalid] = true;
    }
}
