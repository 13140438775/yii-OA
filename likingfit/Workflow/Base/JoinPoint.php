<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/16
 * Time: 上午11:37
 */
namespace likingfit\Workflow\Base;
class JoinPoint extends Object {
    private $alive;
    private $value;
    private $step;
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
}