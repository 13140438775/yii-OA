<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/16
 * Time: 上午11:44
 */

namespace likingfit\Workflow\Base;

use likingfit\Events\TaskEvent;
use likingfit\Events\WorkItemEvent;
use likingfit\Workflow\Exception\ProcessException;
use likingfit\Workflow\Util\EventService;
use likingfit\Workflow\Workflow;
use yii\base\Event;

class Process extends Object
{
    const INITIALIZED = 1;  //初始化状态

    const RUNNING = 2;  //运行状态

    const COMPLETED = 3;  //已经结束

    const CANCELED = 4;  //被撤销

    const EVENT_BEFORE_ABORT = "ProcessBeforeAbort";

    const EVENT_AFTER_ABORT = "ProcessAfterAbort";

    const EVENT_BEFORE_START = "ProcessBeforeStart";

    const EVENT_AFTER_START = "ProcessAfterStart";

    private $defineId;
    private $version;
    private $state;
    private $suspended;
    private $creatorId;
    private $createdTime;
    private $startedTime;
    private $endTime;
    private $expiredTime;
    private $parentProcessId;
    private $parentTaskId;
    private $variables = array();
    /**
     * @var Net
     */
    private $net;

    /**
     * @return mixed
     */
    public function getDefineId()
    {
        return $this->defineId;
    }

    /**
     * @param mixed $defineId
     */
    public function setDefineId($defineId)
    {
        $this->defineId = $defineId;
    }

    /**
     * @return mixed
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param mixed $version
     */
    public function setVersion($version)
    {
        $this->version = $version;
    }

    /**
     * @return mixed
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param mixed $state
     */
    public function setState($state)
    {
        $this->state = $state;
    }

    /**
     * @return mixed
     */
    public function getSuspended()
    {
        return $this->suspended;
    }

    /**
     * @param mixed $suspended
     */
    public function setSuspended($suspended)
    {
        $this->suspended = $suspended;
    }

    /**
     * @return mixed
     */
    public function getCreatorId()
    {
        return $this->creatorId;
    }

    /**
     * @param mixed $creatorId
     */
    public function setCreatorId($creatorId)
    {
        $this->creatorId = $creatorId;
    }

    /**
     * @return mixed
     */
    public function getCreatedTime()
    {
        return $this->createdTime;
    }

    /**
     * @param mixed $createdTime
     */
    public function setCreatedTime($createdTime)
    {
        $this->createdTime = $createdTime;
    }

    /**
     * @return mixed
     */
    public function getStartedTime()
    {
        return $this->startedTime;
    }

    /**
     * @param mixed $startedTime
     */
    public function setStartedTime($startedTime)
    {
        $this->startedTime = $startedTime;
    }

    /**
     * @return mixed
     */
    public function getEndTime()
    {
        return $this->endTime;
    }

    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime)
    {
        $this->endTime = $endTime;
    }

    /**
     * @return mixed
     */
    public function getExpiredTime()
    {
        return $this->expiredTime;
    }

    /**
     * @param mixed $expiredTime
     */
    public function setExpiredTime($expiredTime)
    {
        $this->expiredTime = $expiredTime;
    }

    /**
     * @return mixed
     */
    public function getParentProcessId()
    {
        return $this->parentProcessId;
    }

    /**
     * @param mixed $parentProcessId
     */
    public function setParentProcessId($parentProcessId)
    {
        $this->parentProcessId = $parentProcessId;
    }

    /**
     * @return mixed
     */
    public function getParentTaskId()
    {
        return $this->parentTaskId;
    }

    /**
     * @param mixed $parentTaskId
     */
    public function setParentTaskId($parentTaskId)
    {
        $this->parentTaskId = $parentTaskId;
    }


    public function getNet()
    {
        return $this->net;
    }

    public function setNet($net)
    {
        $this->net = $net;
    }

    public function setVariable($name, $value)
    {
        Workflow::getPersistenceService()->setVariable($this->getId(), $name, $value);
        $this->variables[$name] = $value;
    }

    public function setVariables($params)
    {
        foreach ($params as $name => $value) {
            $this->setVariable($name, $value);
        }
    }

    public function getVariables()
    {
        return Workflow::getPersistenceService()->getVariables($this->getId());
    }

    public function getVariable($name)
    {
        $this->variables = $this->getVariables();
        if (isset($this->variables[$name])) {
            return $this->variables[$name];
        } else {
            return null;
        }
    }

    public function start()
    {
        Event::trigger($this, self::EVENT_BEFORE_START);

        $this->setState(self::RUNNING);
        $persistService = Workflow::getPersistenceService();
        $persistService->saveProcess($this);
        $start = $this->getNet()->getStart();
        $token = new Token();
        $token->setProcessId($this->id);
        $token->setAlive(true);
        $token->setValue(1);
        $token->setStep(0);
        $eventService = Workflow::getEventService();
        if ($eventService) {
            $data = [
                'process_id' => $this->getId()
            ];
            $eventService->notify(EventService::PROCESS_RUNNING, $data);
        }
        $start->fire($token);

        Event::trigger($this, self::EVENT_AFTER_START);
        return;
    }

    public function complete()
    {
        Workflow::getLogService()->log($this->getId() . ':complete');
        $persistenceService = Workflow::getPersistenceService();
        $tokenList = $persistenceService->getProcessToken($this->id);
        foreach ($tokenList AS $token) {
            if ($token->isAlive()) {
                throw new ProcessException($this->getId(), 'Process complete failed, still has alive token');
            }
        }
        $eventService = Workflow::getEventService();
        if ($eventService) {
            $data = [
                'process_id' => $this->getId()
            ];
            $eventService->notify(EventService::PROCESS_COMPLETE, $data);
        }
        $this->state = self::COMPLETED;
        $this->endTime = date('Y-m-d H:i:s');
        $persistenceService->saveProcess($this);
        $persistenceService->delProcessToken($this->id);
        //如果为子流程
        if ($this->parentProcessId && $this->parentTaskId) {
            $parentTask = $persistenceService->getTask($this->parentTaskId);
            if ($parentTask->getType() == Task::SUBFLOW) {
                $parentProcess = Workflow::getProcess($this->parentProcessId);
                $parentProcess->completeTask($this->parentTaskId);
            }
        }
    }

    public function abort()
    {
        Event::trigger($this, self::EVENT_BEFORE_ABORT);

        $persistenceService = Workflow::getPersistenceService();
        if ($this->state == self::COMPLETED || $this->state == self::CANCELED) {
            throw new ProcessException($this->getId(), 'Process abort failed, incorrect state:' . $this->state);
        }
        $persistenceService->abortProcess($this->id);

        Event::trigger($this, self::EVENT_AFTER_ABORT);
        return;
    }

    public function suspend()
    {
        $persistenceService = Workflow::getPersistenceService();
        if ($this->state == self::COMPLETED || $this->state == self::CANCELED) {
            throw new ProcessException($this->getId(), 'Process suspend failed, incorrect state:' . $this->state);
        }
        if ($this->suspended) {
            return;
        }
        $this->suspended = true;
        $persistenceService->saveProcess($this);
    }

    public function restore()
    {
        $persistenceService = Workflow::getPersistenceService();
        if ($this->state == self::COMPLETED || $this->state == self::CANCELED) {
            throw new ProcessException($this->getId(), 'Process restore failed, incorrect state:' . $this->state);
        }
        if (!$this->suspended) {
            return;
        }
        $this->suspended = false;
        $persistenceService->saveProcess($this);
    }

    /**
     * @return Graph
     * @throws \ReflectionException
     * @CreateTime 18/4/24 11:50:00
     * @Author: fangxing@likingfit.com
     */
    public function graph()
    {
        $net = $this->getNet();
        $graph = new Graph($net);
        $tokens = Workflow::getPersistenceService()->getProcessToken($this->getId());
        $nodes = array();
        foreach ($tokens as $token) {
            $nodes[] = $net->getElement($token->getNodeId());
        }
        $graph->updateStatus($this, $nodes);
        return $graph;
    }

    /**
     * 获取流程所以第一步非子流程节点,如果为子流程,继续从子流程内向下找
     * @return Activity[]
     */
    public function getFirstNonSubflowActivities()
    {
        $persistenceService = Workflow::getPersistenceService();
        $net = $this->getNet();
        $startNode = $net->getStart();
        $result = array();
        $startNextActivities = $startNode->getNextEffectActivity();
        foreach ($startNextActivities AS $startNextActivity) {
            if ($startNextActivity->getTasks()[0]->getType() == Task::SUBFLOW) {
                $subProcessModel = $persistenceService->getActivitySubProcess($this->getId(), $startNextActivity->getId());
                $subProcess = Workflow::getProcess($subProcessModel['ID']);
                $result = array_merge($result, $subProcess->getFirstNonSubflowActivities());
            } else {
                $result[] = $startNextActivity;
            }
        }
        return $result;
    }

    /**
     * 获取流程所以最后非子流程节点,如果为子流程,继续从子流程内向下找
     *
     * @return Activity[]
     * @throws \ReflectionException
     * @CreateTime 18/4/2 12:00:21
     * @Author: fangxing@likingfit.com
     */
    public function getLastNonSubflowActivities()
    {
        $persistenceService = Workflow::getPersistenceService();
        $net = $this->getNet();
        $endNodes = $net->getEnds();
        $result = array();
        foreach ($endNodes AS $endNode) {
            $endPrevActivities = $endNode->getPrevEffectActivity();
            foreach ($endPrevActivities AS $endPrevActivity) {
                if ($endPrevActivity->getTasks()[0]->getType() == Task::SUBFLOW) {
                    $subProcessModel = $persistenceService->getActivitySubProcess($this->getId(), $endPrevActivity->getId());
                    if (!$subProcessModel['ID']) {
                        continue;
                    }
                    $subProcess = Workflow::getProcess($subProcessModel['ID']);
                    $result = array_merge($result, $subProcess->getLastNonSubflowActivities());
                } else {
                    $result[] = $endPrevActivity;
                }
            }
        }
        return $result;
    }

    /**
     * 获取某节点所有的上一步,穿透父子流程
     *
     * @param $activityId
     * @param bool $loop
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/3/27 15:07:29
     * @Author: fangxing@likingfit.com
     */
    public function getPrevActivities($activityId, $loop = false)
    {
        $persistenceService = Workflow::getPersistenceService();
        $net = $this->getNet();
        /** @var Node $node */
        $node = $net->getElement($activityId);
        if($loop){
            $prevActivities = $node->getPrevEffectActivityForLoop();
        }else{
            $prevActivities = $node->getPrevEffectActivity2($loop);
        }
        $result = array();
        if (empty($prevActivities)) {
            //上一节点为开始节点
            if ($this->getParentProcessId()) {
                $parentProcess = Workflow::getProcess($this->getParentProcessId());
                $parentTaskInstance = $persistenceService->getTask($this->getParentTaskId());
                return $parentProcess->getPrevActivities($parentTaskInstance->getActivityId(), $loop);
            } else {
                return $result;
            }
        }
        foreach ($prevActivities AS $prevActivity) {
            if ($prevActivity->getTasks()[0]->getType() == Task::FORM) {
                $result[] = $prevActivity;
            } else if ($prevActivity->getTasks()[0]->getType() == Task::SUBFLOW) {
                $subProcessModel = $persistenceService->getActivitySubProcess($this->getId(), $prevActivity->getId());
                if (!$subProcessModel['ID']) {
                    continue;
                }
                $subProcess = Workflow::getProcess($subProcessModel['ID']);
                $lastActivities = $subProcess->getLastNonSubflowActivities();
                foreach ($lastActivities AS $lastActivity) {
                    $result[] = $lastActivity;
                }
            }
        }
        return $result;
    }

    /**
     * 获取某节点所有的下一步,穿透父子流程（流程已完成时可用）
     *
     * @param $activityId
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:25:14
     * @Author: fangxing@likingfit.com
     */
    public function getNextActivities($activityId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $net = $this->getNet();
        /** @var Node $node */
        $node = $net->getElement($activityId);
        $nextActivities = $node->getNextEffectActivity();
        $result = array();
        if (empty($nextActivities)) {
            //下一节点为结束节点
            if ($this->getParentProcessId()) {
                $parentProcess = Workflow::getProcess($this->getParentProcessId());
                $parentTaskInstance = $persistenceService->getTask($this->getParentTaskId());
                return $parentProcess->getNextActivities($parentTaskInstance->getActivityId());
            } else {
                return $result;
            }
        }
        foreach ($nextActivities AS $nextActivity) {
            if ($nextActivity->getTasks()[0]->getType() == Task::FORM) {
                $result[] = $nextActivity;
            } else if ($nextActivity->getTasks()[0]->getType() == Task::SUBFLOW) {
                $subProcessModel = $persistenceService->getActivitySubProcess($this->getId(), $nextActivity->getId());
                $subProcess = Workflow::getProcess($subProcessModel['ID']);
                $firstActivities = $subProcess->getFirstNonSubflowActivities();
                foreach ($firstActivities AS $lastActivity) {
                    $result[] = $lastActivity;
                }
            }
        }
        return $result;
    }

    /**
     * 获取下一步节点
     *
     * @param $activityId
     * @return array|Activity[]
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:20:43
     * @Author: fangxing@likingfit.com
     */
    public function getNextActivitiesNew($activityId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $net = $this->getNet();
        /** @var Node $node */
        $node = $net->getElement($activityId);
        $nextActivities = $node->getNextEffectActivity();
        $result = [];
        if (empty($nextActivities)) {
            //下一节点为结束节点
            if ($this->getParentProcessId()) {
                $parentProcess = Workflow::getProcess($this->getParentProcessId());
                $parentTaskInstance = $persistenceService->getTask($this->getParentTaskId());
                return $parentProcess->getNextActivitiesNew($parentTaskInstance->getActivityId());
            } else {
                return $result;
            }
        }
        foreach ($nextActivities AS $nextActivity) {
            $task = $nextActivity->getTasks()[0];
            $type = $task->getType();
            if ($type == Task::FORM) {
                $result[] = $nextActivity;
            } else if ($type == Task::SUBFLOW) {
                $net = $this->getSubFlowInfo($task);
                $result = array_merge($result, $this->getNextActivitiesRecursive($net));
            }
        }
        return $result;
    }

    /**
     * 获取子流程信息
     *
     * @param $task Task
     * @return Net
     * @throws \ReflectionException
     * @CreateTime 18/3/27 15:00:57
     * @Author: fangxing@likingfit.com
     */
    public function getSubFlowInfo($task)
    {
        $persistenceService = Workflow::getPersistenceService();
        $defineId = $task->getParams()["process_id"];
        $define = $persistenceService->getProcessDef($defineId);
        $path = \Yii::getAlias('@app') . $define->PROCESS_PATH;
        $template = Workflow::parse($path);
        $net = $template->initNet();
        $persistenceService->cacheNet($defineId, $net); //缓存流程图
        return $net;
    }

    /**
     * 获取下一个节点Activity对象
     *
     * @param $net Net
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/3/27 15:20:21
     * @Author: fangxing@likingfit.com
     */
    public function getNextActivitiesRecursive($net)
    {
        $startNode = $net->getStart();
        $result = [];
        $startNextActivities = $startNode->getNextEffectActivity();
        foreach ($startNextActivities as $startNextActivity) {
            $task = $startNextActivity->getTasks()[0];
            if ($task->getType() == Task::SUBFLOW) {
                $net = $this->getSubFlowInfo($task);
                $result = array_merge($result, $this->getNextActivitiesRecursive($net));
            } else {
                $result[] = $startNextActivity;
            }
        }
        return $result;
    }


    /**
     * @param $nodeId
     * 重做当前流程某个节点
     * 首先找到流程中当前所有进行中的节点(currentNodeList)
     * 其中必有一节点为需重做节点的后继节点(currentSuccessorNode)
     * 从需重做节点的所有前驱节点中找到第一个与上述后继节点处于同一线上的节点(redoStartNode)
     * 收回currentSuccessorNode上所有token
     * 在redoStartNode上fire一个新token
     */
    public function redo($nodeId)
    {
//        $persistenceService = Workflow::getPersistenceService();
//        $tokenList = $persistenceService->getProcessToekn($this->getId());
//        //找出currentSuccessorNode
//        $step = 0;
//        foreach($tokenList AS $token){
//            if(isSuccessor($nodeId, $token->getNodeId())){
//                $currentSuccessorNodeId = $token->getNodeId();
//                $step = max($step, $token->getStep());
//            }
//        }
//        $currentSuccessorNode = $this->getNet()->getElement($currentSuccessorNodeId);
//        //找出redoStartNode
//        $redoStartNode = getRedoStartNode($nodeId, $currentSuccessorNodeId);
//        $persistenceService->delProcessToken($this->getId());
//        $token = new Token();
//        $token->setProcessId($this->getId());
//        $token->setValue($redoStartNode->getVolume());
//        $token->setStep($step + 1);
//        $redoStartNode->fire($token);
    }

    public function createActivityTask(Activity $activity, Token $token)
    {
        $persistenceService = Workflow::getPersistenceService();
        $taskList = array();
        foreach ($activity->getTasks() AS $task) {
            $taskInstance = new TaskInstance();
            $taskInstance->init($task);
            $taskInstance->setProcessId($this->id);
            $taskInstance->setState(TaskInstance::INITIALIZED);
            $taskInstance->setDefineId($this->getDefineId());
            $taskInstance->setVersion($this->getVersion());
            $taskInstance->setStep($token->getStep());
            $id = $persistenceService->saveTask($taskInstance);
            $taskInstance->id = $id;
            $taskList[] = $taskInstance;
        }
        foreach ($taskList as $taskInstance) {

            //触发事件
            $taskEvent = new TaskEvent;
            $taskEvent->activity = $activity;
            Event::trigger($taskInstance, TaskInstance::EVENT_BEFORE_START, $taskEvent);
            if(!$taskEvent->is_valid){
                continue;
            }

            $taskInstance->start();
            $persistenceService->saveTask($taskInstance);
            $workItem = $taskInstance->getRunner()->start($taskInstance);

            //触发事件
            $event = new WorkItemEvent;
            $event->workItem = $workItem;
            Event::trigger($taskInstance, TaskInstance::EVENT_AFTER_START, $event);
        }
    }

    /**
     * @param $activityId
     *
     * @return TaskInstance[]
     */
    public function getActivityTask($activityId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $taskList = $persistenceService->getProcessTask($this->id, $activityId);
        return $taskList;
    }

    public function completeTask($taskId)
    {
        //完成Task
        $persistenceService = Workflow::getPersistenceService();
        $task = $persistenceService->getTask($taskId);
        //非启动状态不能完成
        if ($task->getState() != self::RUNNING) {
            throw new ProcessException($this->getId(), 'Complete Task:' . $task->getId() . ' failed, incorrect state:' . $task->getState());
        }
        //挂起时不能完成
        if ($task->isSuspended()) {
            throw new ProcessException($this->getId(), 'Complete Task:' . $task->getId() . ' failed, task is suspended');
        }
        //自定义判断完成条件时
        if ($task->getCompletionEvaluator() && !$task->getCompletionEvaluator()->canComplete($task)) {
            throw new ProcessException($this->getId(), 'Complete Task:' . $task->getId() . ' failed, task can`t complete');
        }
        $task->complete();
        $persistenceService->saveTask($task);
        //尝试完成Activity
        $process = Workflow::getProcess($task->getProcessId());
        $activity = $process->getNet()->getElement($task->getActivityId());
        $tokenList = $persistenceService->getProcessToken($task->getProcessId(), $task->getActivityId());
        $activity->complete($tokenList[0]);
        return;
    }

    public function abortTask($taskId)
    {
        //中断Task
        $persistenceService = Workflow::getPersistenceService();
        $task = $persistenceService->getTask($taskId);
        if ($task->getState() == TaskInstance::COMPLETED || $task->getState() == TaskInstance::CANCELED) {
            throw new ProcessException($this->getId(), 'Abort Task:' . $task->getId() . ' failed, task incorrect state:' . $task->getState());
        }
        if ($task->isSuspended()) {
            throw new ProcessException($this->getId(), 'Abort Task:' . $task->getId() . ' failed, task is suspended');
        }
        $task->abort();
        $persistenceService->saveTask($task);
        //尝试完成Activity
        $process = Workflow::getProcess($task->getProcessId());
        $activity = $process->getNet()->getElement($task->getActivityId());
        $tokenList = $persistenceService->getProcessToken($task->getProcessId(), $task->getActivityId());
        $activity->complete($tokenList[0]);
        return;
    }

    public function suspendTask($taskId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $task = $persistenceService->getTask($taskId);
        if ($task->getState() == TaskInstance::COMPLETED || $this->getState() == TaskInstance::CANCELED) {
            throw new ProcessException($this->getId(), 'Suspend Task:' . $task->getId() . ' failed, task incorrect state:' . $task->getState());
        }
        if ($task->isSuspended()) {
            return true;
        }
        $task->suspend();
        $persistenceService->saveTask($task);
        return true;
    }

    public function restoreTask($taskId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $task = $persistenceService->getTask($taskId);
        if ($task->getState() == TaskInstance::COMPLETED || $this->getState() == TaskInstance::CANCELED) {
            throw new ProcessException($this->getId(), 'Restore Task:' . $task->getId() . ' failed, task incorrect state:' . $task->getState());
        }
        if (!$task->isSuspended()) {
            return true;
        }
        $task->restore();
        $persistenceService->saveTask($task);
        return $task;
    }

    public function assignTask($taskId, $actorId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $task = $persistenceService->getTask($taskId);
        $workItem = new WorkItem();
        $workItem->setActorId($actorId);
        $workItem->setTaskId($task->getId());
        $workItem->setState(WorkItem::INITIALIZED);
        $workItemId = $persistenceService->saveWorkItem($workItem);
        return $workItemId;
    }

    public function claimWorkItem($workItemId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $workItem = $persistenceService->getWorkItem($workItemId);
        if ($workItem->getState() != WorkItem::INITIALIZED) {
            throw new ProcessException($this->getId(), 'Claim work item:' . $workItem->getId() . ' failed, work item incorrect state:' . $workItem->getState());
        }
        $task = $persistenceService->getTask($workItem->getTaskId());
        if ($task->getState() != TaskInstance::RUNNING) {
            throw new ProcessException($this->getId(), 'Claim work item:' . $workItem->getId() . ' failed, task incorrect state:' . $task->getState());
        }
        if ($task->isSuspended()) {
            throw new ProcessException($this->getId(), 'Claim work item:' . $workItem->getId() . ' failed, work item is suspended');
        }
        $workItem->claim();
        $persistenceService->saveWorkItem($workItem);
    }

    public function completeWorkItem($workItemId)
    {
        $persistenceService = Workflow::getPersistenceService();
        $workItem = $persistenceService->getWorkItem($workItemId);
        $task = $persistenceService->getTask($workItem->getTaskId());
        if ($workItem->getState() != WorkItem::CLAIMED) {
            throw new ProcessException($this->getId(), 'Complete work item:' . $workItem->getId() . ' failed, work item incorrect state:' . $workItem->getState());
        }
        if ($task->getState() != TaskInstance::RUNNING) {
            throw new ProcessException($this->getId(), 'Complete work item:' . $workItem->getId() . ' failed, task incorrect state:' . $task->getState());
        }
        if ($task->isSuspended()) {
            throw new ProcessException($this->getId(), 'Complete work item:' . $workItem->getId() . ' failed, work item is suspended');
        }
        $workItem->complete();
        $persistenceService->saveWorkItem($workItem);
        $this->completeTask($task->getId());
    }
}