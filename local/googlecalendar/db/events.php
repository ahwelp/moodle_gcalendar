<?php

defined('MOODLE_INTERNAL') || die();

// List of legacy event handlers.

$handlers = array(
        // No more old events!
);

// List of events_2 observers.

$observers = array(
    array(
        'eventname' => 'core\event\course_created',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_update_observer::google_calendar_course_created'
    ),
    array(
        'eventname' => 'core\event\course_updated',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_update_observer::google_calendar_course_updated'
    ),
    array(
        'eventname' => 'core\event\course_deleted',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_update_observer::google_calendar_course_deleted'
    ),
    array(
        'eventname' => 'core\event\role_assigned',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_update_observer::google_calendar_role_assigned'
    ),
    array(
        'eventname' => 'core\event\role_unassigned',
        'includefile' => 'local/googlecalendar/classes/observer.php',
        'callback' => 'google_calendar_course_update_observer::google_calendar_role_unassigned'
    )
);

