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

include_once 'google_service.class.php';

class Calendar extends GoogleService {
    
    /*
     * create_user_calendar
     * 
     * This function will create a calendar for a specific user.
     * 
     * @param (int) $userid - The target user
     * 
     */
    function create_user_calendar($userid = null) {
        global $DB, $CFG;

        function __construct() {
            parent::__construct();
        }

        if ($this->user_calendar_exist($userid)) {
            return;
        }

        $sql = "SELECT * 
                  FROM {user} 
                 WHERE id = ?";
        $result = $DB->get_record_sql($sql, array($userid));

        $calendar = new Google_Service_Calendar_Calendar();

        $calendar->setSummary( get_string('user', 'local_googlecalendar') . $result->username);
        $calendar->setTimeZone($CFG->timezone);
        $createdCalendar = $this->get_service()->calendars->insert($calendar);
        $calendarId = $createdCalendar->getId();

        /* Share the calendar */
        $rule = new Google_Service_Calendar_AclRule();
        $scope = new Google_Service_Calendar_AclRuleScope();

        $scope->setType("user");
        $scope->setValue($result->email);
        $rule->setScope($scope);
        $rule->setRole("reader");
        $createdRule = $this->get_service()->acl->insert($calendarId, $rule);

        $register = new stdClass();
        $register->calendarid = $calendarId;
        $register->userid = $userid;
        $DB->insert_record("local_googlecalendar", $register);
    }

    /*
     * user_calendar_exist
     * 
     * This function will search if the user already has a calendar
     * 
     * @param (int) $userid - Target user
     * @return (bollean) 
     */
    public function user_calendar_exist($userid = null) {
        global $DB;

        $sql = "SELECT * 
                  FROM {local_googlecalendar} 
                 WHERE userid = ?";

        $result = $DB->get_records_sql($sql, array($userid));

        if (!empty($result)) {
            return true;
        }
        return false;
    }

    /*
     * user_calendar_exist
     * 
     * This function will create a calendar for a specific course
     * 
     * @param (int) $courseid - Target course
     * 
     */
    function create_course_calendar($courseid = null) {
        global $DB, $CFG;

        $sql = "SELECT * FROM {course} 
                 WHERE id = ?";
        $result = $DB->get_record_sql($sql, array($courseid));

        $calendar = new Google_Service_Calendar_Calendar();
        $calendar->setSummary($result->fullname);
        $calendar->setTimeZone($CFG->timezone);
        $createdcalendar = $this->get_service()->calendars->insert($calendar);
        $calendarid = $createdcalendar->getId();
        
        $register = new stdClass();
        
        //This is the two way sync magic
        try {            
            $channel = new Google_Service_Calendar_Channel($this->get_client());
            $channel->setId($courseid);
            $channel->setType('web_hook');
            $channel->setAddress($CFG->wwwroot . "/local/googlecalendar/event_watcher.php");
            $watchevent = $this->get_service()->events->watch($calendarid, $channel);
            
            
            $register->resourceid = $watchevent->resourceId;
            $register->resourceexpiration = $watchevent->expiration;
            
        } catch (Exception $exc) {
            
        }
        
        $register->calendarid = $calendarid;
        $register->courseid = $courseid;
        $register->synctokenlastdate = time();

        $DB->insert_record("local_googlecalendar", $register);
    }
    
    /*
     * course_calendar_exist
     * 
     * This function will search if there is a calendar for the course
     * 
     * @param (int) $courseid - Target user
     * @return (bollean) 
     */
    public static function course_calendar_exist($courseid = null) {
        global $DB;
        if ($courseid == null) {
            return;
        }
        $sql = "SELECT * 
                  FROM {local_googlecalendar} 
                 WHERE courseid = ?";

        $result = $DB->get_records_sql($sql, array($courseid));

        if (!empty($result)) {
            return true;
        }
        return false;
    }

    

    /*
     * delete_course_calendar
     * 
     * This function will delete the google calendar of a specific course
     * 
     * @param (int) $courseid - Target course
     * 
     */
    public function delete_course_calendar($courseid = null) {
        global $DB;

        $sql = "SELECT id, calendarid
                  FROM {local_googlecalendar}
                 WHERE courseid = ?";
        $result = $DB->get_records_sql($sql, array($courseid));
        $result = array_pop($result);

        if (!empty($result)) {
            $sql = 'DELETE 
                      FROM {local_googlecalendar} 
                     WHERE id = ?';
            $DB->execute($sql, array($result->id));
            $this->get_service()->calendars->delete($result->calendarid);
        }
    }

    /*
     * enrol_teacher
     * 
     * This function will create a writer ACL to a specific user in a specific
     * calendar
     * 
     * @param (int) $userid - Target user
     * @param (int) $courseid - Target course
     * 
     */
    function enrol_teacher($userid, $courseid) {
        global $DB;

        $sql = "SELECT calendarid
                  FROM {local_googlecalendar}
                 WHERE courseid = ?";

        $result = $DB->get_record_sql($sql, array($courseid));
        $calendarId = $result->calendarid;

        $result = $DB->get_records("user", array('id' => $userid));
        $useremail = array_pop($result)->email;

        $rule = new Google_Service_Calendar_AclRule();
        $scope = new Google_Service_Calendar_AclRuleScope();
        $scope->setType("user");
        $scope->setValue($useremail);
        $rule->setScope($scope);
        $rule->setRole("writer");

        $createdRule = $this->get_service()->acl->insert($calendarId, $rule);
    }

    /*
     * unenrol_teacher
     * 
     * This function will remove the writer ACL to a specific user in a specific
     * calendar
     * 
     * @param (int) $userid - Target user
     * @param (int) $courseid - Target course
     * 
     */
    function unenrol_teacher($userid, $courseid) {
        global $DB;

        $sql = "SELECT calendarid
                  FROM {local_googlecalendar} 
                 WHERE courseid = ?";
        $result = $DB->get_record_sql($sql, array($courseid));

        if (!empty($result)) {
            $calendarId = $result->calendarid;

            $result = $DB->get_records("user", array('id' => $userid));
            $useremail = array_pop($result)->email;

            $acl_list = $this->get_service()->acl->listAcl($calendarId);

            $acl_id = '';
            foreach ($acl_list->getItems() as $rule) {
                if ($rule->getScope()['value'] == $useremail) {
                    $acl_id = $rule->getId();
                }
            }
            if ($acl_id != '') {
                $this->get_service()->acl->delete($calendarId, $acl_id);
            }
        }
    }

}
