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
 * Defines some translation strings in german.
 *
 * @package local_lbplanner
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'LB Planner';
$string['unit_day'] = 'Tag';
$string['unit_day_pl'] = 'Tage';
// Capabilities.
$string['lb_planner:admin'] = 'LB Planner Administrator';
$string['lb_planner:manager'] = 'LB Planner Manager';
$string['lb_planner:student'] = 'LB Planner Student';
$string['lb_planner:teacher'] = 'LB Planner Lehrer';
$string['lb_planner:slotmaster'] = 'LB Planner Slotmaster';
// Settings
$string['sett_futuresight_title'] = 'Reservierungszeitraum der Studenten';
$string['sett_futuresight_desc'] = 'Wie viele Tage im Voraus Studierende Termine/Slots buchen dürfen. (0 = nur am selben Tag)';
$string['sett_outdaterange_title'] = 'Sichtbarkeitsdauer nach Kursende';
$string['sett_outdaterange_desc'] = 'Die maximale Dauer, die ein Kurs nach seinem Ende im EduPlanner sichtbar bleibt.';
$string['sett_sentrydsn_title'] = 'Sentry DSN';
$string['sett_sentrydsn_desc'] = 'Zielort, an den Debug- und Fehlermeldungen übermittelt werden. (Bitte fragen Sie das Pallasys-Team nach einem Schlüssel)';
// Custom Fields.
$string['cf_name'] = 'LB Planer Aufgabentyp';
$string['cf_description'] = 'Gibt an, ob die Aufgabe GK/EK/TEST/M ist';
// Invite States.
$string['invite_state_pending'] = 'ausstehend';
$string['invite_state_accepted'] = 'akzeptiert';
$string['invite_state_declined'] = 'abgelehnt';
$string['invite_state_expired'] = 'abgelaufen';
// Misc.
$string['plan_defaultname'] = 'Plan für {$a}'; // $a is the user's name.
$string['capability_deprecated_unnecessary'] = 'Diese Berechtigung wurde entfernt, da sie nicht mehr benötigt wird';
// Error messages.
$string['err_accessdenied'] = 'Zugriff verweigert';
$string['err_doublechacheset'] = '{$a} wurde bereits im Cache gespeichert'; // $a is an object name.
$string['err_dateformat'] = 'Ungültiges Datumsformat: \'{$a}\' erhalten, erwartet YYYY-MM-DD';
$string['err_enum_casevaluetype_unimp'] = 'Nicht implementierter Werttyp für Enum::format()';
$string['err_enum_namemissing'] = 'Name {$a->name} existiert nicht in {$a->classname}';
$string['err_invite_alr'] = 'Einladung bereits {$a}'; // $a is a state the invite is in.
$string['err_invite_notfound'] = 'Einladung existiert nicht';
$string['err_invite_yourself'] = 'Du kannst dich nicht selbst einladen';
$string['err_invite_alrmember'] = 'Benutzer ist bereits Mitglied';
$string['err_invite_alrinvited'] = 'Benutzer wurde bereits eingeladen';
$string['err_mod_assnocmid'] = 'Assignid angefordert, aber keine cmid gesetzt';
$string['err_mod_cmidnoass'] = 'Cmid angefordert, aber keine assignid gesetzt';
$string['err_mod_nocmidnorass'] = 'Ungültiges Modulmodell: weder cmid noch assignid definiert';
$string['err_mod_cmidnocm'] = 'Kursmodul mit cmid {$a} konnte nicht abgerufen werden';
$string['err_mod_assnocm'] = 'Kursmodul mit assignid {$a->assignid} und courseid {$a->courseid} konnte nicht abgerufen werden';
$string['err_plan_cantremove_userfromother'] = 'Benutzer ist nicht in diesem Plan';
$string['err_plan_cantremove_yourself'] = 'Du kannst dich nicht selbst entfernen';
$string['err_plan_cantremove_owner'] = 'Besitzer kann nicht entfernt werden';
$string['err_plan_cantleave_empty'] = 'Plan muss mindestens ein weiteres Mitglied haben';
$string['err_plan_changeaccess_inval'] = 'Ungültiger Zugriffstyp';
$string['err_plan_changeaccess_self'] = 'Du kannst deine eigenen Berechtigungen nicht ändern';
$string['err_plan_changeaccess_ofowner'] = 'Berechtigungen des Besitzers können nicht geändert werden';
$string['err_plan_changeaccess_toowner'] = 'Berechtigung kann nicht auf Besitzer gesetzt werden';
$string['err_cf_nocatid'] = 'Kategorie-ID für benutzerdefinierte Felder nicht gefunden';
$string['err_cf_nodata'] = 'Keine Daten für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid}';
$string['err_cf_multidata'] = 'Mehrere Einträge für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid}';
$string['err_sentry_transactcoll'] = 'Sentry-Transaktion existiert bereits';
$string['err_sentry_webservfalse'] = 'Webservice: call_user_func_array gab false zurück bei {$a}'; // $a is a function
$string['err_slot_reservnoexist'] = 'Reservierung {$a} nicht gefunden';
$string['err_slot_calcdatetime'] = 'Slot-Zeitpunkt konnte nicht berechnet werden';
$string['err_slot_urnotsupervisor'] = 'Du bist kein Supervisor dieses Slots';
$string['err_slot_startunittoosmall'] = 'Slot-Starteinheit muss >=1 sein';
$string['err_slot_startunittoolarge'] = 'Slot-Starteinheit muss <={$a} sein';
$string['err_slot_durationtoosmall'] = 'Slot-Dauer muss >=1 sein';
$string['err_slot_durationtoolarge'] = 'Slot-Starteinheit plus Dauer muss <={$a} sein';
$string['err_slot_roomnametooshort'] = 'Raumname muss mindestens 2 Zeichen haben';
$string['err_slot_roomnametoolong'] = 'Raumname darf maximal {$a} Zeichen haben';
$string['err_slot_roomsizetoosmall'] = 'Raumgröße muss mindestens 0 sein';
$string['err_slot_overfull'] = 'Slot ist überfüllt';
$string['err_slotfilter_bothnull'] = 'Courseid und Jahrgang können nicht beide null sein';
$string['err_reserv_past'] = 'Vergangene Termine können nicht reserviert werden';
$string['err_reserv_toofuture'] = 'Datum liegt zu weit in der Zukunft (max. {$a} Tage)';
$string['err_reserv_studentnoaccess'] = 'Kein Zugriff auf diesen Slot';
$string['err_reserv_studentalrin'] = 'Du hast bereits eine Reservierung für diesen Slot';
$string['err_reserv_slotfull'] = 'Slot ist voll';
$string['err_reserv_unreserv_alrstarted'] = 'Reservierung hat bereits begonnen';
$string['err_reserv_unreserv_alrended'] = 'Reservierung ist bereits beendet';
$string['err_reserv_unreserv_alrstartedorforce'] =
	'Bereits begonnene Reservierungen können nicht storniert werden. Zum Erzwingen die Force-Option nutzen.';
$string['err_color_wrongformat'] = 'Ungültiges Farbformat: "{$a}" (erwartet #RGB oder #RRGGBB)';
$string['err_color_wronglength'] = 'Ungültige Farblänge: {$a}';
$string['err_color_nonhexadecimal'] = 'Ungültiges Zeichen in Farbe "{$a}"';
$string['err_course_shortnamelength'] = 'Kurzname muss 1-5 Zeichen haben (aktuell: {$a})';
$string['err_notif_notfound'] = 'Benachrichtigung nicht gefunden';
$string['err_user_notfound'] = 'Benutzer nicht in EduPlanner registriert';
