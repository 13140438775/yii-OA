<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/2/6
 * Time: 上午10:51
 */
namespace likingfit\Workflow\Base;

/**
 * Class Graph
 * 流程图类
 * @package likingfit\Workflow\Base
 */
class Graph {
    CONST COMPLETE = 1;
    CONST PENDING = 2;
    CONST NOT_START = 3;
    
    private $start;
    //nodeId => id 映射
    private $idMap = array();
    private $elementMap = array();
    
    /**
     * 通过一个Net生成Graph
     * Graph constructor.
     *
     * @param Net $net
     */
    public function __construct(Net $net){
        $id = 1;
        $startNode = $net->getStart();
        $startNodeId = $startNode->getId();
        $start = [
            'id' => $id,
            'node_id' => $startNodeId,
            'name' => 'Start',
            'prev' => [],
            'next' => array(),
            'status' => true,
            'type' => 0,
            'start' => true,
            'end' => false,
            'param' => []
        ];
        $this->idMap[$startNodeId] = $id;
        $id++;
        $this->elementMap[$start['id']] = $start;
        $end = [
            'id' => 0,
            'node_id' => '',
            'name' => 'End',
            'prev' => array(),
            'next' => [],
            'status' => self::NOT_START,
            'type' => 0,
            'start' => false,
            'end' => true,
            'param' => []
        ];
        $queue = array();
        array_push($queue, $start['node_id']);
        while(count($queue)){
            $graphNodeId = array_shift($queue);
            $graphNode = $this->elementMap[$this->idMap[$graphNodeId]];
            /** @var $node Node **/
            $node = $net->getElement($graphNode['node_id']);
            $nextNodes = $node->getNextEffectActivityForGraph();
            foreach($nextNodes AS $nextNode){
                $nextNodeId = $nextNode->getId();
                if(!isset($this->idMap[$nextNodeId])){
                    $task = $nextNode->getTasks()[0];
                    //未生成的点,并进入队列
                    $param = array();
                    $type = $task->getType();
                    if($type == Task::SUBFLOW){
//                        $param['process_id'] = 0;
                    }else if($type == Task::FORM){
//                        $param['page'] = $task->getParams()['form']['uri'];
                    }
                    $nextGraphNode = [
                        'id' => $id,
                        'node_id' => $nextNode->getId(),
                        'name' => $nextNode->getDisplayName(),
                        'prev' => array(),
                        'next' => array(),
                        'status' => self::NOT_START,
                        'type' => $type,
                        'start' => false,
                        'end' => false,
                        'param' => $param
                    ];
                    $this->idMap[$nextNodeId] = $id;
                    $id++;
                    array_push($queue, $nextGraphNode['node_id']);
                }else{
                    //已经生成的点
                    $nextGraphNode = $this->elementMap[$this->idMap[$nextNodeId]];
                }
                //把当前点插入下一点的prev数组
                array_push($nextGraphNode['prev'], $graphNode['id']);
                $this->elementMap[$nextGraphNode['id']] = $nextGraphNode;
                //把下一点插入当前点的next数组
                array_push($graphNode['next'], $nextGraphNode['id']);
            }
            if(empty($graphNode['next'])){
                $graphNode['next'] = [$end['id']];
                array_push($end['prev'], $graphNode['id']);
            }
            $this->elementMap[$graphNode['id']] = $graphNode;
        }
        $this->elementMap[0] = $end;
        $this->start = $this->elementMap[1];
        return;
    }
    
    /**
     * 根据token停留的Node更新每个节点状态
     * @param Process $process
     * @param Node[] $nodes
     * @return self
     */
    public function updateStatus($process, $nodes){
        if($process->getState() == Process::COMPLETED){
            //流程已完成
            foreach($this->elementMap AS $nodeId => $graphNode){
                $this->elementMap[$nodeId]['status'] = self::COMPLETE;
            }
            return $this;
        }
        //$persistService = Workflow::getPersistenceService();
        $stopIds = array();
        $prevQueue = array();
        $nextQueue = array();
        foreach($nodes as $node){
            if($node instanceof Synchronizer){
                //停留在Synchronizer说明在等待汇聚
                $nextActivity = $node->getNextEffectActivityForGraph();
                $prevActivity = $node->getPrevEffectActivityForGraph();
                foreach($nextActivity AS $activity){
                    $nextQueue[] = $this->idMap[$activity->getId()];
                }
                foreach($prevActivity AS $activity){
                    $prevQueue[] = $this->idMap[$activity->getId()];
                }
            }else{
                //停留在Activity说明在等待操作完成
                $stopIds[] = $this->idMap[$node->getId()];
            }
        }
        //先将停止位置之前全部置为已完成
        foreach($stopIds AS $stopId){
            $stopGraphNode = $this->elementMap[$stopId];
            $prevQueue = array_merge($prevQueue, $stopGraphNode['prev']);
        }
        while(count($prevQueue)){
            $id = array_shift($prevQueue);
            $this->elementMap[$id]['status'] = self::COMPLETE;
            if($this->elementMap[$id]['type'] == Task::SUBFLOW){
//                $this->elementMap[$id]['param']['process_id'] = $persistService->getActivitySubProcessId($processId, $this->elementMap[$id]['node_id'])['ID'];
            }
            $graphNode = $this->elementMap[$id];
            if($graphNode['prev']){
                $prevQueue = array_merge($prevQueue, $graphNode['prev']);
            }
        }
        //再将停止位置之后全部置为未到达
        foreach($stopIds AS $stopId){
            $stopGraphNode = $this->elementMap[$stopId];
            $nextQueue = array_merge($nextQueue, $stopGraphNode['next']);
        }
        while(count($nextQueue)){
            $id = array_shift($nextQueue);
            $this->elementMap[$id]['status'] = self::NOT_START;
            $graphNode = $this->elementMap[$id];
            if($graphNode['prev']){
                $nextQueue = array_merge($nextQueue, $graphNode['next']);
            }
        }
        //最后将停止位置置为等待操作
        foreach($stopIds as $stopId){
            $this->elementMap[$stopId]['status'] = self::PENDING;
            if($this->elementMap[$stopId]['type'] == Task::SUBFLOW){
//                $this->elementMap[$stopId]['param']['process_id'] = $persistService->getActivitySubProcessId($processId, $this->elementMap[$stopId]['node_id'])['ID'];
            }
        }
        return $this;
    }
    /**
     * 链表类型数据转化为点和边两个数组
     * @return array
     */
    public function toArray(){
        $graphNodes = array();
        $edges = array();
        foreach($this->elementMap AS $nodeId => $graphNode){
            $graphNodes[] = [
                'id' => $graphNode['id'],
                'node_id' => $graphNode['node_id'],
                'name' => $graphNode['name'],
                'status' => $graphNode['status'],
                'type' => $graphNode['type'],
                'start' => $graphNode['start'],
                'end' => $graphNode['end'],
                'param' => $graphNode['param']
            ];
            foreach($graphNode['next'] AS $nextNodeId){
                $edges[] = [
                    'from' => $graphNode['id'],
                    'to' => $nextNodeId
                ];
            }
        }
        return [
            $graphNodes,
            $edges
        ];
    }
    
    /**
     * @param $elementId
     *
     * @return Element
     */
    public function getElement($elementId){
        return $this->elementMap[$elementId];
    }
    public function addElement(Element $element){
        $this->elementMap[$element->getId()] = $element;
    }
    
    
    public function getStart() {
        return $this->start;
    }
    
    public function setStart($startNode) {
        $this->start = $startNode;
    }
    /**
     * @return array $elementMap
     */
    public function getElementMap() {
        return $this->elementMap;
    }
    
    /**
     * @param multitype: $elementMap
     */
    public function setElementMap($elementMap) {
        $this->elementMap = $elementMap;
    }
}