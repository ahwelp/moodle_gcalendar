<?php

defined('MOODLE_INTERNAL') || die();

// List of legacy event handlers.

$handlers = array(
        // No more old events!
);

// List of events_2 observers.
/*
$observers = array(
    array(
        'eventname' => 'core\event\course_created',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_course_created'
    ),
    array(
        'eventname' => 'core\event\course_deleted',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_course_deleted'
    ),
    array(
        'eventname' => 'core\event\role_assigned',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_role_assigned'
    ),
    array(
        'eventname' => 'core\event\role_unassigned',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_role_unassigned'
    ),
    array(
        'eventname' => 'core\event\user_created',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_user_created'
    ),
    array(
        'eventname' => 'core\event\user_deleted',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_observer::google_calendar_user_deleted'
    )
);*/
//take a look at http://stackoverflow.com/questions/26341243/moodle-service-hooks-and-integrating-with-another-system
