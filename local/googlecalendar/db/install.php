<?php

$sql = "UPDATE {config} SET value = ? WHERE name = 'calendar'";
$DB->execute($sql, '../local/googlecalendar/');

