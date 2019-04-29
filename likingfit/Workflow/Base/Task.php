<?php

namespace likingfit\Workflow\Base;

class Task extends Object {
    const TOOL = 1;
    const SUBFLOW = 2;
    const FORM = 3;
    const CANCELED = 4;

    private $type = 0;
    private $activityId;
    private $creator;
    private $runner;
    private $completionEvaluator;
    private $params = array();
    
    public function getActivityId(){
        return $this->activityId;
    }

    public function setActivityId($activityId) {
        $this->activityId = $activityId;
    }

    public function getType() {
        return $this->type;
    }

    public function setType($type) {
        $this->type = $type;
    }

    public function getCreator() {
        return $this->creator;
    }

    public function setCreator($creator) {
        $this->creator = $creator;
    }

    public function getRunner() {
        return $this->runner;
    }

    public function setRunner($runner) {
        $this->runner = $runner;
    }

    public function getCompletionEvaluator() {
        return $this->completionEvaluator;
    }

    public function setCompletionEvaluator($completionEvaluator) {
        $this->completionEvaluator = $completionEvaluator;
    }

    public function getParams() {
        return $this->params;
    }

    public function setParams($params) {
        $this->params = $params;
    }
    
}
    
    






















