<?php
namespace likingfit\Workflow;
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午4:54
 */
namespace likingfit\Workflow;

use likingfit\Workflow\Base\WorkItem;
use likingfit\Workflow\Util\ConditionService;
use likingfit\Workflow\Parser\Parser;
use likingfit\Workflow\Base\Process;
use likingfit\Workflow\Util\EventService;
use likingfit\Workflow\Util\LockService;
use likingfit\Workflow\Util\PersistenceService;

class Workflow {
    /** @var PersistenceService */
    private static $PersistenceService = null;
    /** @var LockService */
    private static $LockService = null;
    private static $LogService = null;
    private static $ConditionService = null;
    /** @var EventService  */
    private static $EventService = null;
    private static $processMap = array();

    /**
     * 获取存取服务
     *
     * @return PersistenceService|object
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:04:47
     * @Author: fangxing@likingfit.com
     */
    public static function getPersistenceService(){
        $dbXml = 'app\services\WorkflowPersistenceService';  //举个例子, 该信息从xml配置文件拿取
        if(empty(self::$PersistenceService)){
            $reflec = new \ReflectionClass($dbXml);
            self::$PersistenceService = $reflec->newInstance();
        }
        return self::$PersistenceService;
    }
    
    /**
     * 获取条件判断服务  //ps: Workflow::getConditionService()->resolve("a+1&gt;=3 &amp;&amp; a>1",['a' => 2]);
     */
    public static function getConditionService(){
        if(empty(self::$ConditionService)){
            self::$ConditionService = new ConditionService();
        }
        return self::$ConditionService;
    }
    
    /**
     * 获取锁服务
     */
    public static function getLockService(){
        $dbXml = 'app\services\WorkLockService';
        if(empty(self::$LockService)){
            $reflec = new \ReflectionClass($dbXml);
            self::$LockService = $reflec->newInstance();
        }
        return self::$LockService;
    }
    
    /**
     * 获取日志服务
     */
    public static function getLogService(){
        $logXml = 'app\services\WorkLogService';
        if(empty(self::$LogService)){
            $reflec = new \ReflectionClass($logXml);
            self::$LogService = $reflec->newInstance();
        }
        return self::$LogService;
    }
    /**
     *
     */
    public static function getEventService(){
        $name = 'app\services\WorkEventService';
        if(empty(self::$EventService)){
            $reflec = new \ReflectionClass($name);
            self::$EventService = $reflec->newInstance();
        }
        return self::$EventService;
    }

    /**
     * 创建流程实例对象
     *
     * @param $defineId
     * @param $creatorId
     * @return Process
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:05:40
     * @Author: fangxing@likingfit.com
     */
    public static function createProcess($defineId, $creatorId){
        $persistenceService = self::getPersistenceService();
        $define = $persistenceService->getProcessDef($defineId);
        $path = \Yii::getAlias('@app').$define->PROCESS_PATH;
        $template = self::parse($path);
        $net = $template->initNet();
        $persistenceService->cacheNet($defineId, $net); //缓存流程图
        $process = new Process();
        $process->setDefineId($defineId);
        $process->setVersion($define->VERSION);
        $process->setState(Process::INITIALIZED);
        $process->setSuspended(false);
        $process->setCreatorId($creatorId);
        $process->setCreatedTime(date('Y-m-d H:i:s'));
        $pid = $persistenceService->saveProcess($process);  //持久化

        $process->setId($pid);
        $process->setVariables($template->getDataFields());
        $process->setNet($net);
        self::$processMap[$process->getId()] = $process;
        return $process;
    }

    /**
     * 创建子流程实例对象
     *
     * @param $subflowDefineId
     * @param $creatorId
     * @param $parentProcessId
     * @param $parentTaskId
     * @return Process
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:09:25
     * @Author: fangxing@likingfit.com
     */
    public static function createSubflowProcess($subflowDefineId,$creatorId,$parentProcessId,$parentTaskId){
        $persistenceService = self::getPersistenceService();
        $parent = Workflow::getProcess($parentProcessId);
        $define = $persistenceService->getProcessDef($subflowDefineId, $parent->getVersion());
        $path = \Yii::getAlias('@app').$define->PROCESS_PATH;
        $template = self::parse($path);
        $net = $template->initNet();
        $persistenceService->cacheNet($subflowDefineId, $net);
        $process = new Process();
        $process->setDefineId($subflowDefineId);
        $process->setVersion($define->VERSION);
        $process->setState(Process::INITIALIZED);
        $process->setSuspended(false);
        $process->setCreatorId($creatorId);
        $process->setCreatedTime(date('Y-m-d H:i:s'));
        $process->setParentProcessId($parentProcessId);
        $process->setParentTaskId($parentTaskId);
        $pid = $persistenceService->saveProcess($process);  //持久化
        $process->setId($pid);
        $process->setVariables($template->getDataFields());
        $process->setNet($net);
        self::$processMap[$process->getId()] = $process;
        return $process;
    }


    /**
     * 获取流程实例对象
     *
     * @param $processId
     * @return Process|mixed
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:13:07
     * @Author: fangxing@likingfit.com
     */
    public static function getProcess($processId){
        if(isset(self::$processMap[$processId])){
            return self::$processMap[$processId];
        }
        $persistenceService = self::getPersistenceService();
        $process = $persistenceService->getProcess($processId);
        $defineId = $process->getDefineId();
        if(!($net = $persistenceService->getCacheNet($defineId))){
            $define = $persistenceService->getProcessDef($defineId, $process->getVersion());
            $path = \Yii::getAlias('@app').$define->PROCESS_PATH;
            $template = self::parse($path);
            $net = $template->initNet();
            $persistenceService->cacheNet($defineId, $net);
        }
        $process->setNet($net);
        self::$processMap[$process->getId()] = $process;
        return $process;
    }
    
    /**
     * 获取用户所有工作事项
     *
     * @param $userId
     *
     * @return WorkItem[]
     */
    public static function getUserWorkItem($userId){
        $persistenceService = Workflow::getPersistenceService();
        return $persistenceService->getUserWorkItem($userId);
    }
    public static function parse($path) {
      return Parser::parse($path);
    }
    
    /**
     * 获取工作项所属processId
     * @param $workItemId
     *
     * @return integer
     */
    public static function getWorkItemProcessId($workItemId){
        $persistenceService = Workflow::getPersistenceService();
        $workItem = $persistenceService->getWorkItem($workItemId);
        $taskInstance = $persistenceService->getTask($workItem->getTaskId());
        return $taskInstance->getProcessId();
    }
    
    /**
     * 设置工作项所属的流程实例参数值
     * @param string $processId
     * @param string $name
     * @param string|int $value
     */
    public static function setVariableByWorkItemId($processId, $name, $value){
    	$process = self::getProcess($processId);
    	$process->setVariable($name,$value);
    }
    
    public static function getProcessDef($defineId, $version = 1){
        $persistService = Workflow::getPersistenceService();
        $define = $persistService->getProcessDef($defineId, $version);
        return $define;
    }
    
    /**
     * 获取datefile
     * @param string $processId
     * @param string $name
     *
     * @return unknown
     */
    public static function getVariableByName($processId,$name){
        $process = self::getProcess($processId);
        return $process->getVariable($name);
    }
    
    /**
     * @param $name
     * @param $value
     */
    public static function searchProcessIdByVariable($name, $value){
        $persistService = Workflow::getPersistenceService();
        $processIds = $persistService->getProcessIdByVariable($name, $value);
        return $processIds;
    }
}
