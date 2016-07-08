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

defined('MOODLE_INTERNAL') || die();

include_once 'calendar.class.php';
include_once 'event.class.php';

class google_calendar_course_update_observer {

    //Create a calendar for the course
    public static function google_calendar_course_created(core\event\course_created $course_event_data) {
        $course_calendar = new Calendar();
        $course_calendar->create_course_calendar($course_event_data->get_data()['objectid']);
    }

    public static function google_calendar_course_deleted(core\event\course_deleted $course_event_data) {
        
        if(!Calendar::course_calendar_exist($course_event_data->get_data()['objectid'])){
            return;
        }
        
        $calendar_events = new CalendarEvent();
        $calendar_events->remove_events_from_course($course_event_data->get_data()['objectid']);
        
        $course_calendar = new Calendar();
        $course_calendar->delete_course_calendar($course_event_data->get_data()['objectid']);
    }

    public static function google_calendar_course_updated(core\event\course_updated $course_event_data) {
        
    }

    public static function google_calendar_role_assigned(core\event\role_assigned $enrolment_data) {
        global $DB;

        $enrolment_data_data = $enrolment_data->get_data();
        $snapshotid = $enrolment_data->get_data()['other']['id'];
        $snapshot = $enrolment_data->get_record_snapshot('role_assignments', $snapshotid);

        $roleid = $snapshot->roleid;
        $rolename = $DB->get_records_sql("SELECT shortname from {role} WHERE id = ?", array($roleid));
        $rolename = array_pop($rolename);
        $rolename = $rolename->shortname;

        if ($rolename == 'editingteacher') {
            $calendar = new Calendar();
            $calendar->enrol_teacher($enrolment_data_data['relateduserid'], $enrolment_data_data['courseid']);
        }
        if ($rolename == 'student') {
            $calendar = new Calendar();
            $calendar->create_user_calendar($enrolment_data_data['relateduserid']);
        }
    }

    public static function google_calendar_role_unassigned(core\event\role_unassigned $enrolment_data) {
        global $DB;

        if(!Calendar::course_calendar_exist($enrolment_data->get_data()['courseid'])){
            return;
        }
        
        $enrolment_data_data = $enrolment_data->get_data();
        $snapshotid = $enrolment_data->get_data()['other']['id'];
        $snapshot = $enrolment_data->get_record_snapshot('role_assignments', $snapshotid);

        $roleid = $snapshot->roleid;
        $rolename = $DB->get_records_sql("SELECT shortname from {role} WHERE id = ?", array($roleid));
        $rolename = array_pop($rolename);
        $rolename = $rolename->shortname;

        if ($rolename == 'editingteacher') {
            $calendar = new Calendar();
            $calendar->unenrol_teacher($enrolment_data_data['relateduserid'], $enrolment_data_data['courseid']);
        }
        
        if($rolename == 'student'){
            $event = new CalendarEvent();
            $event->remove_events_from_user($enrolment_data_data['relateduserid'], $enrolment_data_data['courseid']);                    
        }
    }
}
