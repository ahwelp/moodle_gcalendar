<?php
//If it don't woks, just make it by hand
if( $DB->get_record('config', array('name'=>'calendar')) ){
  $sql = "UPDATE {config} SET value = ? WHERE name = 'calendar'";
  $DB->execute($sql, '../local/googlecalendar/');
}else{
  $sql = "INSERT INTO {config} (value, name) VALUES (?, ?)";
  $DB->execute($sql, '../local/googlecalendar/');
}
