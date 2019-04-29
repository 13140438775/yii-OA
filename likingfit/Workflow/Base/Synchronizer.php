<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:20
 */
namespace likingfit\Workflow\Base;
use likingfit\Workflow\Workflow;

class Synchronizer extends Node {
    private $start = false;
    private $end = false;
    private $volume = 0;
    public function fire(Token $token){
        $processId = $token->getProcessId();
        Workflow::getLogService()->log($processId.'-'.$this->getId().':Synchronizer:fire token');
        $persistenceService = Workflow::getPersistenceService();
        $lock = Workflow::getLockService();
        $lockName = $processId.'-'.$this->getId();
        $lock->lock($lockName);
        //使用锁保证token一个一个进入同步器
        try{
            //锁内持久化token
            $token->setNodeId($this->getId());
            $id = $persistenceService->saveToken($token);
            $token->setId($id);
            $joinPoint = $this->getJoinPoint($processId);
            $lock->unlock($lockName);
            if($joinPoint['value'] < $this->getVolume()){
                //尚有未到达token
                Workflow::getLogService()->log($processId.'-'.$this->getId().":Wait All Token Arrive");
                return;
            }
        }catch(\Exception $e){
            $lock->unlock($lockName);
            throw $e;
        }
        //删除当前同步器所有持有token
        $persistenceService->delProcessToken($processId, $this->getId());
        if(!$this->end){
            //非结束节点要向下流转
            $doLoop = false;
            //首先尝试Loop
            if($joinPoint['alive']){
                foreach($this->getNextLoop() AS $loop){
                    $token = new Token();
                    $token->setAlive($joinPoint['alive']);
                    $token->setProcessId($processId);
                    $token->setStep($joinPoint['step'] + 1);
                    $doLoop = $loop->take($token);
                }
            }
            //Loop失败则尝试Transition
            if(!$doLoop){
                foreach($this->getNextTransition() AS $transition){
                    $token = new Token();
                    $token->setAlive($joinPoint['alive']);
                    $token->setProcessId($processId);
                    $token->setStep($joinPoint['step'] + 1);
                    $transition->take($token);
                }
            }
        }else{
            //结束节点 完成Process
            $process = Workflow::getProcess($processId);
            $process->complete();
        }
    }
    
    /**
     * 统计已到达Token,获取汇聚对象
     * @return array
     */
    public function getJoinPoint($processId){
        $joinPoint = [
            'value' => 0,
            'step' => 0,
            'alive' => false
        ];
        $arriveFrom = array();
        $persistenceService = Workflow::getPersistenceService();
        $tokenList = $persistenceService->getProcessToken($processId, $this->getId());
        foreach($tokenList AS $tk){
            /** @var $tk Token */
            $fromActivity = $tk->getFromActivityId();
            $joinPoint['step'] = max($joinPoint['step'], $tk->getStep());
            if(!isset($arriveFrom[$fromActivity])){
                $arriveFrom[$fromActivity] = true;
                $joinPoint['value'] += $tk->getValue();
            }
            if($tk->isAlive()){
                $joinPoint['alive'] = true;
            }
        }
        return $joinPoint;
    }
    public function isStart(){
        return $this->start;
    }
    public function setStart($start){
        $this->start = $start;
    }
    public function isEnd(){
        return $this->end;
    }
    public function setEnd($end){
        $this->end = $end;
    }
    public function getVolume(){
        if(!$this->volume){
            if($this->isStart() || $this->isEnd()){
                $this->volume = 1;
            }else{
                $this->volume = count($this->getPrevTransition()) * count($this->getNextTransition());
            }
        }
        return $this->volume;
    }
    public function setVolume($volume){
        
    }
}