<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/8 16:21:38
 */

namespace likingfit\Events;


use app\models\WorkItem;
use yii\base\Event;

class WorkItemEvent extends Event
{
    /**
     * @var WorkItem
     */
    public $workItem;
}