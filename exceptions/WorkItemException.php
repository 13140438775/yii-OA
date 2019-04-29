<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/26 00:55:16
 */

namespace app\exceptions;


class WorkItemException extends Base
{
    public $code = 10004;
    public $message = "该工作事项已经被其他人完成。";
}