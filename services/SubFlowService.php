<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/24
 * Time: 下午2:42
 */

namespace app\services;

use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Workflow;

class SubFlowService
{
    public function start(TaskInstance $task)
    {
        $process = Workflow::getProcess($task->getProcessId());
        $taskParams = $task->getParams();
        $subDefId = $taskParams['process_id'];
        list($flow, $subProcess) = FlowService::startSubProcess($subDefId, 0, $process->getId(), $task->getId());
        $subProcess->start();
        return;
    }
}