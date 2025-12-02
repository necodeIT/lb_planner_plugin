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
$string['lb_planner:admin'] = 'LB Planner Admin';
$string['lb_planner:manager'] = 'LB Planner ManagerIn';
$string['lb_planner:student'] = 'LB Planner SchülerIn';
$string['lb_planner:teacher'] = 'LB Planner Lehrkraft';
$string['lb_planner:slotmaster'] = 'LB Planner Slotmeister';
// Settings
$string['sett_futuresight_title'] = 'Reservierungszeitraum der Schüler';
$string['sett_futuresight_desc'] = 'Wie viele Tage im Voraus Schüler Termine/Slots buchen dürfen. (0 = nur selber Tag)';
$string['sett_outdaterange_title'] = 'Sichtbarkeitsdauer nach Kursende';
$string['sett_outdaterange_desc'] = 'Die maximale Dauer, die ein Kurs nach seinem Ende im EduPlanner sichtbar bleibt.';
$string['sett_sentrydsn_title'] = 'Sentry DSN';
$string['sett_sentrydsn_desc'] = 'Wo Fehlermeldungen hingeschickt werden. (Bitte frag das Pallasys-Team um einen Code)';
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
$string['err_doublechacheset'] = 'Versuchte {$a} doppelt im Cache zu speichern'; // $a is an object name.
$string['err_dateformat'] = 'Ungültiges Datumsformat: \'{$a}\' erhalten, erwartet YYYY-MM-DD';
$string['err_enum_casevaluetype_unimp'] = 'Nicht implementierter Case Value Typ für Enum::format()';
$string['err_enum_namemissing'] = 'Name {$a->name} existiert nicht in {$a->classname}';
$string['err_invite_alr'] = 'Einladung bereits {$a}'; // $a is a state the invite is in.
$string['err_invite_notfound'] = 'Einladung existiert nicht';
$string['err_invite_yourself'] = 'Kann dich nicht selbst einladen';
$string['err_invite_alrmember'] = 'Kann keineN NutzerIn einladen dier bereits Mitglied ist';
$string['err_invite_alrinvited'] = 'Kann keineN NutzerIn einladen dier bereits eingeladen wurde';
$string['err_mod_assnocmid'] = 'assignid angefordert, aber keine cmid gesetzt';
$string['err_mod_cmidnoass'] = 'cmid angefordert, aber keine assignid gesetzt';
$string['err_mod_nocmidnorass'] = 'Ungültiges Modulmodell: weder cmid noch assignid gesetzt';
$string['err_mod_cmidnocm'] = 'Kursmodul mit cmid {$a} konnte nicht abgerufen werden';
$string['err_mod_assnocm'] = 'Kursmodul mit assignid {$a->assignid} und courseid {$a->courseid} konnte nicht abgerufen werden';
$string['err_plan_cantremove_userfromother'] = 'Kann keineN NutzerIn von einem Plan entfernen in dem sier nicht ist';
$string['err_plan_cantremove_yourself'] = 'Kann dich nicht selbst entfernen';
$string['err_plan_cantremove_owner'] = 'Kann BesitzerIn nicht entfernen';
$string['err_plan_cantleave_empty'] = 'Kann Plan nicht austreten: Plan muss mindestens ein weiteres Mitglied haben';
$string['err_plan_changeaccess_inval'] = 'Ungültiger Zugriffstyp';
$string['err_plan_changeaccess_self'] = 'Kann eigene Berechtigungen nicht ändern';
$string['err_plan_changeaccess_ofowner'] = 'Kann Berechtigungen ders BesitzerIns nicht ändern';
$string['err_plan_changeaccess_toowner'] = 'Kann Berechtigungen nicht auf BesitzerIn ändern';
$string['err_cf_nocatid'] = 'Kategorie-ID für Custom Fields nicht gefunden';
$string['err_cf_nodata'] = 'Keine Instanzdaten für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid}';
$string['err_cf_multidata'] = 'Mehrere Einträge für Modul-ID {$a->cmid} in Kategorie-ID {$a->catid}';
$string['err_sentry_transactcoll'] = 'Versuchte neue Sentry-Transaktion zu starten obwohl ein Span schon existiert';
$string['err_sentry_webservfalse'] = 'Webservice-Override: call_user_func_array gab bei {$a} false zurück'; // $a is a function
$string['err_slot_reservnoexist'] = 'Reservierung {$a} existiert nicht';
$string['err_slot_calcdatetime'] = 'Slot-Zeitpunkt konnte nicht berechnet werden';
$string['err_slot_urnotsupervisor'] = 'Du bist kein Betreuer dieses Slots';
$string['err_slot_startunittoosmall'] = 'Slot-Starteinheit muss >=1 sein';
$string['err_slot_startunittoolarge'] = 'Slot-Starteinheit muss <={$a} sein';
$string['err_slot_durationtoosmall'] = 'Slot-Dauer muss >=1 sein';
$string['err_slot_durationtoolarge'] = 'Slot-Starteinheit plus Dauer muss <={$a} sein';
$string['err_slot_roomnametooshort'] = 'Raumname muss mindestens 2 Zeichen haben';
$string['err_slot_roomnametoolong'] = 'Raumname darf maximal {$a} Zeichen haben';
$string['err_slot_roomsizetoosmall'] = 'Raumgröße muss >=0 sein';
$string['err_slot_overfull'] = 'Slot ist jetzt überfüllt';
$string['err_slotfilter_bothnull'] = 'courseid und vintage können nicht beide null sein';
$string['err_reserv_past'] = 'Vergangene Termine können nicht reserviert werden';
$string['err_reserv_toofuture'] = 'Datum ist nach erlaubten Datum ({$a} Tage in der Zukunft)';
$string['err_reserv_studentnoaccess'] = 'SchülerIn hat keinen Zugriff auf diesen Slot';
$string['err_reserv_studentalrin'] = 'SchülerIn hat bereits eine Reservierung für diesen Slot';
$string['err_reserv_slotfull'] = 'Slot ist schon voll';
$string['err_reserv_unreserv_alrstarted'] = 'Kann bereits begonnene Reservierung nicht stornieren';
$string['err_reserv_unreserv_alrended'] = 'Kann bereits vergangene Reservierung nicht stornieren';
$string['err_reserv_unreserv_alrstartedorforce'] =
	'Schüler können bereits begonnene Reservierungen nicht stornieren. Falls du trotzdem stornieren willst, bitte erzwingen.';
$string['err_color_wrongformat'] = 'Ungültiges Farbformat - erwartet #RGB oder #RRGGBB, nicht "{$a}"';
$string['err_color_wronglength'] = 'Ungültiges Farbformat - falsche Länge von {$a}';
$string['err_color_nonhexadecimal'] = 'Ungültiges Farbformat - nicht-hexadezimalziffer in "{$a}"';
$string['err_course_shortnamelength'] = 'Länge des Kurznamens muss <=5 und >0 sein (aktuell: {$a})';
$string['err_notif_notfound'] = 'Benachrichtigung nicht gefunden';
$string['err_user_notfound'] = 'NutzerIn nicht in EduPlanner registriert';
