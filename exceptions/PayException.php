<?php
/**
 * Created by PhpStorm.
 * User: y'y
 * Date: 2018/3/18
 * Time: 13:32
 */

namespace app\exceptions;


class PayException extends Base
{
    const NOT_FOUND = 70001;
    public static $reasons = [
        self::NOT_FOUND => "包含未知的付款单"
    ];
}