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

    private $readers = array();
    private $userswithacess = array();
    private $userswithnoacess = array();
    private $eventdata;
    private $resourcedata;

    function set_eventdata($eventdata) {
        $this->eventdata = $eventdata;
    }

    function load_data() {
        global $DB;

        $this->eventdata = $DB->get_record("event", array('id' => $this->eventdata));

        if ($this->eventdata->modulename != 0) {
            $this->resourcedata = $DB->get_record($this->eventdata->modulename, array('id' => $this->eventdata->instance));
        }
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


        $cm = get_coursemodule_from_instance($this->eventdata->modulename, $this->eventdata->instance);
        foreach ($this->readers as $reader) {
            if (\core_availability\info_module::is_user_visible($cm, $reader->id, false)) {
                $this->userswithaccess[] = $reader;
            } else {
                $this->userswithnoaccess[] = $reader;
            }
        }
    }

    /*
     * create_late_events
     * 
     * This function will be executed whenever a user is enroled as a student.
     * This function will serach for every event in a course the user has the
     * right to see. We will create this events on the user calendar.
     *
     * @param courseid int - Target course 
     * @param userid int - Target user
     *  
     */

    public function create_late_events($userid, $courseid = null) {
        global $DB, $CFG;

        $user_calendar_id = '';

        $sql = "SELECT id, calendarid 
                  FROM {local_googlecalendar} 
                 WHERE userid = ? ";

        $result = $DB->get_records_sql($sql, array($userid));
        $calendar_data = array_pop($result);

        $user_calendar_id = $calendar_data->calendarid;

        if ($courseid != null) {
            //Iterate over a course
            $sql = "SELECT * 
                   FROM {event} 
                  WHERE courseid = ? ";

            $results = $DB->get_records_sql($sql, array($courseid));
        } else {
            //Iterate over the whole system
            $sql = "SELECT * 
                      FROM {event}
                     WHERE courseid IN ( SELECT c.id                                                
                                           FROM mdl_course c,
                                                mdl_context cx,
                                                mdl_role_assignments r,
                                                mdl_user u,
                                                mdl_role role
                                          WHERE u.id = ? AND
                                                role.shortname = 'student' AND
                                                cx.instanceid = c.id AND
                                                cx.id = r.contextid AND
                                                u.id=r.userid AND
                                                r.roleid=role.id
                                                ORDER BY c.fullname;)";

            $results = $DB->get_records_sql($sql, array($userid));
        }

        foreach ($results as $result) {
            $cm = get_coursemodule_from_instance($result->modulename, $result->instance);

            if (\core_availability\info_module::is_user_visible($cm, $userid, true)) {

                $event = new Google_Service_Calendar_Event();
                $event->setSummary($result->name);
                $event->setDescription(strip_tags($result->description));

                if ($result->timeduration > 0) {
                    //If there is time duration, event will signal during the whole period
                    $start = new Google_Service_Calendar_EventDateTime();
                    $start->setDateTime($this::date3339($result->timestart));
                    $start->setTimeZone($CFG->timezone);
                    $event->setStart($start);

                    $end = new Google_Service_Calendar_EventDateTime();
                    $end->setDateTime($this::date3339($result->timestart + $result->timeduration));
                    $end->setTimeZone($CFG->timezone);
                    $event->setEnd($end);
                } else {
                    //If there is no time duration, event will signal 30 minutes before
                    $start = new Google_Service_Calendar_EventDateTime();
                    $start->setDateTime($this::date3339($result->timestart - 1800));
                    $start->setTimeZone($CFG->timezone);
                    $event->setStart($start);

                    $end = new Google_Service_Calendar_EventDateTime();
                    $end->setDateTime($this::date3339($result->timestart));
                    $end->setTimeZone($CFG->timezone);
                    $event->setEnd($end);
                }

                $eventid = $this->get_service()->events->insert($calendar_data->calendarid, $event);

                $event_object = new stdClass();
                $event_object->eventid = $result->id;
                $event_object->googleeventid = $eventid->getId();
                $event_object->googlecalendarid = $calendar_data->id;
                $DB->insert_record("local_googlecalendar_event", $event_object);
            }
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

        $result = $DB->get_record("local_googlecalendar", array("courseid" => $this->eventdata->courseid));

        $event = new Google_Service_Calendar_Event();

        //Events sometimes have different names like "quiz open"
        //this can be a problem for the teacher
        if (!empty($this->resourcedata)) {
            //if the event refer to a resource, let's take the resource name
            $event->setSummary($this->resourcedata->name);
        } else {
            //if it is a simple event, let's take the event name
            $event->setSummary($this->eventdata->name);
        }

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
        if (!empty($result)) {
            $this->update_sync_token();
            $teacher_event = $this->get_service()->events->insert($result->calendarid, $event);

            $this->courseid = $this->eventdata->courseid;

            $event_object = new stdClass();
            $event_object->eventid = $this->eventdata->id;
            $event_object->googleeventid = $teacher_event->getId();
            $event_object->googlecalendarid = $result->id;
            $event_object->updated = time() + 10;

            $DB->insert_record("local_googlecalendar_event", $event_object);
        }

        //Create event on every single reader "user calendar"
        foreach ($this->userswithaccess as $reader) {
            $student_event = $this->get_service()->events->insert($reader->calendarid, $event);

            $event_object = new stdClass();
            $event_object->eventid = $this->eventdata->id;
            $event_object->googleeventid = $student_event->getId();
            $event_object->googlecalendarid = $reader->googlecalendarid;

            $DB->insert_record("local_googlecalendar_event", $event_object);
        }
        return true;
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

        $results = $DB->get_records_sql($sql, Array($this->eventdata));

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
        //$this->update_sync_token();
        return true;
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

        if (empty($result)) {
            return false;
        }

        $result = array_pop($result);

        $event = new Google_Service_Calendar_Event();

        //Events sometimes have different names like "quiz open"
        //this can be a problem for the teacher
        if (!empty($this->resourcedata)) {
            //if the event refer to a resource, let's take the resource name
            $event->setSummary($this->resourcedata->name);
        } else {
            //if it is a simple event, let's take the event name
            $event->setSummary($this->eventdata->name);
        }

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
     * update_sync_token
     *
     * This function will update the reference timestamp of the last time 
     * the calendar was updated
     *
     */

    private function update_sync_token() {
        global $DB;

        return;

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
