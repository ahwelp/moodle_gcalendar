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

//
// This class intent to manage events on the googlecalendar plugin
// 

class CalendarEvent extends GoogleService {

    private $google = false;
    private $eventdata;
    private $googleeventdata;
    private $eventid;
    private $readers;
    private $userswithaccess;
    private $userswithnoaccess;
    private $courseid;

    public function __construct() {
        parent::__construct();
    }

    function get_eventdata() {
        return $this->eventdata;
    }

    function get_readers() {
        return $this->readers;
    }

    function set_eventdata($eventdata) {
        $this->eventdata = $eventdata;
    }

    function get_eventid() {
        return $this->eventid;
    }

    function set_eventid($eventid) {
        $this->eventid = $eventid;
    }

    function set_readers($readers) {
        $this->readers = $readers;
    }

    function get_userswithaccess() {
        return $this->userswithaccess;
    }

    function get_userswithnoaccess() {
        return $this->userswithnoaccess;
    }

    function set_userswithaccess($userswithaccess) {
        $this->userswithaccess = $userswithaccess;
    }

    function set_userswithnoaccess($userswithnoaccess) {
        $this->userswithnoaccess = $userswithnoaccess;
    }

    function is_google() {
        return $this->google;
    }

    function set_google($google) {
        $this->google = $google;
    }

    /*
     * @function load_readers()
     * 
     * Must be reviewd, now it brings all the students from a course
     * and don't look for acess restrictions like chained activityes
     * and groups
     * 
     */

    function load_readers() {
        global $DB;
        $sql = " 
            SELECT u.id, u.email, gc.calendarid, gc.id as googlecalendarid
             FROM
              mdl_course c,
              mdl_context cx,
              mdl_role_assignments r,
              mdl_user u,
              mdl_role role,
              mdl_local_googlecalendar gc
            WHERE
             c.id = ? AND 
             gc.userid = u.id AND
             cx.instanceid = c.id AND
             cx.id = r.contextid AND
             u.id=r.userid AND
             r.roleid=role.id AND 
             role.shortname = 'student'
            ORDER BY 
             u.id;";

        //Load all users with student role
        $this->readers = $DB->get_records_sql($sql, array($this->eventdata->courseid));

        //When it is an course event, everybody will be affected
        if ($this->eventdata->modulename === 0) {
            $this->userswithaccess = $this->readers;
            return;
        }

        $this->userswithaccess = $this->readers;
        $this->userswithnoaccess = array();

        // Trying With Context. Context don't filter restriction like visible and groups        
        /*
          $capability = 'mod/' . $this->eventdata->modulename . ':view';
          foreach ($this->readers as $reader) {
          if(has_capability($capability, $this->eventdata->context, $reader->id)){
          $this->userswithaccess[] = $reader;
          }else{
          $this->userswithnoaccess[] = $reader;
          }
          }
         */

        // Trying with is_user_visible. Restriction is created after the calendar be adviced
        // maybe update the other places in moodle, or catch a further event and cache the info
        // of the event
        /*
          $cm = get_coursemodule_from_instance($this->eventdata->modulename, $this->eventdata->instance);
          foreach ($this->readers as $reader) {
          if( \core_availability\info_module::is_user_visible($cm, $reader->id, false) ){
          $this->userswithaccess[] = $reader;
          }else{
          $this->userswithnoaccess[] = $reader;
          }
          }
         */
    }

    /*
     * @function remove_events_from_course
     *
     * Remove all the events from a course. Including student and teacher
     * calendar.
     * 
     * Used before delete the course calendar
     *
     * @param courseid int - The target course
     *      
     */

    public function remove_events_from_course($courseid = null) {
        global $DB;

        if ($courseid == null) {
            return;
        }

        $sql = "SELECT gev.id,
                       gev.googleeventid,
                       gco.calendarid
                  FROM {local_googlecalendar} as gco,
                       {local_googlecalendar_event} as gev
                 WHERE gev.googlecalendarid = gco.id AND
                       gev.eventid in (SELECT ce.eventid
                                         FROM {local_googlecalendar} as co,
                                              {local_googlecalendar_event} as ce
                                        WHERE co.courseid = ? AND
                                              co.id = ce.googlecalendarid)";

        $results = $DB->get_records_sql($sql, array($courseid));

        //Delete the events from the database before send the delete to google
        $placeholder = '';
        $userlist = array();
        foreach ($results as $result) {
            $placeholder .= '?, ';
            $userlist[] = $result->id;
        }
        $placeholder = rtrim($placeholder, ", ");
        if ($placeholder != '') {
            $sql = "DELETE FROM {local_googlecalendar} 
                     WHERE id IN ($placeholder)";
            $DB->execute($sql, $userlist);
        }
        //Delete events in google
        foreach ($results as $result) {
            $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
        }
    }

    /*
     * remove_events_from_user
     *
     * Remove all events for the user (student) in a specific course.
     * Used when the user enrolment is removed.
     *
     * @param userid int - Target user
     * @param courseid int - Target course
     * 
     */

    public function remove_events_from_user($userid, $courseid) {
        global $DB;

        $sql = "SELECT ge.id, gc.calendarid, ge.googleeventid
                 FROM {event} ev, {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                WHERE ev.courseid = ? AND 
                      ev.id = ge.eventid AND 
                      gc.userid = ? AND 
                      gc.id = ge.googlecalendarid";

        $results = $DB->get_records_sql($sql, array($courseid, $userid));

        $placeholder = '';
        $userlist = array();

        foreach ($results as $result) {
            $placeholder .= '?, ';
            $userlist[] = $result->id;
            $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
        }

        $placeholder = rtrim($placeholder, ', ');
        if ($placeholder != '') {
            $sql = "DELETE FROM {local_googlecalendar_event} 
                     WHERE id IN (" . $placeholder . ")";
            $DB->execute($sql, $userlist);
        }
    }

    /*
     * dispatch_create
     *
     * This function will create the defined event on every affected calendar.
     * This function will use the object vars to work, so you need to set the values
     * before use.
     *
     */

    public function dispatch_create() {
        global $DB, $CFG;

        $sql = "SELECT * from {local_googlecalendar} 
                 WHERE courseid = ?";
        $result = $DB->get_records_sql($sql, array($this->eventdata->courseid));
        $result = array_pop($result);

        $event = new Google_Service_Calendar_Event();
        $event->setSummary($this->eventdata->name);
        $event->setDescription(strip_tags($this->eventdata->description));

        if ($this->eventdata->timeduration > 0) {
            //If there is time duration, event will signal during the whole period
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart + $this->eventdata->timeduration));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        } else {
            //If there is no time duration, event will signal 30 minutes before
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart - 1800));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        }

        //add event into course calendar
        $this->update_sync_token();
        $teacher_event = $this->get_service()->events->insert($result->calendarid, $event);

        $this->courseid = $this->eventdata->courseid;

        $event_object = new stdClass();
        $event_object->eventid = $this->eventdata->id;
        $event_object->googleeventid = $teacher_event->getId();
        $event_object->googlecalendarid = $result->id;
        $event_object->updated = time() + 10;

        $DB->insert_record("local_googlecalendar_event", $event_object);

        //Create event on every single reader "user calendar"
        foreach ($this->userswithaccess as $reader) {
            $student_event = $this->get_service()->events->insert($reader->calendarid, $event);

            $event_object = new stdClass();
            $event_object->eventid = $this->eventdata->id;
            $event_object->googleeventid = $student_event->getId();
            $event_object->googlecalendarid = $reader->googlecalendarid;

            $DB->insert_record("local_googlecalendar_event", $event_object);
        }
    }

    /*
     * dispatch_update
     *
     * This function will update the defined event on every affected calendar.
     * This function will use the object vars to work, so you need to set the values
     * before use.
     *
     */

    public function dispatch_update() {
        global $DB, $CFG;

        //Update teacher calendar
        $sql = "SELECT gc.calendarid, ge.googleeventid
                 FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge
                WHERE gc.courseid = ? AND 
                      ge.eventid = ? AND 
                      ge.googlecalendarid = gc.id";
        $result = $DB->get_records_sql($sql, array($this->eventdata->courseid, $this->eventdata->id));
        $result = array_pop($result);

        $event = new Google_Service_Calendar_Event();
        $event->setSummary($this->eventdata->name);
        $event->setDescription(strip_tags($this->eventdata->description));

        if ($this->eventdata->timeduration > 0) {
            //If there is time duration, event will signal during the whole period
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart + $this->eventdata->timeduration));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        } else {
            //If there is no time duration, event will signal 30 minutes before
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart - 1800));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        }

        $this->get_service()->events->update($result->calendarid, $result->googleeventid, $event);

        $this->courseid = $this->eventdata->courseid;
        $this->update_sync_token();

        //Update the users with permission
        $placeholder = '';
        $userlist = array();

        $userlist[] = $this->eventdata->id;
        foreach ($this->userswithaccess as $user) {
            $placeholder .= '?, ';
            $userlist[] = $user->id;
        }
        $placeholder = rtrim($placeholder, ', ');

        if ($placeholder != '') {
            $sql = "SELECT gc.calendarid, ge.googleeventid
                     FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                    WHERE ge.eventid = ? AND 
                          ge.googlecalendarid = gc.id AND 
                          gc.userid in ($placeholder)";

            $results = $DB->get_records_sql($sql, $userlist);

            foreach ($results as $result) {
                $this->get_service()->events->update($result->calendarid, $result->googleeventid, $event);
            }
        }

        //Delete from the users with no permission
        $placeholder = '';
        $userlist = array();
        $userlist[] = $this->eventdata->id;

        foreach ($this->userswithnoaccess as $user) {
            $placeholder .= '?, ';
            $userlist[] = $user->id;
        }
        $placeholder = rtrim($placeholder, ', ');
        if ($placeholder != '') {
            $sql = "SELECT ge.id, gc.calendarid, ge.googleeventid
                     FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                    WHERE ge.eventid = ? AND 
                          ge.googlecalendarid = gc.id AND 
                          gc.userid in ($placeholder)";

            $results = $DB->get_records_sql($sql, $userlist);

            foreach ($results as $result) {
                $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
            }

            $sql = "DELETE FROM {local_googlecalendar_event} WHERE id IN ($placeholder)";
            $DB->execute($sql, $userlist);
        }
    }

    /*
     * dispatch_delete
     *
     * Delete a event on every single calendar
     * This function will use the object vars to work, so you need to set the values
     * before use.
     *
     */

    public function dispatch_delete() {
        global $DB, $CFG;

        $sql = "SELECT ge.id, gc.calendarid, gc.courseid, ge.googleeventid 
                 FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                WHERE ge.eventid = ? AND 
                ge.googlecalendarid = gc.id";

        $results = $DB->get_records_sql($sql, Array($this->eventid));

        $id_list = Array();
        $placeholder = '';

        foreach ($results as $result) {
            $id_list[] = $result->id;
            $placeholder .= '?, ';
            try {
                $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
            } catch (Exception $exc) {
                
            }
        }

        $placeholder = rtrim($placeholder, ', ');

        $sql = "DELETE 
                  FROM {local_googlecalendar_event}
                 WHERE id IN ({$placeholder})";

        $DB->execute($sql, $id_list);
        $this->eventid = $this->eventdata;
        $this->update_sync_token();
    }

    /*
     * dispatch_show_visible
     *
     * Toggle the visibility back to visible on every affected event on the Google Calendar
     * 
     * Indeed, it will create the event on the calendar, because google do not support
     * visibility in a usable way
     * 
     * This function will use the object vars to work, so you need to set the values
     * before use.
     *
     */

    public function dispatch_show_visible() {
        global $DB, $CFG;

        $sql = "SELECT * from {local_googlecalendar} 
                 WHERE courseid = ?";
        $result = $DB->get_records_sql($sql, array($this->eventdata->courseid));
        $result = array_pop($result);

        $event = new Google_Service_Calendar_Event();

        $event->setSummary($this->eventdata->name);
        $event->setDescription(strip_tags($this->eventdata->description));

        if ($this->eventdata->timeduration > 0) {
            //If there is time duration, event will signal during the whole period
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart + $this->eventdata->timeduration));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        } else {
            //If there is no time duration, event will signal 30 minutes before
            $start = new Google_Service_Calendar_EventDateTime();
            $start->setDateTime($this::date3339($this->eventdata->timestart - 1800));
            $start->setTimeZone($CFG->timezone);
            $event->setStart($start);

            $end = new Google_Service_Calendar_EventDateTime();
            $end->setDateTime($this::date3339($this->eventdata->timestart));
            $end->setTimeZone($CFG->timezone);
            $event->setEnd($end);
        }

        //Create event on every single reader user calendar
        foreach ($this->userswithaccess as $reader) {
            $student_event = $this->get_service()->events->insert($reader->calendarid, $event);

            $event_object = new stdClass();
            $event_object->eventid = $this->eventdata->id;
            $event_object->googleeventid = $student_event->getId();
            $event_object->googlecalendarid = $reader->googlecalendarid;

            $DB->insert_record("local_googlecalendar_event", $event_object);
        }

        $this->courseid = $this->eventdata->courseid;
    }

    /*
     * dispatch_hide_visible
     *
     * Toggle the visibility to not visible on every affected event on the Google Calendar
     * 
     * Indeed, it will delete the event on the calendar, because google do not support
     * visibility in a usable way
     * 
     * This function will use the object vars to work, so you need to set the values
     * before use.
     *
     */

    public function dispatch_hide_visible() {
        global $DB, $CFG;

        $sql = "SELECT ge.id, gc.calendarid, gc.courseid, ge.googleeventid 
                 FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                WHERE ge.eventid = ? AND 
                      gc.courseid = 0 AND 
                      ge.googlecalendarid = gc.id";

        $results = $DB->get_records_sql($sql, Array($this->eventdata->id));

        $id_list = Array();
        $placeholder = '';

        foreach ($results as $result) {
            $id_list[] = $result->id;
            $placeholder .= '?, ';
            $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
        }
        $placeholder = rtrim($placeholder, ', ');

        if ($placeholder != '') {
            $sql = "DELETE 
                      FROM {local_googlecalendar_event}
                     WHERE id IN ({$placeholder})";

            $DB->execute($sql, $id_list);
        }
    }

    public function dispatch_google_create() {

        error_log(print_r($this->googleeventdata, true));
    }

    public function dispatch_google_update() {

        error_log("##########VAMOS ALTERAR ALGUMA COISA");
    }

    public function dispatch_google_delete() {
        global $DB;

        return; //This code below will blow the system so we wont execute it yet :P

        if ($this->eventdata->modulename != '0') {
            $sql = "DELETE from {" . $this->eventdata->modulename . "} 
                     WHERE id = ?";
            $DB->execute($sql, array($this->eventdata->instance));
        }

        $sql = "DELETE FROM {event} 
                 WHERE id = ? ";
        $DB->execute($sql, array($this->eventdata->id));

        $sql = "SELECT gc.calendarid, ge.googleeventid  
                  FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge
                 WHERE gc.id = ge.googlecalendarid AND 
                       ge.eventid = ? AND 
                       gc.userid != 0";

        $results = $DB->get_records_sql($sql, array($this->eventdata->id));

        foreach ($results as $result) {
            $this->get_service()->events->delete($result->calendarid, $result->googleeventid);
        }

        $sql = "DELETE 
                  FROM {local_googlecalendar_event} 
                 WHERE eventid = ?";
        $DB->execute($sql, array($this->eventdata->id));
    }

    /*
     * manage_google_request
     *
     * This function will interpret the google request and do the required
     * action
     *
     * @param (String) $resourceid - This value reference the calendar that was updated
     * 
     */

    public function manage_google_request($resourceid = null) {
        global $DB;

        $sql = "SELECT * from {local_googlecalendar} 
                 WHERE resourceid = ?";

        $result = $DB->get_records_sql($sql, array($resourceid));
        $result = array_pop($result);

        if (empty($result) || ( time() - $result->synctokenlastdate) < 5) {
            error_log(print_r("In backoff time", true));
            return;
        }

        $this->courseid = $result->courseid;

        $calendarid = $result->calendarid;
        $optparams = array(
            "updatedMin" => $this->date3339($result->synctokenlastdate)
        );

        //List of updated events
        $updated_events = $this->get_service()->events->listEvents($calendarid, $optparams);

        foreach ($updated_events->getItems() as $event) {
            $this->googleeventdata = $event;

            $sql = "SELECT ev.*, ce.updated 
                      FROM {event} ev, {local_googlecalendar_event} ce 
                     WHERE ce.googleeventid = ? AND 
                           ev.id = ce.eventid";

            $result = $DB->get_records_sql($sql, array($event->getId()));
            $this->eventdata = array_pop($result);

            //If the resource do not exist in moodle, and the action
            //was delet, ignore
            if (empty($this->eventdata) && $event->getStatus() == "cancelled") {
                continue;
            }

            //Event do not exist, so we will create it
            if (empty($this->eventdata)) {
                $this->dispatch_google_create();
            }

            //If the event say it is cancelled, so let's erase it from moodle
            if ($event->getStatus() == "cancelled") {
                $this->dispatch_google_delete();
            }

            //The event exist in moodle and was not cancelled, so it looks like
            //we need to update it
            if (!empty($this->eventdata) && $event->getStatus() != "cancelled") {
                $this->dispatch_google_update();
            }
        }

        $this->resourceid = $resourceid;
        $this->update_sync_token();
    }

    /*
     * update_sync_token
     *
     * This function will update the reference timestamp of the last time 
     * the calendar was updated
     *
     */

    private function update_sync_token() {
        global $DB;

        if (!empty($this->eventid)) {
            $sql = "SELECT gc.courseid 
                      FROM {local_googlecalendar} gc, {local_googlecalendar_event} ge 
                     WHERE ge.googlecalendarid = gc.id AND 
                           ge.eventid = ? AND 
                           gc.courseid != 0";

            $result = $DB->get_records_sql($sql, array($this->eventid));
            $this->courseid = array_pop($result)->id;
        } else if (!empty($this->resourceid)) {
            $sql = "SELECT gc.courseid 
                      FROM {local_googlecalendar} gc 
                     WHERE gc.resourceid = ?";
            $result = $DB->get_records_sql($sql, array($this->resourceid));
            $this->courseid = array_pop($result)->courseid;
        }

        $sql = "UPDATE {local_googlecalendar} 
                   SET synctokenlastdate = ? 
                 WHERE courseid = ?";

        $DB->execute($sql, array(time(), $this->courseid));
    }

    /*
     * date3339
     *
     * Convert a timestamp into a valid RFC3339 date
     *
     * @param (long) timestamp - 
     * @return (date) date - 
     */

    static function date3339($timestamp = 0) {
        if (!$timestamp) {
            $timestamp = time();
        }
        $date = date('Y-m-d\TH:i:s', $timestamp);

        $matches = array();
        if (preg_match('/^([\-+])(\d{2})(\d{2})$/', date('O', $timestamp), $matches)) {
            $date .= $matches[1] . $matches[2] . ':' . $matches[3];
        } else {
            $date .= 'Z';
        }
        return $date;
    }

}
