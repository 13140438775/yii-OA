<?php

namespace app\services;

use likingfit\Workflow\Base\Net;
use likingfit\Workflow\Base\Token;
use likingfit\Workflow\Util\PersistenceService;
use app\models\RtToken;
use likingfit\Workflow\Base\Task;
use likingfit\Workflow\Base\WorkItem;
use app\models\TaskInstance;
use app\models\RtWorkitem;
use likingfit\Workflow\Base\Process;
use app\models\ProcessInstance;
use app\models\ProcinstVar;
use app\models\Workflowdef;
use likingfit\Workflow\Exception\FlowException;
use likingfit\Workflow\Workflow;
use yii\helpers\ArrayHelper;

class WorkflowPersistenceService implements PersistenceService
{
    /**
     * @param array $conditions
     * @return Token[]
     * @CreateTime 18/4/24 11:46:15
     * @Author: fangxing@likingfit.com
     */
    public static function getTokens($conditions=[])
    {
        $result = [];
        $rtTokens = RtToken::findAll($conditions);
        if (empty($rtTokens)) {
            return $result;
        }
        foreach ($rtTokens as $rtToken) {
            $attr = $rtToken->attributes;
            $token = new Token();
            $token->setId($attr['ID']);
            $token->setAlive($attr['ALIVE']);
            $token->setValue($attr['VALUE']);
            $token->setNodeId($attr['NODE_ID']);
            $token->setProcessId($attr['PROCESSINSTANCE_ID']);
            $token->setStep($attr['STEP_NUMBER']);
            $token->setFromActivityId($attr['FROM_ACTIVITY_ID']);
            $result[] = $token;
        }
        return $result;
    }

    /**
     * 设置token
     *
     * @param Token $token
     * @return bool|mixed|string
     * @throws \yii\base\Exception
     * @CreateTime 18/4/24 11:47:10
     * @Author: fangxing@likingfit.com
     */
    public static function saveToken(Token $token)
    {
        $tokenId = $token->getId();
        if (!empty($tokenId)) {
            $rtToken = RtToken::findOne($tokenId);
            if (empty($rtToken)) {
                return false;
            }
            $rtToken->ALIVE = $token->isAlive();
            $rtToken->VALUE = $token->getValue();
            $rtToken->NODE_ID = $token->getNodeId();
            $rtToken->PROCESSINSTANCE_ID = $token->getProcessId();
            $rtToken->STEP_NUMBER = $token->getStep();
            $rtToken->FROM_ACTIVITY_ID = $token->getFromActivityId();
            $rtToken->save();
        } else {
            $rtToken = new RtToken();
            $rtToken->ID = generateId();
            $rtToken->ALIVE = $token->isAlive();
            $rtToken->VALUE = $token->getValue();
            $rtToken->NODE_ID = $token->getNodeId();
            $rtToken->PROCESSINSTANCE_ID = $token->getProcessId();
            $rtToken->STEP_NUMBER = $token->getStep();
            $rtToken->FROM_ACTIVITY_ID = $token->getFromActivityId();
            $rtToken->save();
        }
        return $rtToken->ID;
    }

    /**
     * 删除token 暂不实现
     */
    public static function delToken($tokenId)
    {
        RtToken::updateAll(["VALID" => 0], ['ID' => $tokenId]);
        return true;
    }

    /**
     * @param $processId
     * @param null $nodeId
     * @return array|Token[]
     * @CreateTime 18/4/13 14:23:42
     * @Author: fangxing@likingfit.com
     */
    public static function getProcessToken($processId, $nodeId = null)
    {
        $param = ['PROCESSINSTANCE_ID' => $processId, "VALID" => 1];
        if (!empty($nodeId)) {
            $param['NODE_ID'] = $nodeId;
        }
        return self::getTokens($param);
    }

    /**
     * 删除进程token
     *
     * @param unknown $processId
     * @param string $nodeId
     */
    public static function delProcessToken($processId, $nodeId = null)
    {
        $param = ['PROCESSINSTANCE_ID' => $processId];
        if (!empty($param)) {
            $param['NODE_ID'] = $nodeId;
        }
        RtToken::updateAll(["VALID" => 0], $param);
    }

    /**
     * 保存任务
     *
     * @param Task $task
     *
     * @return boolean
     */
    public static function saveTask(\likingfit\Workflow\Base\TaskInstance $task)
    {
        $taskInstanceId = $task->getId();
        if (!empty($taskInstanceId)) {
            $taskInstance = TaskInstance::findOne($taskInstanceId);
            if (empty($taskInstance)) {
                return false;
            }
            $taskInstance->TASK_ID = $task->getTaskId();
            $taskInstance->ACTIVITY_ID = $task->getActivityId();
            $taskInstance->NAME = $task->getName();
            $taskInstance->DISPLAY_NAME = $task->getDisplayName();
            $taskInstance->STATE = $task->getState();
            $taskInstance->SUSPENDED = $task->isSuspended();
            $taskInstance->TASK_TYPE = $task->getType();
            $taskInstance->CREATED_TIME = $task->getCreateTime();
            $taskInstance->STARTED_TIME = $task->getStartedTime();
            $taskInstance->EXPIRED_TIME = $task->getExpiredTime();
            $taskInstance->END_TIME = $task->getEndTime();
            $taskInstance->PROCESSINSTANCE_ID = $task->getprocessId();
            $taskInstance->PROCESS_ID = $task->getdefineId();
            $taskInstance->VERSION = $task->getversion();
            $taskInstance->STEP_NUMBER = $task->getStep();
            $taskInstance->CAN_BE_WITHDRAWN = $task->getCanCancel();
            $taskInstance->save();
        } else {
            $taskInstance = new TaskInstance();
            $taskInstance->ID = generateId();
            $taskInstance->BIZ_TYPE = " ";
            $taskInstance->TASK_ID = $task->getTaskId();
            $taskInstance->ACTIVITY_ID = $task->getActivityId();
            $taskInstance->NAME = $task->getName();
            $taskInstance->DISPLAY_NAME = $task->getDisplayName();
            $taskInstance->STATE = $task->getState();
            $taskInstance->SUSPENDED = $task->isSuspended();
            $taskInstance->TASK_TYPE = $task->getType();
            $taskInstance->CREATED_TIME = $task->getCreateTime();
            $taskInstance->STARTED_TIME = $task->getStartedTime();
            $taskInstance->EXPIRED_TIME = $task->getExpiredTime();
            $taskInstance->END_TIME = $task->getEndTime();
            $taskInstance->PROCESSINSTANCE_ID = $task->getprocessId();
            $taskInstance->PROCESS_ID = $task->getdefineId();
            $taskInstance->VERSION = $task->getversion();
            $taskInstance->STEP_NUMBER = $task->getStep();
            $taskInstance->CAN_BE_WITHDRAWN = $task->getCanCancel();
            $taskInstance->save();
        }
        return $taskInstance->ID;
    }

    /**
     * 获取任务
     *
     * @param unknown $taskInstanceId
     *
     * @return NULL|\likingfit\Workflow\Base\Task
     */
    public static function getTask($taskInstanceId)
    {
        $taskInstance = TaskInstance::findOne($taskInstanceId);
        if (empty($taskInstance)) {
            return null;
        }
        $task = new \likingfit\Workflow\Base\TaskInstance();
        $task->setId($taskInstance->ID);
        $task->setTaskId($taskInstance->TASK_ID);
        $task->setActivityId($taskInstance->ACTIVITY_ID);
        $task->setName($taskInstance->NAME);
        $task->setDisplayName($taskInstance->DISPLAY_NAME);
        $task->setState($taskInstance->STATE);
        $task->setSuspended($taskInstance->SUSPENDED);
        $task->setType($taskInstance->TASK_TYPE);
        $task->setCreateTime($taskInstance->CREATED_TIME);
        $task->setStartedTime($taskInstance->STARTED_TIME);
        $task->setExpiredTime($taskInstance->EXPIRED_TIME);
        $task->setEndTime($taskInstance->END_TIME);
        $task->setProcessId($taskInstance->PROCESSINSTANCE_ID);
        $task->setDefineId($taskInstance->PROCESS_ID);
        $task->setVersion($taskInstance->VERSION);
        $task->setStep($taskInstance->STEP_NUMBER);
        $task->setCanCancel($taskInstance->CAN_BE_WITHDRAWN);
        return $task;
    }

    public static function getProcessTask($processId, $nodeId = null)
    {
        $condition = [
            'PROCESSINSTANCE_ID' => $processId,
            'ACTIVITY_ID' => $nodeId
        ];
        $taskList = TaskInstance::find()->where($condition)->all();
        $taskArr = array();
        foreach ($taskList AS $taskInstance) {
            $task = new \likingfit\Workflow\Base\TaskInstance();
            $task->setId($taskInstance->ID);
            $task->setTaskId($taskInstance->TASK_ID);
            $task->setActivityId($taskInstance->ACTIVITY_ID);
            $task->setName($taskInstance->NAME);
            $task->setDisplayName($taskInstance->DISPLAY_NAME);
            $task->setState($taskInstance->STATE);
            $task->setSuspended($taskInstance->SUSPENDED);
            $task->setType($taskInstance->TASK_TYPE);
            $task->setCreateTime($taskInstance->CREATED_TIME);
            $task->setStartedTime($taskInstance->STARTED_TIME);
            $task->setExpiredTime($taskInstance->EXPIRED_TIME);
            $task->setEndTime($taskInstance->END_TIME);
            $task->setProcessId($taskInstance->PROCESSINSTANCE_ID);
            $task->setDefineId($taskInstance->PROCESS_ID);
            $task->setVersion($taskInstance->VERSION);
            $task->setStep($taskInstance->STEP_NUMBER);
            $task->setCanCancel($taskInstance->CAN_BE_WITHDRAWN);
            $taskArr[] = $task;
        }
        return $taskArr;
    }

    /**
     * 保存工作项
     *
     * @param WorkItem $workItem
     */
    public static function saveWorkItem(WorkItem $workItem)
    {
        $itemId = $workItem->getId();
        if (!empty($itemId)) {
            $rtWorkItem = RtWorkitem::findOne($itemId);
            $rtWorkItem->STATE = $workItem->getState();
            $rtWorkItem->CLAIMED_TIME = $workItem->getClaimTime();
            $rtWorkItem->END_TIME = $workItem->getEndTime();
            $rtWorkItem->ACTOR_ID = $workItem->getActorId();
            $rtWorkItem->TASKINSTANCE_ID = $workItem->getTaskId();
            $rtWorkItem->save();
        } else {
            $rtWorkItem = new RtWorkitem();
            $rtWorkItem->ID = generateId();
            $rtWorkItem->STATE = $workItem->getState();
            $rtWorkItem->CREATED_TIME = date('Y-m-d H:i:s');
            $rtWorkItem->ACTOR_ID = $workItem->getActorId();
            $rtWorkItem->TASKINSTANCE_ID = $workItem->getTaskId();
            $rtWorkItem->save();
        }
        return $rtWorkItem->ID;
    }

    /**
     * 获取工作项
     *
     * @param unknown $itemId
     *
     * @return NULL|\likingfit\Workflow\Base\WorkItem
     */
    public static function getWorkItem($itemId)
    {
        $rtWorkItem = RtWorkitem::findOne($itemId);
        if (empty($rtWorkItem)) {
            return null;
        }
        $workItem = new WorkItem();
        $workItem->setId($rtWorkItem->ID);
        $workItem->setState($rtWorkItem->STATE);
        $workItem->setCreateTime($rtWorkItem->CREATED_TIME);
        $workItem->setClaimTime($rtWorkItem->CLAIMED_TIME);
        $workItem->setEndTime($rtWorkItem->END_TIME);
        $workItem->setActorId($rtWorkItem->ACTOR_ID);
        $workItem->setTaskId($rtWorkItem->TASKINSTANCE_ID);
        return $workItem;
    }

    /**
     * 获取用户工作项
     * @param       $userId
     * @param array $state
     *
     * @return WorkItem[]
     */
    public static function getUserWorkItem($userId, $state = [])
    {
        $condition = [
            'ACTOR_ID' => $userId,
        ];
        if (!empty($state)) {
            $condition['STATE'] = $state;
        }
        $rtWorkItemList = RtWorkitem::find()->where($condition)->all();
        $workItemList = array();
        foreach ($rtWorkItemList AS $rtWorkItem) {
            $workItem = new WorkItem();
            $workItem->setId($rtWorkItem->ID);
            $workItem->setState($rtWorkItem->STATE);
            $workItem->setCreateTime($rtWorkItem->CREATED_TIME);
            $workItem->setClaimTime($rtWorkItem->CLAIMED_TIME);
            $workItem->setEndTime($rtWorkItem->END_TIME);
            $workItem->setActorId($rtWorkItem->ACTOR_ID);
            $workItem->setTaskId($rtWorkItem->TASKINSTANCE_ID);
            $workItemList[] = $workItem;
        }
        return $workItemList;
    }

    /**
     * 保存工作流实例
     *
     * @param Process $process
     *
     * @return boolean
     */
    public static function saveProcess(Process $process)
    {
        $pid = $process->getId();
        if (!empty($pid)) {
            $instanceProcess = ProcessInstance::findOne($pid);
            if (empty($instanceProcess)) {
                return false;
            }
            $instanceProcess->NAME = $process->getName();
            $instanceProcess->DISPLAY_NAME = $process->getDisplayName();
            $instanceProcess->STATE = $process->getState();
            $instanceProcess->SUSPENDED = $process->getSuspended();
            $instanceProcess->CREATOR_ID = $process->getCreatorId();
            $instanceProcess->CREATED_TIME = $process->getCreatedTime();
            $instanceProcess->STARTED_TIME = $process->getStartedTime();
            $instanceProcess->EXPIRED_TIME = $process->getExpiredTime();
            $instanceProcess->END_TIME = $process->getEndTime();
            $instanceProcess->PARENT_PROCESSINSTANCE_ID = $process->getParentProcessId();
            $instanceProcess->PARENT_TASKINSTANCE_ID = $process->getParentTaskId();
            $instanceProcess->save();
        } else {
            $instanceProcess = new ProcessInstance();
            $instanceProcess->ID = generateId();
            $instanceProcess->PROCESS_ID = $process->getDefineId();
            $instanceProcess->VERSION = $process->getVersion();
            $instanceProcess->NAME = $process->getName();
            $instanceProcess->DISPLAY_NAME = $process->getDisplayName();
            $instanceProcess->STATE = $process->getState();
            $instanceProcess->SUSPENDED = $process->getSuspended();
            $instanceProcess->CREATOR_ID = $process->getCreatorId();
            $instanceProcess->CREATED_TIME = $process->getCreatedTime();
            $instanceProcess->STARTED_TIME = $process->getStartedTime();
            $instanceProcess->EXPIRED_TIME = $process->getExpiredTime();
            $instanceProcess->END_TIME = $process->getEndTime();
            $instanceProcess->PARENT_PROCESSINSTANCE_ID = $process->getParentProcessId();
            $instanceProcess->PARENT_TASKINSTANCE_ID = $process->getParentTaskId();
            $instanceProcess->save();
        }
        return $instanceProcess->ID;
    }

    /**
     * 获取工作流实例
     *
     * @param unknown $processId
     */
    public static function getProcess($processId)
    {
        $instanceProcess = ProcessInstance::findOne($processId);
        if (empty($instanceProcess)) {
            return null;
        }
        $process = new Process();
        $process->setId($instanceProcess->ID);
        $process->setDefineId($instanceProcess->PROCESS_ID);
        $process->setVersion($instanceProcess->VERSION);
        $process->setName($instanceProcess->NAME);
        $process->setDisplayName($instanceProcess->DISPLAY_NAME);
        $process->setState($instanceProcess->STATE);
        $process->setSuspended($instanceProcess->SUSPENDED);
        $process->setCreatorId($instanceProcess->CREATOR_ID);
        $process->setCreatedTime($instanceProcess->CREATED_TIME);
        $process->setStartedTime($instanceProcess->STARTED_TIME);
        $process->setExpiredTime($instanceProcess->EXPIRED_TIME);
        $process->setEndTime($instanceProcess->END_TIME);
        $process->setParentProcessId($instanceProcess->PARENT_PROCESSINSTANCE_ID);
        $process->setParentTaskId($instanceProcess->PARENT_TASKINSTANCE_ID);
        return $process;
    }

    public static function getActivitySubProcess($parentProcessId, $activityId)
    {
        $condition = [
            'PROCESSINSTANCE_ID' => $parentProcessId,
            'ACTIVITY_ID' => $activityId
        ];
        $parentTask = TaskInstance::find()->where($condition)->one();
        if (!$parentTask) {
            return 0;
        }
        $condition = [
            'PARENT_TASKINSTANCE_ID' => $parentTask['ID']
        ];
        $subProcess = ProcessInstance::find()->where($condition)->one();
        return $subProcess;
    }

    /**
     * 设置实例参数
     *
     * @param unknown $processId
     * @param unknown $name
     * @param unknown $value
     */
    public static function setVariable($processId, $name, $value)
    {
        $item = ProcinstVar::findOne([
            'PROCESSINSTANCE_ID' => $processId,
            'NAME' => $name
        ]);
        if (!empty($item)) {
            $item->VALUE = $value;
            $item->save();
        } else {
            $item = new ProcinstVar();
            $item->PROCESSINSTANCE_ID = $processId;
            $item->NAME = $name;
            $item->VALUE = $value;
            $item->save();
        }
        return $item->attributes;
    }

    /**
     * 获取实例参数
     *
     * @param $processId
     * @return array
     * @CreateTime 18/3/22 16:37:35
     * @Author: fangxing@likingfit.com
     */
    public static function getVariables($processId)
    {
        $items = ProcinstVar::findAll(['PROCESSINSTANCE_ID' => $processId]);
        $result = [];
        if (empty($items)) {
            return $result;
        }
        foreach ($items as $var) {
            $result[$var->NAME] = $var->VALUE;
        }
        return $result;
    }

    /**
     * 获取流程实例某个变量
     *
     * @param $processId
     * @param $name
     * @return string
     * @CreateTime 18/3/22 16:33:52
     * @Author: fangxing@likingfit.com
     */
    public static function getVariableByName($processId, $name)
    {
        $item = ProcinstVar::findOne(['PROCESSINSTANCE_ID' => $processId, "NAME" => $name]);
        return $item ? $item->VALUE : null;
    }

    /**
     * 取消流程
     *
     * @param string $processId
     */
    public static function abortProcess($processId)
    {
        $subFlows = ProcessInstance::findAll(["PARENT_PROCESSINSTANCE_ID" => $processId]);
        foreach ($subFlows as $flow) {
            self::abortProcess($flow->ID);
        }
        $persistenceService = Workflow::getPersistenceService();
        $persistenceService::delProcessToken($processId);
        ProcessInstance::updateAll(['STATE' => Process::CANCELED], ['ID' => $processId]);  //更新实例
        $tasks = TaskInstance::findAll(['PROCESSINSTANCE_ID' => $processId]);
        $taskIds = [];
        foreach ($tasks as $task) {
            $task->STATE = Task::CANCELED;
            $task->save();
            $taskIds[] = $task->ID;
        }
        if (!empty($taskIds)) {
            RtWorkitem::updateAll(['STATE' => WorkItem::CANCELED,], ['TASKINSTANCE_ID' => $taskIds]);
        }
    }

    /**
     * @param      $processId
     * @param null $version
     *
     * @return array|null|\yii\db\ActiveRecord
     * @throws FlowException
     */
    public static function getProcessDef($processId, $version = null)
    {
        $condition = [
            'PROCESS_ID' => $processId,
            'STATE' => 1
        ];
        if (!empty($version)) {
            $condition['VERSION'] = $version;
        }
        $def = Workflowdef::find()->where($condition)->orderBy(['VERSION' => SORT_DESC])->one();
        if (empty($def)) {
            throw new FlowException('not found define xml record');
        }
        return $def;
    }

    public static function getProcessIdByVariable($name, $value)
    {
        $condition = [
            'name' => $name,
            'value' => $value
        ];
        $processVar = ProcinstVar::find()->where($condition)->all();
        return ArrayHelper::getcolumn($processVar, 'PROCESSINSTANCE_ID');
    }

    /**
     * 缓存流程图
     *
     * @param $defineId
     * @param Net $net
     * @CreateTime 18/3/27 14:07:53
     * @Author: fangxing@likingfit.com
     */
    public static function cacheNet($defineId, Net $net)
    {
        \Yii::$app->redis->set($defineId, serialize($net));
    }

    /**
     * 获取缓存流程图
     *
     * @param $defineId
     * @return Net
     * @CreateTime 18/3/27 14:07:32
     * @Author: fangxing@likingfit.com
     */
    public static function getCacheNet($defineId)
    {
        return unserialize(\Yii::$app->redis->get($defineId));
    }
}