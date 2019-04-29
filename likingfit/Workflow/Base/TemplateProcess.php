<?php

namespace likingfit\Workflow\Base;

use likingfit\Workflow\Parser\EntityNames;
use likingfit\workflow\Exception\ParseException;

class TemplateProcess extends Object{
    private $net;
    private $dataFields = array();
    private $tasks = array();
    private $activities = array();
    private $transitions = array();
    private $synchronizers = array();
    private $loops = array();
    private $startNode = null;
    private $endNodes = array();
    protected  $taskInstanceCreator = '';
    protected  $formTaskInstanceRunner = '';
    protected  $toolTaskInstanceRunner = '';
    protected  $subflowTaskInstanceRunner = '';
    protected  $formTaskInstanceCompletionEvaluator = '';
    protected  $toolTaskInstanceCompletionEvaluator = '';
    protected  $subflowTaskInstanceCompletionEvaluator = '';
	/**
     * @return Loop[] $loops
     */
    public function getLoops() {
        return $this->loops;
    }

	/**
     * @param multitype: $loops
     */
    public function setLoops($loops) {
        $this->loops = $loops;
    }

	/**
     * @return Net $net
     */
    public function getNet() {
        return $this->net;
    }

	/**
     * @param Net $net
     */
    public function setNet($net) {
        $this->net = $net;
    }

	/**
     * @return DataField[] $dataFields
     */
    public function getDataFields() {
        return $this->dataFields;
    }

	/**
     * @return Task[] $tasks
     */
    public function getTasks() {
        return $this->tasks;
    }

	/**
     * @return Activity[] $activities
     */
    public function getActivities() {
        return $this->activities;
    }

	/**
     * @return Transition[] $transitions
     */
    public function getTransitions() {
        return $this->transitions;
    }

	/**
     * @return Synchronizer[] $synchronizers
     */
    public function getSynchronizers() {
        return $this->synchronizers;
    }

	/**
     * @return Synchronizer $startNode
     */
    public function getStartNode() {
        return $this->startNode;
    }

	/**
     * @return Synchronizer[] $endNodes
     */
    public function getEndNodes() {
        return $this->endNodes;
    }

	/**
     * @return string $taskInstanceCreator
     */
    public function getTaskInstanceCreator() {
        return $this->taskInstanceCreator;
    }

	/**
     * @return string $formTaskInstanceRunner
     */
    public function getFormTaskInstanceRunner() {
        return $this->formTaskInstanceRunner;
    }

	/**
     * @return string $toolTaskInstanceRunner
     */
    public function getToolTaskInstanceRunner() {
        return $this->toolTaskInstanceRunner;
    }

	/**
     * @return string $subflowTaskInstanceRunner
     */
    public function getSubflowTaskInstanceRunner() {
        return $this->subflowTaskInstanceRunner;
    }

	/**
     * @return string $formTaskInstanceCompletionEvaluator
     */
    public function getFormTaskInstanceCompletionEvaluator() {
        return $this->formTaskInstanceCompletionEvaluator;
    }

	/**
     * @return string $toolTaskInstanceCompletionEvaluator
     */
    public function getToolTaskInstanceCompletionEvaluator() {
        return $this->toolTaskInstanceCompletionEvaluator;
    }

	/**
     * @return string $subflowTaskInstanceCompletionEvaluator
     */
    public function getSubflowTaskInstanceCompletionEvaluator() {
        return $this->subflowTaskInstanceCompletionEvaluator;
    }

	/**
     * @param multitype: $dataFields
     */
    public function setDataFields($dataFields) {
        $this->dataFields = $dataFields;
    }

	/**
     * @param multitype: $tasks
     */
    public function setTasks($tasks) {
        $this->tasks = $tasks;
    }

	/**
     * @param multitype: $activities
     */
    public function setActivities($activities) {
        $this->activities = $activities;
    }

	/**
     * @param multitype: $transitions
     */
    public function setTransitions($transitions) {
        $this->transitions = $transitions;
    }

	/**
     * @param multitype: $synchronizers
     */
    public function setSynchronizers($synchronizers) {
        $this->synchronizers = $synchronizers;
    }

	/**
     * @param string $startNode
     */
    public function setStartNode($startNode) {
        $this->startNode = $startNode;
    }

	/**
     * @param multitype: $endNodes
     */
    public function setEndNodes($endNodes) {
        $this->endNodes = $endNodes;
    }

	/**
     * @param string $taskInstanceCreator
     */
    public function setTaskInstanceCreator($taskInstanceCreator) {
        $this->taskInstanceCreator = $taskInstanceCreator;
    }

	/**
     * @param string $formTaskInstanceRunner
     */
    public function setFormTaskInstanceRunner($formTaskInstanceRunner) {
        $this->formTaskInstanceRunner = $formTaskInstanceRunner;
    }

	/**
     * @param string $toolTaskInstanceRunner
     */
    public function setToolTaskInstanceRunner($toolTaskInstanceRunner) {
        $this->toolTaskInstanceRunner = $toolTaskInstanceRunner;
    }

	/**
     * @param string $subflowTaskInstanceRunner
     */
    public function setSubflowTaskInstanceRunner($subflowTaskInstanceRunner) {
        $this->subflowTaskInstanceRunner = $subflowTaskInstanceRunner;
    }

	/**
     * @param string $formTaskInstanceCompletionEvaluator
     */
    public function setFormTaskInstanceCompletionEvaluator($formTaskInstanceCompletionEvaluator) {
        $this->formTaskInstanceCompletionEvaluator = $formTaskInstanceCompletionEvaluator;
    }

	/**
     * @param string $toolTaskInstanceCompletionEvaluator
     */
    public function setToolTaskInstanceCompletionEvaluator($toolTaskInstanceCompletionEvaluator) {
        $this->toolTaskInstanceCompletionEvaluator = $toolTaskInstanceCompletionEvaluator;
    }

	/**
     * @param string $subflowTaskInstanceCompletionEvaluator
     */
    public function setSubflowTaskInstanceCompletionEvaluator($subflowTaskInstanceCompletionEvaluator) {
        $this->subflowTaskInstanceCompletionEvaluator = $subflowTaskInstanceCompletionEvaluator;
    }

    public function initNet() {
        $net = new Net();
        
        $startNode = $this->getStartNode();
        $endNodes = $this->getEndNodes();
        $startNodeNextEdges = $this->getEdgesById($startNode->id,$this->getTransitions(),EntityNames::NODE_FROM);
        $startNodePrevEdges = $this->getEdgesById($startNode->id,$this->getLoops(),EntityNames::NODE_TO);
        $startNode->setNextEdges($startNodeNextEdges);
        $startNode->setPrevEdges($startNodePrevEdges);
        $elementMap = $this->handleNetRelation(); 
        
        $net->setStart($startNode);
        $net->setEnds($endNodes);
        $net->setElementMap($elementMap);
        $this->setNet($net);
        
        return $net;
    }
    
    public function handleNetRelation() {
        $elementMap = array();
        $activities = $this->getActivities();
        $synchronizers = $this->getSynchronizers();
        $startNode = $this->getStartNode();
        $endNodes = $this->getEndNodes();
        $transitions = $this->getTransitions();
        $loops = $this->getLoops();
        
        $elementMap[$startNode->id] = $startNode;
        foreach ($activities as $activity) {
            $activityPrevEdges = $this->getEdgesById($activity->id,$transitions,EntityNames::NODE_TO);
            $activityNextEdges = $this->getEdgesById($activity->id,$transitions,EntityNames::NODE_FROM);
            $activity->setPrevEdges($activityPrevEdges);
            $activity->setNextEdges($activityNextEdges);
            $elementMap[$activity->id] = $activity;
        }
         
        foreach ($synchronizers as $synchronizer) {
            $synchronizerPrevEdges = $this->getEdgesById($synchronizer->id,$transitions,EntityNames::NODE_TO);
            $synchronizerNextEdges = $this->getEdgesById($synchronizer->id,$transitions,EntityNames::NODE_FROM);
            $synchronizerPrevLoops = $this->getEdgesById($synchronizer->id,$loops,EntityNames::NODE_TO);
            $synchronizerNextLoops = $this->getEdgesById($synchronizer->id,$loops,EntityNames::NODE_FROM);
            $synchronizerPrevEdges  = array_merge($synchronizerPrevEdges,$synchronizerPrevLoops);
            $synchronizerNextEdges = array_merge($synchronizerNextEdges,$synchronizerNextLoops);
            $synchronizer->setPrevEdges($synchronizerPrevEdges);
            $synchronizer->setNextEdges($synchronizerNextEdges);
            $elementMap[$synchronizer->id] = $synchronizer;
        }
         
        foreach ($endNodes as $endNode){
            $endNodePrevEdges = $this->getEdgesById($endNode->id,$transitions,EntityNames::NODE_TO);
            $endNode->setPrevEdges($endNodePrevEdges);
            $elementMap[$endNode->id] = $endNode;
        }
        
        foreach ($loops as $loop) {
            $fromId = $loop->getFromId();
            $toId = $loop->getToId();
            $preNode = $this->getNodeById($fromId);
            $nextNode = $this->getNodeById($toId);
            $loop->setPrevNode($preNode) ;
            $loop->setNextNode($nextNode);
            $elementMap[$loop->id] = $loop;
        }
        
        foreach ($transitions as $transition) {
            $fromId = $transition->getFromId();
            $toId = $transition->getToId();
            $preNode = $this->getNodeById($fromId);
            $nextNode = $this->getNodeById($toId);
            $transition->setPrevNode($preNode) ;
            $transition->setNextNode($nextNode);
            $elementMap[$transition->id] = $transition;
        } 
        
        return $elementMap;
    }
   
    
    public  function getNodeById($id) {
        $startNode = $this->getStartNode();
        if($startNode->id == $id) {
            return $startNode;
        }
        $activities = $this->getActivities();
        $synchronizers = $this->getSynchronizers();
        $endNodes = $this->getEndNodes();
        
        foreach ($activities as $activity) {
            if($activity->id == $id) {
                return $activity;
            }
        }
        foreach ($synchronizers as $synchronizer) {
            if($synchronizer->id == $id) {
                return $synchronizer;
            }
        }
    
        foreach ($endNodes as $endNode) {
            if($endNode->id == $id) {
                return $endNode;
            }
        }
    
        throw new ParseException('can not find element, id is '.$id);
    }
    
    /**
     * @param $id
     * @param Edge[] $edges
     * @param $type
     *
     * @return array
     */
    public  function getEdgesById($id,$edges,$type) {
        $nodeEdges = array();
        foreach ($edges as $edge) {
            if(EntityNames::NODE_FROM == $type) {
                if($edge->getFromId() == $id) {
                    $nodeEdges[] = $edge;
                }
            }else if(EntityNames::NODE_TO == $type) {
                if($edge->getToId() == $id) {
                    $nodeEdges[] = $edge;
                }
            }
        }
    
        return $nodeEdges;
    }
    
    
    
}

























