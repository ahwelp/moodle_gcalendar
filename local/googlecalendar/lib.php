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

function googlecalendar_add_event($args){
    CalendarDispatcher::add_event($args);    
}

function googlecalendar_update_event($args){
    CalendarDispatcher::update_event($args);
}

function googlecalendar_delete_event($args){
    CalendarDispatcher::delete_event($args);
}

function googlecalendar_show_event($args){
    CalendarDispatcher::show_event($args);
}

function googlecalendar_hide_event($args){
    CalendarDispatcher::hide_event($args);
}

