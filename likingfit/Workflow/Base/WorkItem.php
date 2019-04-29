<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/19
 * Time: 下午3:10
 */
namespace likingfit\Workflow\Base;

class WorkItem extends Object {
    const INITIALIZED = 1;
    const CLAIMED = 2;
    const COMPLETED = 3;
    const CANCELED = 4;
    
    private $actorId;
    private $state;
    private $taskId;
    private $createTime;
    private $claimTime;
    private $endTime;
   
    public function claim(){
        $this->state = self::CLAIMED;
        $this->claimTime = date('Y-m-d H:i:s');
        return;
    }
    public function complete(){
        $this->state = self::COMPLETED;
        $this->endTime = date('Y-m-d H:i:s');
        return;
    }
    public function getActorId(){
        return $this->actorId;
    }
    public function setActorId($actorId){
        $this->actorId = $actorId;
    }
    public function getState(){
        return $this->state;
    }
    public function setState($state){
        $this->state = $state;
    }
    
    /**
     * @return mixed
     */
    public function getCreateTime(){
        return $this->createTime;
    }
    
    /**
     * @param mixed $createTime
     */
    public function setCreateTime($createTime){
        $this->createTime = $createTime;
    }
    
    /**
     * @return mixed
     */
    public function getClaimTime(){
        return $this->claimTime;
    }
    
    /**
     * @param mixed $claimTime
     */
    public function setClaimTime($claimTime){
        $this->claimTime = $claimTime;
    }
    
    /**
     * @return mixed
     */
    public function getEndTime(){
        return $this->endTime;
    }
    
    /**
     * @param mixed $endTime
     */
    public function setEndTime($endTime){
        $this->endTime = $endTime;
    }
    public function getTaskId(){
        return $this->taskId;
    }
    public function setTaskId($taskId){
        $this->taskId = $taskId;
    }
    public function get($name){
        if(isset($this->$name)){
            return $this->$name;
        }else{
            return "";
        }
    }
    public function set($name,$value){
        $this->$name = $value;
    }
    
}