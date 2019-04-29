<?php
namespace likingfit\Workflow\Util;

use likingfit\Workflow\Base\Net;
use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Base\Token;
use likingfit\Workflow\Base\WorkItem;
use likingfit\Workflow\Base\Process;

interface PersistenceService{
    /**
     * 获取token信息
     * @param array $conditions
     * @return Token[]
     */
    public static function getTokens($conditions);
    
    /**
     * 设置token
     * @param Token $token
     */
    public static function saveToken(Token $token);
    
    /**
     * 删除token
     * @param int $tokenId
     * @return boolean
     */
    public static function delToken($tokenId);
    
    /**
     * 获取流程ID下某节点所有token
     * @param      $processId
     * @param null $nodeId
     *
     * @return Token[]
     */
    public static function getProcessToken($processId, $nodeId = null);
    
    /**
     * 删除流程ID下某节点所有token
     * @param      $processId
     * @param null $nodeId
     *
     * @return boolean
     */
    public static function delProcessToken($processId, $nodeId=null);
    
    /**
     * @param TaskInstance $task
     *
     * @return mixed
     */
    public static function saveTask(TaskInstance $task);
    
    /**
     * @param $taskId
     *
     * @return TaskInstance
     */
    public static function getTask($taskId);
    
    /**
     * @param      $processId
     * @param null $nodeId
     *
     * @return TaskInstance[]
     */
    public static function getProcessTask($processId, $nodeId=null);
    
    public static function saveWorkItem(WorkItem $workItem);
    
    /**
     * @param $workItemId
     *
     * @return WorkItem
     */
    public static function getWorkItem($workItemId);
    
    public static function saveProcess(Process $process);
    
    /**
     * @param $processId
     *
     * @return Process
     */
    public static function getProcess($processId);
    
    /**
     * @param $parentProcessId
     * @param $activityId
     *
     * @return Process
     */
    public static function getActivitySubProcess($parentProcessId, $activityId);
    
    public static function setVariable($processId,$name,$value);
    
    public static function getVariables($processId);
    
    public static function abortProcess($processId);
    
    public static function getProcessDef($processId, $version = 1);

    public static function cacheNet($defineId, Net $net);

    public static function getCacheNet($defineId);
}