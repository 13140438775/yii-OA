<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:14
 */
namespace likingfit\Workflow\Base;
/**
 * Class Edge
 * @package likingfit\Workflow\Base
 * @property  \likingfit\Workflow\Base\Node $prevNode
 * @property  \likingfit\Workflow\Base\Node $nextNode
 */
abstract class Edge extends Element {
    /**
     * @var $prevNode Node
     */
    protected $prevNode;
    /**
     * @var $nextNode Node
     */
    protected $nextNode;
    protected $condition;
    protected $weight;
    protected $fromId;
    protected $toId;
    /**
     * @return string $fromId
     */
    public function getFromId() {
        return $this->fromId;
    }

	/**
     * @return string $toId
     */
    public function getToId() {
        return $this->toId;
    }

	/**
     * @param $fromId
     */
    public function setFromId($fromId) {
        $this->fromId = $fromId;
    }

	/**
     * @param $toId
     */
    public function setToId($toId) {
        $this->toId = $toId;
    }

	abstract public function take(Token $token);
    abstract public function getWeight();
    public function getPrevNode(){
        return $this->prevNode;
    }
    public function setPrevNode(Node $node){
        $this->prevNode = $node;
    }
    public function getNextNode(){
        return $this->nextNode;
    }
    public function setNextNode(Node $node){
        $this->nextNode = $node;
    }
    public function getCondition(){
        return $this->condition;
    }
    public function setCondition($condition){
        $this->condition = $condition;
    }
}