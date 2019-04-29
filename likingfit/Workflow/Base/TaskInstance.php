<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/20
 * Time: 下午6:49
 */

namespace likingfit\Workflow\Base;


class TaskInstance extends Object
{
    const INITIALIZED = 1;

    const RUNNING = 2;

    const COMPLETED = 3;

    const CANCELED = 4;

    const EVENT_BEFORE_START = "TaskBeforeStart";

    const EVENT_AFTER_START = "TaskAfterStart";

    const EVENT_BEFORE_COMPLETE = "TaskBeforeComplete";

    const EVENT_AFTER_COMPLETE = "TaskAfterComplete";

    private $taskId;
    private $processId;
    private $activityId;
    private $state;
    private $type;
    private $suspended = false;
    private $creator;
    private $runner;
    private $completionEvaluator;
    private $params = array();
    private $createTime;
    private $startedTime;
    private $expiredTime;
    private $endTime;
    private $defineId;  //模版文件ID
    private $version;
    private $step;
    private $canCancel = 0;

    public function __construct()
    {
    }

    public function init(Task $task)
    {
        $this->type = $task->getType();
        $this->params = $task->getParams();
        $this->taskId = $task->getId();
        $this->activityId = $task->getActivityId();
        $this->name = $task->getName();
        $this->displayName = $task->getDisplayName();
        $this->description = $task->getDescription();
        $creator = $task->getCreator();
        if (!empty($creator)) {
            $this->creator = (new \ReflectionClass($task->getCreator()))->newInstance();
        }
        $runner = $task->getRunner();
        if (!empty($runner)) {
            $this->runner = (new \ReflectionClass($task->getRunner()))->newInstance();
        }
        $completionEvaluator = $task->getCompletionEvaluator();
        if (!empty($completionEvaluator)) {
            $this->completionEvaluator = (new \ReflectionClass($task->getCompletionEvaluator()))->newInstance();
        }
        $this->createTime = date('Y-m-d H:i:s');
        return $this;
    }

    public function start()
    {
        $this->state = self::RUNNING;
        $this->startedTime = date('Y-m-d H:i:s');
        return;
    }

    public function complete()
    {
        $this->state = self::COMPLETED;
        $this->endTime = date('Y-m-d H:i:s');
        return true;
    }

    public function abort()
    {
        $this->state = self::CANCELED;
        return true;
    }

    public function suspend()
    {
        $this->setSuspended(true);
        return true;
    }

    public function restore()
    {
        $this->setSuspended(false);
        return true;
    }

    /**
     * @return mixed
     */
    public function getTaskId()
    {
        return $this->taskId;
    }

    /**
     * @param mixed $taskId
     */
    public function setTaskId($taskId)
    {
        $this->taskId = $taskId;
    }

    /**
     * @return mixed
     */
    public function getProcessId()
    {
        return $this->processId;
    }

    /**
     * @param mixed $processId
     */
    public function setProcessId($processId)
    {
        $this->processId = $processId;
    }

    /**
     * @return mixed
     */
    public function getActivityId()
    {
        return $this->activityId;
    }

    /**
     * @param mixed $activityId
     */
    public function setActivityId($activityId)
    {
        $this->activityId = $activityId;
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
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param int $type
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return bool
     */
    public function isSuspended()
    {
        return $this->suspended;
    }

    /**
     * @param bool $suspended
     */
    public function setSuspended($suspended)
    {
        $this->suspended = $suspended;
    }

    /**
     * @return object
     */
    public function getCreator()
    {
        return $this->creator;
    }

    /**
     * @param object $creator
     */
    public function setCreator($creator)
    {
        $this->creator = $creator;
    }

    /**
     * @return object
     */
    public function getRunner()
    {
        return $this->runner;
    }

    /**
     * @param object $runner
     */
    public function setRunner($runner)
    {
        $this->runner = $runner;
    }

    /**
     * @return object
     */
    public function getCompletionEvaluator()
    {
        return $this->completionEvaluator;
    }

    /**
     * @param object $completionEvaluator
     */
    public function setCompletionEvaluator($completionEvaluator)
    {
        $this->completionEvaluator = $completionEvaluator;
    }

    /**
     * @return array
     */
    public function getParams()
    {
        return $this->params;
    }

    /**
     * @param array $params
     */
    public function setParams($params)
    {
        $this->params = $params;
    }

    /**
     * @return false|string
     */
    public function getCreateTime()
    {
        return $this->createTime;
    }

    /**
     * @param false|string $createTime
     */
    public function setCreateTime($createTime)
    {
        $this->createTime = $createTime;
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
    public function getStep()
    {
        return $this->step;
    }

    /**
     * @param mixed $step
     */
    public function setStep($step)
    {
        $this->step = $step;
    }

    /**
     * @return mixed
     */
    public function getCanCancel()
    {
        return $this->canCancel;
    }

    /**
     * @param mixed $canCancel
     */
    public function setCanCancel($canCancel)
    {
        $this->canCancel = $canCancel;
    }
}