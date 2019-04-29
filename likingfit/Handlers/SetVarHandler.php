<?php
/**
 * 合营蛋疼的选择订单
 *
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/11 11:36:57
 */

namespace likingfit\Handlers;


use likingfit\Workflow\Base\Process;
use likingfit\Workflow\Workflow;

class SetVarHandler implements Handler
{
    public function events()
    {
        return [
            Process::EVENT_BEFORE_START => "beforeStart"
        ];
    }

    /**
     * 将父流程变量在子流程中重现
     *
     * @param $event
     * @throws \ReflectionException
     * @CreateTime 18/4/11 13:27:21
     * @Author: fangxing@likingfit.com
     */
    public function beforeStart($event)
    {
        $process = $event->sender;
        /**
         * @var $process Process
         */
        $parentProcessId = $process->getParentProcessId();
        if($parentProcessId){
            $parentProcess = Workflow::getProcess($parentProcessId);
            $process->setVariables($parentProcess->getVariables());
        }
    }
}