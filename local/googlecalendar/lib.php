<?php

/**
 * Google Calendar Integration - 
 *
 * @package    local_googlecalendar
 * @author     Artur Welp <ahwelp@univates.br>
 * @author     Maurício Severo da Silva <mss@univates.br>
 * @author     Alexandre Sturmer Wolf <awolf@univates.br>
 * @copyright  2016 Univates - htttp://www.univates.br/
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
/*
 * This file is loaded by the file calendar/lib.php in the function 
 * calendar_event_hook()
 */

include_once 'classes/calendar_dispatcher.php';

/*
 * This will solve the problems of the plugin location,
 * the native calenar require the extended files to be in calenadar/name/
 * so to load this file we set the $CFG->calendar as ../local/google_calendar/
 * in the istallation
 * 
 * now we set it back to what it should be, so the functions will be called
 * correctly
 */
$CFG->calendar = 'googlecalendar';

function googlecalendar_add_event($moodle_event) {
    global $DB;

    $record = new stdClass();
    $record->action = 'add_event';
    $record->eventid = $moodle_event->id;
    $DB->insert_record("local_googlecalendar_cron", $record);
}

function googlecalendar_update_event($moodle_event) {
    global $DB;

    $record = new stdClass();
    $record->action = 'add_update';
    $record->eventid = $moodle_event->id;
    $DB->insert_record("local_googlecalendar_cron", $record);
}

function googlecalendar_delete_event($moodle_event) {
    global $DB;

    $record = new stdClass();
    $record->action = 'delete_event';
    $record->eventid = $moodle_event;
    $DB->insert_record("local_googlecalendar_cron", $record);
}

function googlecalendar_show_event($moodle_event) {
    global $DB;

    $record = new stdClass();
    $record->action = 'show_event';
    $record->eventid = $moodle_event->id;
    $DB->insert_record("local_googlecalendar_cron", $record);
}

function googlecalendar_hide_event($moodle_event) {
    global $DB;

    $record = new stdClass();
    $record->action = 'hide_event';
    $record->eventid = $moodle_event->id;
    $DB->insert_record("local_googlecalendar_cron", $record);
}

function googlecalendar_create_user_bond() {
    global $USER, $DB;

    $record = new stdClass();
    $record->action = 'student_calendar_create';
    $record->userid = $USER->id;
    $DB->insert_record("local_googlecalendar_cron", $record);
    //CalendarDispatcher::student_calendar_create($USER->id);
}

function googlecalendar_activate_user_bond() {
    global $USER;
    CalendarDispatcher::student_acl_activate($USER->id);
}

function googlecalendar_deactivate_user_bond() {
    global $USER;
    CalendarDispatcher::student_acl_delete($USER->id);
}

