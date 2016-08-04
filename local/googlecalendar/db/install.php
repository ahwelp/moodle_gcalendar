<?php

function xmldb_local_googlecalendar_install() {
    global $DB;
    if ($DB->get_record('config', array('name' => 'calendar'))) {
        $sql = "UPDATE {config} SET value = ? WHERE name = 'calendar'";
        $DB->execute($sql, array('../local/googlecalendar/'));
    } else {
        $sql = "INSERT INTO {config} (value, name) VALUES (?, ?)";
        $DB->execute($sql, array('../local/googlecalendar/', 'calendar'));
    }
}
