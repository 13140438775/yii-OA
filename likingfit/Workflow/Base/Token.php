<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:19
 */
namespace likingfit\Workflow\Base;
class Token extends Object {
    private $processId;
    private $process;
    private $alive = true;
    private $value = 0;
    private $step = 0;
    private $nodeId = 0;
    private $fromActivityId = 0;
    public function getProcessId(){
        return $this->processId;
    }
    public function setProcessId($processId){
        $this->processId = $processId;
    }
    public function getProcess(){
        return $this->process;
    }
    public function setProcess($process){
        $this->process = $process;
    }
    public function isAlive(){
        return $this->alive;
    }
    public function setAlive($alive){
        $this->alive = $alive;
    }
    public function getValue(){
        return $this->value;
    }
    public function setValue($value){
        $this->value = $value;
    }
    public function getStep(){
        return $this->step;
    }
    public function setStep($step){
        $this->step = $step;
    }
    public function getNodeId(){
        return $this->nodeId;
    }
    public function setNodeId($nodeId){
        $this->nodeId = $nodeId;
    }
    public function getFromActivityId(){
        return $this->fromActivityId;
    }
    public function setFromActivityId($fromActivityId){
        $this->fromActivityId = $fromActivityId;
    }
}