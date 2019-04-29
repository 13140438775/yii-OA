<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/3/1
 * Time: 下午12:58
 */

namespace app\exceptions;


class ExpireException extends Base
{
    public $code = 10002;
    public $message = "页面已过期，请刷新后重试！";
}