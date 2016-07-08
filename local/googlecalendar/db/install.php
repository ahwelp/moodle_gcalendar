<?php

$sql = "UPDATE {config} SET name = ?";
$DB->execute($sql, '../local/googlecalendar/');

