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
require_once 'event.class.php';

class CalendarDispatcher {

    public static function calendar_event_hook($action, array $args) {
        CalendarDispatcher::$action($args[0]);
    }

    public static function add_event($moodle_event = array()) {
        global $CFG, $DB;
        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event);
        $event->load_readers();        
        $event->dispatch_create();
    }

    public static function update_event($moodle_event = array()) {
        global $CFG, $DB;
        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event);
        $event->load_readers();
        $event->dispatch_update();
    }

    public static function delete_event($moodle_event = array()) {
        $event = new CalendarEvent();        
        $event->set_eventid($moodle_event);
        $event->dispatch_delete();
    }
    
    public static function show_event($moodle_event = array()) {
        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event);
        $event->load_readers();
        $event->dispatch_show_visible();
    }
    
    public static function hide_event($moodle_event = array()) {
        $event = new CalendarEvent();
        $event->set_eventdata($moodle_event);
        $event->load_readers();
        $event->dispatch_hide_visible();
    }

    public static function google_hook($resourceid = null) {
        $event = new CalendarEvent();
        $event->manage_google_request($resourceid);
    }
    
}
