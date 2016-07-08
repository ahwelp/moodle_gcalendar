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

require_once '../../config.php';
$http_headers = getallheaders();

// How to know if it is realy google calling?
define('APIS_GOOGLE', 'APIs-Google; (+https://developers.google.com/webmasters/APIs-Google.html)');

if ($http_headers['User-Agent'] == APIS_GOOGLE) {        
    include_once 'classes/calendar_dispatcher.php';    
    CalendarDispatcher::google_hook($http_headers['X-Goog-Resource-ID']);    
}