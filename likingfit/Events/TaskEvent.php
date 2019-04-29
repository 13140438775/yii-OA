<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/8 16:21:38
 */

namespace likingfit\Events;


use likingfit\Workflow\Base\Activity;

class TaskEvent extends InterruptedEvent
{
    /**
     * @var Activity
     */
    public $activity;
}