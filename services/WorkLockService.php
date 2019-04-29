<?php

namespace app\services;

use Redis;
use likingfit\Workflow\Util\LockService;

class WorkLockService implements LockService
{
    const EXPIRE = 5000;//5s
    const SLEEP = 100;//0.1s

    public function lock($name)
    {
        $redis = \Yii::$app->redis;
        while (true) {
            $t0 = microtime(true);
            if ($redis->setnx($name, $t0)) {
                return true;
            }
            $t1 = $redis->get($name);
            if (!$t1) {
                continue;
            }
            //锁超时
            if (microtime(true) - $t1 > self::EXPIRE) {
                $t2 = $redis->getSet($name, $t0);
                if (!$t2 || $t1 == $t2) {
                    return true;
                }
            }
            sleep(self::SLEEP);
        }
    }

    public function unlock($name)
    {
        \Yii::$app->redis->del($name);
    }
}