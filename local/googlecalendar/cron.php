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
define('CLI_SCRIPT', true);
require_once '../../config.php';

include_once 'classes/calendar_dispatcher.php';

$results = $DB->get_records_sql("SELECT * FROM {local_googlecalendar_cron} ORDER BY id");

foreach ($results as $result) {
    CalendarDispatcher::calendar_event_hook(trim($result->action), $result);
}
