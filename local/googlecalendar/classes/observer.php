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

class google_calendar_course_observer {

    public static function google_calendar_course_created(core\event\course_created $course_event_data) {
        global $DB;

        $record = new stdClass();
        $record->action = 'course_calendar_create';
        $record->courseid = $course_event_data->get_data()['objectid'];
        $DB->insert_record("local_googlecalendar_cron", $record);
    }

    public static function google_calendar_course_deleted(core\event\course_deleted $course_event_data) {
        global $DB;

        $record = new stdClass();
        $record->action = 'course_calendar_delete';
        $record->courseid = $course_event_data->get_data()['objectid'];
        $DB->insert_record("local_googlecalendar_cron", $record);
    }

    public static function google_calendar_user_created(core\event\user_created $user_data) {
        global $DB;

        $record = new stdClass();
        $record->action = 'user_calendar_create';
        $record->userid = $user_data->get_data()['relateduserid'];
        $DB->insert_record("local_googlecalendar_cron", $record);
    }

    public static function google_calendar_user_deleted(core\event\user_deleted $user_data) {
        global $DB;

        $record = new stdClass();
        $record->action = 'user_calendar_delete';
        $record->userid = $user_data->get_data()['relateduserid'];
        $DB->insert_record("local_googlecalendar_cron", $record);
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
            $record = new stdClass();
            $record->action = 'teacher_acl_create';
            $record->userid = $enrolment_data_data['relateduserid'];
            $record->courseid = $enrolment_data_data['courseid'];
            $DB->insert_record("local_googlecalendar_cron", $record);
        }
        if ($rolename == 'student') {
            $record = new stdClass();
            $record->action = 'student_acl_create';
            $record->userid = $enrolment_data_data['relateduserid'];
            $record->courseid = $enrolment_data_data['courseid'];
            $DB->insert_record("local_googlecalendar_cron", $record);
        }
    }

    public static function google_calendar_role_unassigned(core\event\role_unassigned $enrolment_data) {
        global $DB;

        $enrolment_data_data = $enrolment_data->get_data();
        $snapshotid = $enrolment_data->get_data()['other']['id'];
        $snapshot = $enrolment_data->get_record_snapshot('role_assignments', $snapshotid);

        $roleid = $snapshot->roleid;
        $rolename = $DB->get_records_sql("SELECT shortname from {role} WHERE id = ?", array($roleid));
        $rolename = array_pop($rolename);
        $rolename = $rolename->shortname;

        if ($rolename == 'editingteacher') {
            $record = new stdClass();
            $record->action = 'teacher_acl_delete';
            $record->userid = $enrolment_data_data['relateduserid'];
            $record->courseid = $enrolment_data_data['courseid'];
            $DB->insert_record("local_googlecalendar_cron", $record);
        }
        if ($rolename == 'student') {
            $record = new stdClass();
            $record->action = 'student_acl_delete';
            $record->userid = $enrolment_data_data['relateduserid'];
            $record->courseid = $enrolment_data_data['courseid'];
            $DB->insert_record("local_googlecalendar_cron", $record);
        }
    }

}
