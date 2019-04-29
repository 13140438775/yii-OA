<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/3/1
 * Time: 下午1:02
 */

namespace app\exceptions;


class IllegalRequestException extends Base
{
    public $code = 10003;
    public $message = "非法请求";
}