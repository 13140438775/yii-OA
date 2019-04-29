<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:25
 */
namespace likingfit\Workflow\Base;
use likingfit\Workflow\Workflow;

class Transition extends Edge {
    public function getWeight(){
        if(!$this->weight){
            //前驱节点为同步器
            if($this->prevNode instanceof Synchronizer){
                $prevNode = $this->prevNode;
                if($prevNode->isStart()){
                    //前驱为开始节点
                    $this->weight = 1;
                }else{
                    //前驱为一般同步器
                    $this->weight = $prevNode->getVolume() / count($prevNode->getNextTransition());
                }
            }
            //后继节点为同步器
            if($this->nextNode instanceof Synchronizer){
                $nextNode = $this->nextNode;
                if($nextNode->isEnd()){
                    //后继为结束节点
                    $this->weight = 1;
                }else{
                    //后继为一般同步器
                    $this->weight = $nextNode->getVolume() / count($nextNode->getPrevTransition());
                }
            }
        }
        return $this->weight;
    }
    
    public function take(Token $token){
        $processId = $token->getProcessId();
        $process = Workflow::getProcess($processId);
        Workflow::getLogService()->log($processId.'-'.$this->getId().':Transition:take token');
        //持久化token
        $token->setValue($this->getWeight());
        $persistenceService = Workflow::getPersistenceService();
        $id = $persistenceService->saveToken($token);
        $token->setId($id);
        if($token->isAlive()){
            //计算condition
            $conditionService = Workflow::getConditionService();
            $param = $process->getVariables();
            $result = $conditionService->resolve($this->condition, $param);
            $token->setAlive($result);
        }
        $alive = $token->isAlive();
        $this->nextNode->fire($token);
        //返回进过此边时token的状态
        return $alive;
    }
}