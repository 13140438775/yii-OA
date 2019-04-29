<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:18
 */
namespace likingfit\Workflow\Base;
use likingfit\Events\ActivityEvent;
use likingfit\Workflow\Workflow;
use yii\base\Event;

/**
 * Class Activity
 * @package likingfit\Workflow\Base
 */
class Activity extends Node {

    const EVENT_BEFORE_START = "ActivityBeforeStart";

    const EVENT_AFTER_START = "ActivityAfterStart";

    const EVENT_BEFORE_COMPLETE = "ActivityBeforeComplete";

    const EVENT_AFTER_COMPLETE = "ActivityAfterComplete";

    /**
     * @var Task[]
     */
    private $tasks = array();
    private $dependency = '';
    
    /**
     * @return string $dependency
     */
    public function getDependency() {
        return $this->dependency;
    }

	/**
     * @param string $dependency
     */
    public function setDependency($dependency) {
        $this->dependency = $dependency;
    }

	public function fire(Token $token){
        $processId = $token->getProcessId();
        Workflow::getLogService()->log($processId.'-'.$this->getId().':Activity:fire token');
        //持久化token
        $token->setNodeId($this->getId());
        $persistenceService = Workflow::getPersistenceService();
        $id = $persistenceService->saveToken($token);
        $token->setId($id);

        $event = new ActivityEvent;
        $event->token = $token;
        Event::trigger($this, self::EVENT_BEFORE_START, $event);
        if(!$event->is_valid){
            return;
        }

        if($token->isAlive() && count($this->tasks)){
            //生成task
            Workflow::getLogService()->log($processId.'-'.$this->getId().':Activity:generate task');
            $process = Workflow::getProcess($token->getProcessId());
            $process->createActivityTask($this, $token);

            Event::trigger($this, self::EVENT_AFTER_START);
        }else{
            //死亡token/没有task 直接完成Activity
            $this->complete($token);
        }
    }
    
    public function complete(Token $token){
        $processId = $token->getProcessId();
        Workflow::getLogService()->log($processId.'-'.$this->getId().':Activity:complete');
        $process = Workflow::getProcess($token->getProcessId());

        $event = new ActivityEvent;
        $event->token = $token;
        Event::trigger($this, self::EVENT_BEFORE_COMPLETE, $event);

        //判断条件
        $taskList = $process->getActivityTask($this->getId());
        foreach($taskList AS $task){
            //有未完成/未取消任务
            if($task->getState() !== TaskInstance::COMPLETED && $task->getState() !== TaskInstance::CANCELED){
                Workflow::getLogService()->log($processId.'-'.$this->getId().":Wait All Task Complete");
                return;
            }
        }
        $token->setFromActivityId($this->getId());

        Event::trigger($this, self::EVENT_AFTER_COMPLETE, $event);

        //Activity只能由一个输出
        $this->nextEdges[0]->take($token);
        return;
    }
    
    public function getTasks(){
        return $this->tasks;
    }
    
    public function addTasks($task){
        array_push($this->tasks, $task);
    }
    
    public function setTasks($tasks) {
        $this->tasks = $tasks;
    }
}