<?php
namespace likingfit\Workflow\Util;

use likingfit\Workflow\Workflow;
interface EventService{
    CONST PROCESS_RUNNING = 'running';
    CONST PROCESS_COMPLETE = 'complete';
    /**
     * @param $event
     * @param $data
     *
     * @return mixed
     */
    public static function notify($event, $data);
}