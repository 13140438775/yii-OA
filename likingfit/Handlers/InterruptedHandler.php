<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/13 11:18:53
 */

namespace likingfit\Handlers;

use likingfit\Events\ActivityEvent;
use likingfit\Workflow\Base\Activity;
use yii\base\InvalidCallException;

abstract class InterruptedHandler implements Handler
{
    abstract public function breakPoint();

    public function activityBeforeStart(ActivityEvent $event)
    {
        /**
         * @var $activity Activity
         */
        $activity = $event->sender;
        $activityId = $activity->getId();
        $points = $this->breakPoint();
        if(!isset($points[$activityId])){
            return;
        }
        $callable = $points[$activityId];
        if(is_string($callable)){
            $callable = [$this, $callable];
        }
        if (is_callable($callable)) {
            $event->is_valid = call_user_func($callable, $activity, $event->token);
            return;
        }
        throw new InvalidCallException;
    }
}