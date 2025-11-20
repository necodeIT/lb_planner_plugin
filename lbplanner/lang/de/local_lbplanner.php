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
$string['sett_futuresight_title'] = 'Reservierungsbereich der Studenten';
$string['sett_futuresight_desc'] = 'Maximale Anzahl der Tage im Voraus, an denen Studenten Slots reservieren können. (0 = nur am selben Tag)';
$string['sett_outdaterange_title'] = 'Veralteter Bereich der Kurse';
$string['sett_outdaterange_desc'] = 'Die maximale Dauer, die ein Kurs nach seinem Ende in EduPlanner sichtbar bleibt.';
$string['sett_sentrydsn_title'] = 'Sentry DSN';
$string['sett_sentrydsn_desc'] = 'Wohin Fehler-Debugging-Informationen gesendet werden sollen. (Bitte fragen Sie das Pallasys-Team nach einem Schlüssel)';
// Custom Fields.
$string['cf_name'] = 'LB Planer Aufgabentyp';
$string['cf_description'] = 'Verfolgt, ob die Aufgabe GK/EK/TEST/M ist';
// Invite States.
$string['invite_state_pending'] = 'ausstehend';
$string['invite_state_accepted'] = 'akzeptiert';
$string['invite_state_declined'] = 'abgelehnt';
$string['invite_state_expired'] = 'abgelaufen';
// Misc.
$string['plan_defaultname'] = 'Plan für {$a}'; // $a is the user's name.
$string['capability_deprecated_unnecessary'] = 'Diese Berechtigung wurde aufgrund interner Änderungen entfernt, die sie überflüssig machen';
// Error messages.
$string['err_accessdenied'] = 'Zugriff verweigert';
$string['err_doublechacheset'] = 'Versuch, zwischengespeicherte {$a} zweimal zu setzen'; // $a is an object name.
$string['err_dateformat'] = 'Ungültige Datumsformatierung: erhalten \'{$a}\', muss YYYY-MM-DD sein';
$string['err_enum_casevaluetype_unimp'] = 'Nicht implementierter Fall-Werttyp für Enum::format()';
$string['err_enum_namemissing'] = 'Name {$a->name} existiert nicht in {$a->classname}';
$string['err_invite_alr'] = 'Einladung bereits {$a}'; // $a is a state the invite is in.
$string['err_invite_notfound'] = 'Einladung existiert nicht';
$string['err_invite_yourself'] = 'Sie können sich nicht selbst einladen';
$string['err_invite_alrmember'] = 'Benutzer, der bereits Mitglied ist, kann nicht eingeladen werden';
$string['err_invite_alrinvited'] = 'Benutzer, der bereits eingeladen wurde, kann nicht eingeladen werden';
$string['err_mod_assnocmid'] = 'Assignid angefordert, aber keine cmid gesetzt';
$string['err_mod_cmidnoass'] = 'Cmid angefordert, aber keine assignid gesetzt';
$string['err_mod_nocmidnorass'] = 'Ungültiges Modulmodell: weder cmid noch assignid definiert';
$string['err_mod_cmidnocm'] = 'Kursmodul mit cmid {$a} konnte nicht abgerufen werden';
$string['err_mod_assnocm'] = 'Kursmodul mit assignid {$a->assignid} und courseid {$a->courseid} konnte nicht abgerufen werden';
$string['err_plan_cantremove_userfromother'] = 'Benutzer kann nicht aus einem Plan entfernt werden, in dem er sich nicht befindet';
$string['err_plan_cantremove_yourself'] = 'Sie können sich nicht selbst entfernen';
$string['err_plan_cantremove_owner'] = 'Eigentümer kann nicht entfernt werden';
$string['err_plan_cantleave_empty'] = 'Plan kann nicht verlassen werden: Plan muss mindestens ein weiteres Mitglied haben';
$string['err_plan_changeaccess_inval'] = 'Zugriffstyp nicht gültig';
$string['err_plan_changeaccess_self'] = 'Eigene Berechtigungen können nicht geändert werden';
$string['err_plan_changeaccess_ofowner'] = 'Berechtigungen für den Planbesitzer können nicht geändert werden';
$string['err_plan_changeaccess_toowner'] = 'Berechtigung kann nicht auf Eigentümer geändert werden';
$string['err_cf_nocatid'] = 'Kategorie-ID für benutzerdefinierte Felder konnte nicht gefunden werden';
$string['err_cf_nodata'] = 'Keine Instanzdaten für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid} gefunden';
$string['err_cf_multidata'] = 'Mehrere Daten für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid} gefunden';
$string['err_sentry_transactcoll'] = 'Versuch, eine neue Sentry-Transaktion zu starten, wenn bereits eine Spanne festgelegt ist';
$string['err_sentry_webservfalse'] = 'Webservice-Überschreibung: call_user_func_array hat false bei {$a} zurückgegeben'; // $a is a function
$string['err_slot_reservnoexist'] = 'Reservierung {$a} existiert nicht';
$string['err_slot_calcdatetime'] = 'Slot-Datum/Uhrzeit konnte nicht berechnet werden';
$string['err_slot_urnotsupervisor'] = 'Unzureichende Berechtigung: Sie sind kein Supervisor dieses Slots';
$string['err_slot_startunittoosmall'] = 'Slot-Starteinheit muss >=1 sein';
$string['err_slot_startunittoolarge'] = 'Slot-Starteinheit muss <={$a} sein';
$string['err_slot_durationtoosmall'] = 'Slot-Dauer muss >=1 sein';
$string['err_slot_durationtoolarge'] = 'Slot-Starteinheit plus Dauer muss <={$a} sein';
$string['err_slot_roomnametooshort'] = 'Raumname muss mindestens 2 Zeichen lang sein';
$string['err_slot_roomnametoolong'] = 'Raumname muss {$a} Zeichen lang oder kürzer sein';
$string['err_slot_roomsizetoosmall'] = 'Raumgröße muss >=0 sein';
$string['err_slot_overfull'] = 'Slot ist jetzt überfüllt';
$string['err_slotfilter_bothnull'] = 'Courseid und Jahrgang können nicht beide null sein';
$string['err_reserv_past'] = 'Datum in der Vergangenheit kann nicht reserviert werden';
$string['err_reserv_toofuture'] = 'Datum liegt nach dem erlaubten Datum ({$a} Tage in der Zukunft)';
$string['err_reserv_studentnoaccess'] = 'Student hat keinen Zugriff auf diesen Slot';
$string['err_reserv_studentalrin'] = 'Student hat bereits eine Reservierung für diesen Slot';
$string['err_reserv_slotfull'] = 'Slot ist bereits voll';
$string['err_reserv_unreserv_alrstarted'] = 'Sie können diese Reservierung nicht stornieren, da sie bereits begonnen hat';
$string['err_reserv_unreserv_alrended'] = 'Sie können diese Reservierung nicht stornieren, da sie bereits beendet ist';
$string['err_reserv_unreserv_alrstartedorforce'] =
	'Studenten können Reservierungen, die bereits begonnen haben, nicht stornieren. Wenn Sie diese Reservierung trotzdem stornieren möchten, erzwingen Sie es.';
$string['err_color_wrongformat'] = 'Falsches Farbformat - muss entweder #RGB oder #RRGGBB sein, erhalten "{$a}"';
$string['err_color_wronglength'] = 'Falsches Farbformat - falsche Länge von {$a}';
$string['err_color_nonhexadecimal'] = 'Falsches Farbformat - nicht-hexadezimales Zeichen in Farbe "{$a}" gefunden';
$string['err_course_shortnamelength'] = 'Kurznamenlänge muss <=5 und >0 sein, ist aber {$a}';
$string['err_notif_notfound'] = 'Benachrichtigung existiert nicht';
$string['err_user_notfound'] = 'Benutzer ist nicht in Eduplanner registriert';
