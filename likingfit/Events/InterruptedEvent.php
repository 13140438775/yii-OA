<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/13 11:12:28
 */

namespace likingfit\Events;


use yii\base\Event;

class InterruptedEvent extends Event
{
    public $is_valid = true;

}