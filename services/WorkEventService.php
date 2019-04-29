<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/2/24
 * Time: 下午5:30
 */

namespace app\services;

use app\models\Flow;
use likingfit\Workflow\Util\EventService;

class WorkEventService implements EventService
{
    public static function notify($event, $data)
    {
        switch ($event) {
            case self::PROCESS_RUNNING:
                {
                    $flow = FlowService::getProcessFlow($data['process_id']);
                    $flow->flow_status = Flow::RUNNING;
                    $flow->save();
                    break;
                }
            case self::PROCESS_COMPLETE:
                {
                    FlowService::completeFlowByProcessId($data['process_id']);
                    break;
                }
        }
        return;
    }
}