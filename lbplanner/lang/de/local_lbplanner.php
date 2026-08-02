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

/**
 * German strings for local_lbplanner.
 *
 * @package local_lbplanner
 * @copyright 2026 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

$string['cf_description'] = 'Klassifiziert die Aktivität als GK, EK, TEST oder M für die EduPlanner-Synchronisierung';
$string['cf_name'] = 'LB Planner Aktivitätstyp';
$string['err_sentry_internal'] = 'LB Planner konnte keine Beobachtungsdaten an Sentry senden';
$string['err_sentry_missingvendor'] = 'Sentry ist konfiguriert, aber die gebündelten Composer-Abhängigkeiten fehlen';
$string['err_sync_classificationconfig'] = 'Das LB Planner Aktivitätsfeld ist nicht korrekt konfiguriert';
$string['err_sync_duplicateidentity'] = 'Der Moodle-Snapshot enthält eine doppelte externe ID';
$string['err_sync_invalidreference'] = 'Der Moodle-Snapshot enthält eine ungültige Referenz';
$string['err_sync_invalidsiteurl'] = 'Die kanonische Moodle-Adresse muss eine gültige HTTPS-Adresse sein';
$string['err_sync_unauthorized'] = 'Für die Moodle-Synchronisierung ist ein angemeldeter Nicht-Gast erforderlich';
$string['err_sync_unsupportedclassification'] = 'Eine Aktivität hat eine nicht unterstützte LB Planner Klassifizierung';
$string['lb_planner:sync'] = 'Meine Moodle-Daten mit EduPlanner synchronisieren';
$string['pluginname'] = 'LB Planner';
$string['settings_sentry_dsn'] = 'Sentry-DSN';
$string['settings_sentry_dsn_desc'] = 'Leer lassen, um Sentry-Fehler- und Leistungsberichte zu deaktivieren.';
$string['settings_sentry_environment'] = 'Sentry-Umgebung';
$string['settings_sentry_environment_desc'] = 'Umgebung für Sentry-Ereignisse und Transaktionen von LB Planner.';
$string['settings_sentry_environment_development'] = 'Entwicklung';
$string['settings_sentry_environment_production'] = 'Produktion';
$string['settings_sentry_traces_sample_rate'] = 'Sentry-Stichprobenrate';
$string['settings_sentry_traces_sample_rate_desc'] = 'Anteil der LB Planner Synchronisierungsanfragen als Leistungstraces.';
$string['settings_title'] = 'LB Planner Sync API';
