<?php
namespace likingfit\Workflow\Util;

interface LockService{
    /**
     * 加锁
     * @param unknown $name
     */
    public function lock($name);
    
    /**
     * 取消锁
     * @param unknown $name
     */
    public function unlock($name);
}