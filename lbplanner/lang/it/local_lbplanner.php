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
 * Defines some translation strings in italian.
 *
 * @package local_lbplanner
 * @copyright 2025 Pallasys
 * @license https://creativecommons.org/licenses/by-nc-sa/4.0/ CC-BY-NC-SA 4.0 International or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'LB Planner';
$string['unit_day'] = 'Giorno';
$string['unit_day_pl'] = 'Giorni';
// Capabilities.
$string['lb_planner:admin'] = 'Amministratore LB Planner';
$string['lb_planner:manager'] = 'Manager LB Planner';
$string['lb_planner:student'] = 'Studente LB Planner';
$string['lb_planner:teacher'] = 'Insegnante LB Planner';
$string['lb_planner:slotmaster'] = 'Slotmaster LB Planner';
// Settings
$string['sett_futuresight_title'] = 'Intervallo di prenotazione degli studenti';
$string['sett_futuresight_desc'] = 'Numero massimo di giorni in anticipo in cui gli studenti possono prenotare slot. (0 = solo lo stesso giorno)';
$string['sett_outdaterange_title'] = 'Intervallo di obsolescenza dei corsi';
$string['sett_outdaterange_desc'] = 'La durata massima in cui un corso rimane visibile in EduPlanner dopo la sua conclusione.';
$string['sett_sentrydsn_title'] = 'Sentry DSN';
$string['sett_sentrydsn_desc'] = 'Dove inviare le informazioni di debug degli errori. (Si prega di chiedere una chiave al team Pallasys)';
// Custom Fields.
$string['cf_name'] = 'Tipo di attività LB Planer';
$string['cf_description'] = 'Traccia se l\'attività è GK/EK/TEST/M';
// Invite States.
$string['invite_state_pending'] = 'in attesa';
$string['invite_state_accepted'] = 'accettato';
$string['invite_state_declined'] = 'rifiutato';
$string['invite_state_expired'] = 'scaduto';
// Misc.
$string['plan_defaultname'] = 'Piano per {$a}'; // $a is the user's name.
$string['capability_deprecated_unnecessary'] = 'Questa capacità è stata rimossa a causa di modifiche interne che la rendono non necessaria';
// Error messages.
$string['err_accessdenied'] = 'Accesso negato';
$string['err_doublechacheset'] = 'Tentativo di impostare {$a} nella cache due volte'; // $a is an object name.
$string['err_dateformat'] = 'Formato data non valido: ricevuto \'{$a}\', deve essere YYYY-MM-DD';
$string['err_enum_casevaluetype_unimp'] = 'Tipo di valore del caso non implementato per Enum::format()';
$string['err_enum_namemissing'] = 'Il nome {$a->name} non esiste in {$a->classname}';
$string['err_invite_alr'] = 'Invito già {$a}'; // $a is a state the invite is in.
$string['err_invite_notfound'] = 'L\'invito non esiste';
$string['err_invite_yourself'] = 'Non puoi invitare te stesso';
$string['err_invite_alrmember'] = 'Non è possibile invitare un utente che è già membro';
$string['err_invite_alrinvited'] = 'Non è possibile invitare un utente che è già stato invitato';
$string['err_mod_assnocmid'] = 'Richiesto assignid, ma nessun cmid è impostato';
$string['err_mod_cmidnoass'] = 'Richiesto cmid, ma nessun assignid è impostato';
$string['err_mod_nocmidnorass'] = 'Modello di modulo non valido: né cmid né assignid definiti';
$string['err_mod_cmidnocm'] = 'Impossibile ottenere il modulo del corso con cmid {$a}';
$string['err_mod_assnocm'] = 'Impossibile ottenere il modulo del corso con assignid {$a->assignid} e courseid {$a->courseid}';
$string['err_plan_cantremove_userfromother'] = 'Non è possibile rimuovere un utente da un piano in cui non si trova';
$string['err_plan_cantremove_yourself'] = 'Non puoi rimuovere te stesso';
$string['err_plan_cantremove_owner'] = 'Non è possibile rimuovere il proprietario';
$string['err_plan_cantleave_empty'] = 'Impossibile lasciare il piano: il piano deve avere almeno un altro membro';
$string['err_plan_changeaccess_inval'] = 'Tipo di accesso non valido';
$string['err_plan_changeaccess_self'] = 'Non è possibile modificare le proprie autorizzazioni';
$string['err_plan_changeaccess_ofowner'] = 'Non è possibile modificare le autorizzazioni per il proprietario del piano';
$string['err_plan_changeaccess_toowner'] = 'Non è possibile modificare l\'autorizzazione a proprietario';
$string['err_cf_nocatid'] = 'Impossibile trovare l\'ID della categoria dei campi personalizzati';
$string['err_cf_nodata'] = 'Impossibile trovare dati di istanza per l\'ID modulo {$a->cmid} nell\'ID categoria {$a->catid}';
$string['err_cf_multidata'] = 'Trovati più dati per l\'ID modulo {$a->cmid} nell\'ID categoria {$a->catid}';
$string['err_sentry_transactcoll'] = 'Tentativo di avviare una nuova transazione sentry quando è già impostato uno span';
$string['err_sentry_webservfalse'] = 'Override del servizio web: call_user_func_array ha restituito false in {$a}'; // $a is a function
$string['err_slot_reservnoexist'] = 'La prenotazione {$a} non esiste';
$string['err_slot_calcdatetime'] = 'Impossibile calcolare la data/ora dello slot';
$string['err_slot_urnotsupervisor'] = 'Autorizzazione insufficiente: non sei supervisore di questo slot';
$string['err_slot_startunittoosmall'] = 'L\'unità di inizio dello slot deve essere >=1';
$string['err_slot_startunittoolarge'] = 'L\'unità di inizio dello slot deve essere <={$a}';
$string['err_slot_durationtoosmall'] = 'La durata dello slot deve essere >=1';
$string['err_slot_durationtoolarge'] = 'L\'unità di inizio dello slot più la durata deve essere <={$a}';
$string['err_slot_roomnametooshort'] = 'Il nome della stanza deve essere lungo almeno 2 caratteri';
$string['err_slot_roomnametoolong'] = 'Il nome della stanza deve essere lungo {$a} caratteri o meno';
$string['err_slot_roomsizetoosmall'] = 'La dimensione della stanza deve essere >=0';
$string['err_slot_overfull'] = 'Lo slot è ora sovraccarico';
$string['err_slotfilter_bothnull'] = 'Courseid e vintage non possono essere entrambi null';
$string['err_reserv_past'] = 'Non è possibile prenotare una data nel passato';
$string['err_reserv_toofuture'] = 'La data è oltre la data consentita ({$a} giorni nel futuro)';
$string['err_reserv_studentnoaccess'] = 'Lo studente non ha accesso a questo slot';
$string['err_reserv_studentalrin'] = 'Lo studente ha già una prenotazione per questo slot';
$string['err_reserv_slotfull'] = 'Lo slot è già pieno';
$string['err_reserv_unreserv_alrstarted'] = 'Non puoi annullare questa prenotazione perché è già iniziata';
$string['err_reserv_unreserv_alrended'] = 'Non puoi annullare questa prenotazione perché è già terminata';
$string['err_reserv_unreserv_alrstartedorforce'] =
	'Gli studenti non possono annullare prenotazioni già iniziate. Se vuoi annullare questa prenotazione comunque, forzala.';
$string['err_color_wrongformat'] = 'Formato colore errato - deve essere #RGB o #RRGGBB, ricevuto "{$a}"';
$string['err_color_wronglength'] = 'Formato colore errato - lunghezza errata di {$a}';
$string['err_color_nonhexadecimal'] = 'Formato colore errato - trovato carattere non esadecimale nel colore "{$a}"';
$string['err_course_shortnamelength'] = 'La lunghezza del nome breve deve essere <=5 e >0, ma è {$a}';
$string['err_notif_notfound'] = 'La notifica non esiste';
$string['err_user_notfound'] = 'L\'utente non è registrato in Eduplanner';
