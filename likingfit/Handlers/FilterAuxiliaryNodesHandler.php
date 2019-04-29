<?php
/**
 * 合营蛋疼的选择订单
 *
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/11 11:36:57
 */

namespace likingfit\Handlers;


use likingfit\Events\TaskEvent;
use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Workflow;

class FilterAuxiliaryNodesHandler implements Handler
{
    public function events()
    {
        return [
            TaskInstance::EVENT_BEFORE_START => "beforeStart"
        ];
    }

    /**
     * 过滤辅助模块并自动完成节点
     *
     * @param TaskEvent $event
     * @throws \ReflectionException
     * @throws \likingfit\Workflow\Exception\ProcessException
     * @CreateTime 18/4/11 12:30:28
     * @Author: fangxing@likingfit.com
     */
    public function beforeStart(TaskEvent $event)
    {
        $taskInstance = $event->sender;
        /**
         * @var $taskInstance TaskInstance
         */
        if($taskInstance->getDisplayName() == "辅助模块"){
            $process = Workflow::getProcess($taskInstance->getProcessId());
            $taskInstance->start();
            $persistenceService = Workflow::getPersistenceService();
            $persistenceService->saveTask($taskInstance);
            $process->completeTask($taskInstance->getId());
            $event->is_valid = false;
        }
    }
}