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
include_once "calendar.class.php";
require_once "event.class.php";

class CalendarDispatcher {

    public static function calendar_event_hook($action, $args) {
        CalendarDispatcher::$action($args);
    }

    public static function add_event($moodle_event = array()) {
        global $DB;

        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event->eventid);
        $event->load_data();
        $event->load_readers();
        if ($event->dispatch_create()) {
            $DB->execute("DELETE FROM {local_googlecalendar_cron} WHERE id = ?", array($moodle_event->id));
        }
    }

    public static function update_event($moodle_event = array()) {
        global $DB;

        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event->eventid);
        $event->load_data();
        $event->load_readers();
        if ($event->dispatch_update()) {
            $DB->execute("DELETE FROM {local_googlecalendar_cron} WHERE id = ?", array($moodle_event->id));
        }
    }

    public static function delete_event($moodle_event = array()) {
        global $DB;

        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event->eventid);
        if ($event->dispatch_delete()) {
            $DB->execute("DELETE FROM {local_googlecalendar_cron} WHERE id = ?", array($moodle_event->id));
        }
    }

    public static function show_event($moodle_event = array()) {
        
    }

    public static function hide_event($moodle_event = array()) {
        
    }

    public static function student_calendar_create($user = null) {
        $calendar = new Calendar();
        $event = new CalendarEvent();

        if (!$calendar->create_user_calendar($user)) {
            return false;
        }
        
        //$event->create_late_events($moodle_event->userid);
        
    }

    public static function student_acl_create($moodle_event = array()) {
        global $DB;
    }

    public static function student_acl_activate($userid) {
        global $DB;
        $calendar = new Calendar();
        $calendar->user_calendar_acl_create($userid);
    }

    public static function student_acl_delete($userid) {
        $calendar = new Calendar();
        $calendar->user_calendar_acl_remove($userid);
    }

    public static function course_calendar_create($moodle_event = array()) {
        global $DB;

        $calendar = new Calendar();
        if ($calendar->create_course_calendar($moodle_event->courseid)) {
            $DB->execute("DELETE FROM {local_googlecalendar_cron} WHERE id = ?", array($moodle_event->id));
        }
    }

    public static function course_calendar_delete($moodle_event = array()) {
        echo "course_calendar_delete\n";
    }

    public static function teacher_acl_create($moodle_event = array()) {
        echo "teacher_acl_create\n";
    }

    public static function teacher_acl_delete($moodle_event = array()) {
        
    }

    public static function google_hook($resourceid = null) {
        $event = new CalendarEvent();
        $event->manage_google_request($resourceid);
    }

}
