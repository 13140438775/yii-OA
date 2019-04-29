<?php

namespace likingfit\Workflow\Parser;

use likingfit\Workflow\Exception\ParseException;
use likingfit\Workflow\Base\Synchronizer;
use likingfit\Workflow\Base\Activity;
use likingfit\Workflow\Base\Task;
use likingfit\Workflow\Base\Transition;
use likingfit\Workflow\Base\TemplateProcess;
use likingfit\Workflow\Base\Loop;

class Parser {

    public static function parse($path) {
        libxml_disable_entity_loader(false);
        $dom = new \DOMDocument();
        $dom->load($path);
        
        $templateProcess = new TemplateProcess();
        $root = $dom->documentElement;
        self::checkRootChild($root);
        $templateProcess = self::setBaseAttributes($templateProcess, $root);
        $templateProcess = self::setGlobalTaskAttribute($templateProcess, $root);
        $startNode = self::loadStartNode($root);
        $actvities = self::loadActivites($root);
        $actvities = self::setTaskAttributes($templateProcess, $actvities);
        $synchronizers = self::loadSynchronizer($root);
        $endNodes = self::loadEndNodes($root);
        $dataFields = self::loadDataField($root);
        $transitions = self::loadTransitions($root);
        $loops = self::loadLoops($root);
        
        $templateProcess->setStartNode($startNode);
        $templateProcess->setEndNodes($endNodes);
        $templateProcess->setActivities($actvities);
        $templateProcess->setSynchronizers($synchronizers);
        $templateProcess->setEndNodes($endNodes);
        $templateProcess->setDataFields($dataFields);
        $templateProcess->setTransitions($transitions);
        $templateProcess->setLoops($loops);
        
        return $templateProcess;
    }

    public static function setBaseAttributes($element, $node) {
        $domAttrs = $node->attributes;
        $baseAttr = [ 
                'id', 
                'name', 
                'displayName' 
        ];
        
        foreach ($domAttrs as $attr) {
            $key = lcfirst($attr->nodeName);
            if(in_array($key, $baseAttr)) {
                $element->$key = $attr->nodeValue;
            }
        }
        
        $description = self::getDescription($node);
        $element->description = $description;
        
        return $element;
    }
    
    /**
     * @param TemplateProcess $templateProcess
     * @param                 $root
     *
     * @return TemplateProcess
     */
    public static function setGlobalTaskAttribute(TemplateProcess $templateProcess, $root) {
        $taskInstanceCreator = $root->getAttribute(EntityNames::TASK_INSTANCE_CREATOR);
        $formTaskInstanceRunner = $root->getAttribute(EntityNames::FORM_TASK_INSTANCE_RUNNER);
        $toolTaskInstanceRunner = $root->getAttribute(EntityNames::TOOL_TASK_INSTANCE_RUNNER);
        $subflowTaskInstanceRunner = $root->getAttribute(EntityNames::SUBFLOW_TASK_INSTANCE_RUNNER);
        $formTaskInstanceCompletionEvaluator = $root->getAttribute(EntityNames::FORM_TASK_INSTANCE_COMPLETION_EVALUATOR);
        $toolTaskInstanceCompletionEvaluator = $root->getAttribute(EntityNames::TOOL_TASK_INSTANCE_COMPLETION_EVALUATOR);
        $subflowTaskInstanceCompletionEvaluator = $root->getAttribute(EntityNames::SUBFLOW_TASK_INSTANCE_COMPLETION_EVALUATOR);
        
        $templateProcess->setTaskInstanceCreator($taskInstanceCreator);
        $templateProcess->setFormTaskInstanceRunner($formTaskInstanceRunner);
        $templateProcess->setToolTaskInstanceRunner($toolTaskInstanceRunner);
        $templateProcess->setSubflowTaskInstanceRunner($subflowTaskInstanceRunner);
        $templateProcess->setFormTaskInstanceCompletionEvaluator($formTaskInstanceCompletionEvaluator);
        $templateProcess->setToolTaskInstanceCompletionEvaluator($toolTaskInstanceCompletionEvaluator);
        $templateProcess->setSubflowTaskInstanceCompletionEvaluator($subflowTaskInstanceCompletionEvaluator);
        
        return $templateProcess;
    }

    public static function getDescription($node) {
        $decription = '';
        if($node->hasChildNodes()) {
            $childNodes = $node->childNodes;
            foreach ($childNodes as $element) {
                if(($element->nodeType != XML_TEXT_NODE) && (EntityNames::TAG_DESCRIPTION == $element->tagName)) {
                    $decription = $element->nodeValue;
                }
            }
        }
        
        return $decription;
    }

    public static function loadStartNode($root) {
        $element = self::getElementByTagName($root, EntityNames::TAG_STARTNODE);
        
        if(empty($element)) {
            throw new ParseException('there is no start element');
        }
        
        $startNode = new Synchronizer();
        $startNode = self::setBaseAttributes($startNode, $element);
        $startNode->setStart(true);
        
        return $startNode;
    }

    public static function loadActivites($root) {
        $element = self::getElementByTagName($root, EntityNames::TAG_ACTIVITES);
        $activites = array ();
        $childActivites = self::getElementsByTagName($element, EntityNames::TAG_ACTIVITY);
        foreach ($childActivites as $activityNode) {
            $activity = new Activity();
            $activity = self::setBaseAttributes($activity, $activityNode);
            $dependency = $activityNode->getAttribute(EntityNames::ACTIVITY_DEPENDENCY);
            $activity->setDependency($dependency);
            $activityTasks = array ();
            $tasks = self::getElementByTagName($activityNode, EntityNames::TAG_TASKS);
            if(!empty($tasks)) {
                $taskNodes = self::getElementsByTagName($tasks, EntityNames::TAG_TASK);
                foreach ($taskNodes as $taskNode) {
                    $task = self::loadTask($taskNode);
                    $task->setActivityId($activity->id);
                    $activityTasks[] = $task;
                }
            }
            $activity->setTasks($activityTasks);
            $activites[] = $activity;
        }
        
        return $activites;
    }

    public static function loadTask($taskNode) {
        $task = new Task();
        $task = self::setBaseAttributes($task, $taskNode);
        $type = $taskNode->getAttribute(EntityNames::ATTRIBUTE_TASK_TYPE);
        $formType = \Yii::$app->params['form'];
        $task->setType($formType[$type]);
        $params = array ();
        $taskInstanceCreator = $taskNode->getAttribute(EntityNames::TASK_INSTANCE_CREATOR);
        
        $runner = $taskNode->getAttribute(EntityNames::TASK_INSTANCE_RUNNER);
        $completionEvaluator = $taskNode->getAttribute(EntityNames::TASK_INSTANCE_COMPLETION_EVALUATOR);
        if(EntityNames::ATTRIBUTE_TASK_FORM == $type) {
            $params = self::loadFormTaskParams($taskNode);
        } else if(EntityNames::ATTRIBUTE_TASK_TOOL == $type) {
            $params = self::loadToolTaskParams($taskNode);
        } else {
            $params = self::loadSubflowTaskParams($taskNode);
        }
    
    
        $task->setParams($params);
        $task->setCreator($taskInstanceCreator);
        $task->setRunner($runner);
        $task->setCompletionEvaluator($completionEvaluator);
        
        return $task;
    }

    public static function loadFormTaskParams($taskNode) {
        $params = array ();
        $form = self::getElementByTagName($taskNode, EntityNames::TAG_EDITFORM);
        $form = empty($form) ? self::getElementByTagName($taskNode, EntityNames::TAG_VIEWFORM) : $form;
        $form = empty($form) ? self::getElementByTagName($taskNode, EntityNames::TAG_LISTFORM) : $form;

        /*$perform = self::getElementByTagName($taskNode, EntityNames::TAG_PERFORMER);
        if(!empty($form)) {
            $uriNode = self::getElementByTagName($form, EntityNames::TAG_URI);
            if(!empty($uriNode)) {
                $uri = $uriNode->nodeValue;
                $params['form'] = [ 
                        'uri' => $uri 
                ];
            }
        }
        
        if(empty($perform)) {
             throw new ParseException('form task has no performer');
        }
        
        $params['performer'] = $perform->getAttribute('Name');*/
        // $assignmentHandlerNode = self::getElementByTagName($perform,EntityNames::TAG_ASSIGNMENTHANDLER);
        // if(!empty($assignmentHandlerNode)) {
        // $assignmentHandler = $assignmentHandlerNode->nodeValue;
        // $params['peformer'] = ['assignmentHandler' => $assignmentHandler];
        // }
        
        return $params;
    }

    public static function loadToolTaskParams($taskNode) {
        $params = array ();
        
        $application = self::getElementByTagName($taskNode, EntityNames::TAG_APPLICATION);
        if(empty($application)) {
            throw new ParseException('tool task has no application handler');
        }
        $handlerNode = self::getElementByTagName($application, EntityNames::TAG_TOOLTASK_HANDLER);
        if(!empty($handlerNode)) {
            $handler = $handlerNode->nodeValue;
            $params['application'] = [
            'handler' => $handler
            ];
        }
        
        return $params;
    }

    public static function loadSubflowTaskParams($taskNode) {
        $params = array ();
        
        $subflowProcess = self::getElementByTagName($taskNode, EntityNames::TAG_SUBFLOWWORKFLOWPROCESS);
        if(empty($subflowProcess)) {
            throw new ParseException('subflow task has no subworkflowprocess tag');
        }
        $workflowNode = self::getElementByTagName($subflowProcess, EntityNames::TAG_SUBFLOWWORKFLOWPROCESSID);
        $params['process_id'] = $workflowNode->nodeValue;
        
        return $params;
    }

    public static function setTaskAttributes($templateProcess, $actvities) {
        $taskInstanceCreator = $templateProcess->getTaskInstanceCreator();
        $formTaskInstanceRunner = $templateProcess->getFormTaskInstanceRunner();
        $toolTaskInstanceRunner = $templateProcess->getToolTaskInstanceRunner();
        $subflowTaskInstanceRunner = $templateProcess->getSubflowTaskInstanceRunner();
        $formTaskInstanceCompletionEvaluator = $templateProcess->getFormTaskInstanceCompletionEvaluator();
        $toolTaskInstanceCompletionEvaluator = $templateProcess->getToolTaskInstanceCompletionEvaluator();
        $subflowTaskInstanceCompletionEvaluator = $templateProcess->getSubflowTaskInstanceCompletionEvaluator();
        foreach ($actvities as $activity) {
            $activityTasks = $activity->getTasks();
            foreach ($activityTasks as $index => $activityTask) {
                $creator = $activityTask->getCreator();
                $runner = $activityTask->getRunner();
                $completionEvaluator = $activityTask->getCompletionEvaluator();
                $creator = empty($creator) ? $taskInstanceCreator : $creator;
                if(Task::FORM == $activityTask->getType()) {
                    $runner = empty($runner) ? $formTaskInstanceRunner : $runner;
                    $completionEvaluator = empty($completionEvaluator) ? $formTaskInstanceCompletionEvaluator : $completionEvaluator;
                } else if(Task::TOOL == $activityTask->getType()) {
                    $runner = empty($runner) ? $toolTaskInstanceRunner : $runner;
                    $completionEvaluator = empty($completionEvaluator) ? $toolTaskInstanceCompletionEvaluator : $completionEvaluator;
                } else {
                    $runner = empty($runner) ? $subflowTaskInstanceRunner : $runner;
                    $completionEvaluator = empty($completionEvaluator) ? $subflowTaskInstanceCompletionEvaluator : $completionEvaluator;
                }
                
                $activityTask->setCreator($taskInstanceCreator);
                $activityTask->setRunner($runner);
                $activityTask->setCompletionEvaluator($completionEvaluator);
                $activityTasks[$index] = $activityTask;
            }
        }
        
        return $actvities;
    }

    public static function loadSynchronizer($root) {
        $synchronizersNode = self::getElementsByTagName(self::getElementByTagName($root, EntityNames::TAG_SYNCHRONIZERS), EntityNames::TAG_SYNCHRONIZER);
        if(empty($synchronizersNode)) {
            throw new ParseException('synchronizers has no synchronizer');
        }
        $synchronizers = array ();
        foreach ($synchronizersNode as $synchronizerNode) {
            $synchronizer = new Synchronizer();
            $synchronizer = self::setBaseAttributes($synchronizer, $synchronizerNode);
            $synchronizers[] = $synchronizer;
        }
        
        return $synchronizers;
    }

    public static function loadTransitions($root) {
        $transitionNodes = self::getElementsByTagName(self::getElementByTagName($root, EntityNames::TAG_TRANSITIONS), EntityNames::TAG_TRANSITION);
        if(empty($transitionNodes)) {
            throw new ParseException('transitions has no transition');
        }
        
        $transitions = array ();
        foreach ($transitionNodes as $transitionNode) {
            $transition = new Transition();
            $transition = self::setBaseAttributes($transition, $transitionNode);
            $fromId = $transitionNode->getAttribute('From');
            $toId = $transitionNode->getAttribute('To');
            $transition->setFromId($fromId);
            $transition->setToId($toId);
            $condition = self::getElementByTagName($transitionNode, EntityNames::TAG_CONDITION);
            if(!empty($condition)) {
                $conditionValue = $condition->nodeValue;
                $conditionValue = empty($conditionValue) ? true : $conditionValue;
            } else {
                $conditionValue = true;
            }
            $transition->setCondition($conditionValue);
            
            $transitions[] = $transition;
        }
        
        return $transitions;
    }

    public static function loadLoops($root) {
        $loops = array ();
        $loopsNode = self::getElementByTagName($root, EntityNames::TAG_LOOPS);
        if(empty($loopsNode)) {
            return $loops;
        }
        $loopNodes = self::getElementsByTagName($loopsNode, EntityNames::TAG_LOOP);
        
        foreach ($loopNodes as $loopNode) {
            $loop = new Loop();
            $transition = self::setBaseAttributes($loop, $loopNode);
            $fromId = $loopNode->getAttribute('From');
            $toId = $loopNode->getAttribute('To');
            $loop->setFromId($fromId);
            $loop->setToId($toId);
            $condition = self::getElementByTagName($loopNode, EntityNames::TAG_CONDITION);
            $conditionValue = $condition->nodeValue;
            $loop->setCondition($conditionValue);
            
            $loops[] = $loop;
        }
        
        return $loops;
    }

    public static function loadEndNodes($root) {
        $endNodes = self::getElementsByTagName(self::getElementByTagName($root, EntityNames::TAG_ENDNODES), EntityNames::TAG_ENDNODE);
        if(empty($endNodes)) {
            throw new ParseException('endnodes has no endnode');
        }
        $ends = array ();
        foreach ($endNodes as $endNode) {
            $end = new Synchronizer();
            $end = self::setBaseAttributes($end, $endNode);
            $end->setEnd(true);
            
            $ends[] = $end;
        }
        
        return $ends;
    }

    public static function loadDataField($root) {
        $dataFields = array ();
        $dataFieldsNode = self::getElementByTagName($root, EntityNames::TAG_DATAFIELDS);
        if(empty($dataFieldsNode)) {
            return $dataFields;
        }
        $dataFieldNodes = self::getElementsByTagName($dataFieldsNode, EntityNames::TAG_DATAFIELD);
        foreach ($dataFieldNodes as $dataFieldNode) {
            $dataFields[$dataFieldNode->getAttribute(EntityNames::ATTRIBUTE_NAME)] = $dataFieldNode->getAttribute(EntityNames::DATAFIELD_VALUE);
        }
        
        return $dataFields;
    }

    public static function checkRootChild($root) {
        if(!$root->hasChildNodes()) {
            throw new ParseException('root has no child');
        }
    }

    public static function getElementByTagName($node, $tagName) {
        $childNodes = $node->childNodes;
        $element = null;
        foreach ($childNodes as $node) {
            if(($node->nodeType != XML_TEXT_NODE) && ($tagName == $node->tagName)) {
                $element = $node;
            }
        }
        
        return $element;
    }

    public static function getElementsByTagName($node, $tagName) {
        $childNodes = $node->childNodes;
        $element = array ();
        foreach ($childNodes as $node) {
            if(($node->nodeType != XML_TEXT_NODE) && ($tagName == $node->tagName)) {
                array_push($element, $node);
            }
        }
        
        return $element;
    }
    
    
    
}