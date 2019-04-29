<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/13 12:34:16
 */

namespace likingfit\Events;


use likingfit\Workflow\Base\Token;

class ActivityEvent extends InterruptedEvent
{
    /**
     * @var Token
     */
    public $token;
}