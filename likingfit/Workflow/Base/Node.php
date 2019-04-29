<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:14
 */

namespace likingfit\Workflow\Base;
abstract class Node extends Element
{
    /**
     * @var Edge[]
     */
    protected $prevEdges = array();
    /**
     * @var Edge[]
     */
    protected $nextEdges = array();

    abstract public function fire(Token $token);

    public function getPrevEdges()
    {
        return $this->prevEdges;
    }

    public function addPrevEdge(Edge $edge)
    {
        array_push($this->prevEdges, $edge);
    }

    public function getNextEdges()
    {
        return $this->nextEdges;
    }

    public function addNextEdge(Edge $edge)
    {
        array_push($this->nextEdges, $edge);
    }

    public function getPrevLoop()
    {
        $loop = array();
        foreach ($this->getPrevEdges() AS $edge) {
            if ($edge instanceof Loop) {
                $loop[] = $edge;
            }
        }
        return $loop;
    }

    /**
     * @return Transition[]
     */
    public function getPrevTransition()
    {
        $transition = array();
        foreach ($this->getPrevEdges() AS $edge) {
            if ($edge instanceof Transition) {
                $transition[] = $edge;
            }
        }
        return $transition;
    }

    /**
     * @return Loop[]
     */
    public function getNextLoop()
    {
        $loop = array();
        foreach ($this->getNextEdges() AS $edge) {
            if ($edge instanceof Loop) {
                $loop[] = $edge;
            }
        }
        return $loop;
    }

    /**
     * @return Transition[]
     */
    public function getNextTransition()
    {
        $transition = array();
        foreach ($this->getNextEdges() AS $edge) {
            if ($edge instanceof Transition) {
                $transition[] = $edge;
            }
        }
        return $transition;
    }

    public function setNextEdges($edges)
    {
        $this->nextEdges = $edges;
    }

    public function setPrevEdges($edges)
    {
        $this->prevEdges = $edges;
    }

    /**
     * 生成图的
     *
     * @return Activity[]
     * @CreateTime 18/4/21 17:22:45
     * @Author: fangxing@likingfit.com
     */
    public function getNextEffectActivityForGraph()
    {
        $nextTransitions = $this->getNextTransition();
        $nextEffectActivity = array();
        foreach ($nextTransitions as $transition) {
            $node = $transition->getNextNode();
            if ($node instanceof Synchronizer
                || count($node->getTasks()) == 0) {
                $nextEffectActivity = array_merge($nextEffectActivity, $node->getNextEffectActivityForGraph());
            } else {
                $nextEffectActivity = array_merge($nextEffectActivity, [$node]);
            }
        }
        return $nextEffectActivity;
    }

    /**
     * 生成图的
     *
     * @return Activity[]
     * @CreateTime 18/4/21 17:24:20
     * @Author: fangxing@likingfit.com
     */
    public function getPrevEffectActivityForGraph()
    {
        $prevTransitions = $this->getPrevTransition();
        $prevEffectActivity = array();
        foreach ($prevTransitions as $transition) {
            $node = $transition->getPrevNode();
            if($node instanceof Synchronizer
                || count($node->getTasks()) == 0){
                $prevEffectActivity = array_merge($prevEffectActivity, $node->getPrevEffectActivityForGraph());
            }else{
                $prevEffectActivity = array_merge($prevEffectActivity, [$node]);
            }
        }
        return $prevEffectActivity;
    }

    /**
     * 获取所有有效后驱Activity节点(排除Synchronizer和辅助Activity)
     * @return Activity[]
     */
    public function getNextEffectActivity()
    {
        $nextTransitions = $this->getNextTransition();
        $nextEffectActivity = array();
        foreach ($nextTransitions as $transition) {
            $node = $transition->getNextNode();
            if ($node instanceof Synchronizer
                || count($node->getTasks()) == 0
                || $node->displayName == "辅助模块") {
                $nextEffectActivity = array_merge($nextEffectActivity, $node->getNextEffectActivity());
            } else {
                $nextEffectActivity = array_merge($nextEffectActivity, [$node]);
            }
        }
        return $nextEffectActivity;
    }

    /**
     * 获取所有有效前置节点(排除Synchronizer和辅助Activity)
     * @return Activity[]
     */
    public function getPrevEffectActivity()
    {
        $prevTransitions = $this->getPrevTransition();
        $prevEffectActivity = array();
        foreach ($prevTransitions as $transition) {
            $node = $transition->getPrevNode();
            if($node instanceof Synchronizer
                || count($node->getTasks()) == 0
                || $node->displayName == "辅助模块"){
                $prevEffectActivity = array_merge($prevEffectActivity, $node->getPrevEffectActivity());
            }else{
                $prevEffectActivity = array_merge($prevEffectActivity, [$node]);
            }
        }
        return $prevEffectActivity;
    }

    /**
     * 获取有效的前一个节点，包括loop节点
     *
     * @param $loop bool
     * @return Activity[]
     * @CreateTime 18/3/19 12:09:11
     * @Author: fangxing@likingfit.com
     */
    public function getPrevEffectActivity2($loop = false)
    {
        $prevTransitions = $this->getPrevTransition();
        $prevEffectActivity = [];
        foreach ($prevTransitions as $transition) {
            $node = $transition->getPrevNode();
            $loops = $loop ? $node->getPrevLoop() : '';
            if (($node instanceof Synchronizer && (empty($loops) || !$loop))
                || ($node instanceof Activity && (count($node->getTasks()) == 0 || $node->displayName == "辅助模块"))) {
                $prevEffectActivity = array_merge($prevEffectActivity, $node->getPrevEffectActivity2($loop));
            } else {
                //搜索loop节点
                if ($loop && $loops !== []) {
                    /**
                     * @var $loops Loop[]
                     */
                    foreach ($loops as $row){
                        //排除不是loop到本节点的线
                        if($row->getToId() != $node->getId()){
                            continue;
                        }
                        $prevEffectActivity = array_merge($prevEffectActivity, $row->getPrevNode()->getPrevEffectActivity2($loop));
                    }
                }
                if ($node instanceof Activity) {
                    $prevEffectActivity[] = $node;
                }
            }
        }
        return $prevEffectActivity;
    }

    /**
     * 获取loop节点
     *
     * @return Activity[]
     * @CreateTime 18/4/21 17:24:49
     * @Author: fangxing@likingfit.com
     */
    public function getPrevEffectActivityForLoop()
    {
        $prevTransitions = $this->getPrevTransition();
        $prevEffectActivity = [];
        foreach ($prevTransitions as $transition) {
            $node = $transition->getPrevNode();
            $loops = $node->getPrevLoop();
            if(empty($loops)){
                continue;
            }
            //搜索loop节点
            foreach ($loops as $row){
                //排除不是loop到本节点的线
                if($row->getToId() != $node->getId()){
                    continue;
                }
                $prevEffectActivity = array_merge($prevEffectActivity, $row->getPrevNode()->getPrevEffectActivity2(false));
            }
        }
        return $prevEffectActivity;
    }
}