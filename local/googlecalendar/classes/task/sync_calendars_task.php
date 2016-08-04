<?php

namespace local_googlecalendar\task;

class sync_calendars_task extends \core\task\scheduled_task {

    /**
     * Nome descritivo para este agendamento.
     *
     * @return string
     */
    public function get_name() {
        return 'calendar_sync_task';
    }

    /**
     * Método que será executado pela cron
     */
    public function execute() {
        global $CFG;
        //For now just in linux
        exec("php $CFG->dirroot/local/googlecalendar/cron.php &");
    }

}
