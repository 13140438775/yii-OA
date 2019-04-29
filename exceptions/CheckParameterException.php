<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/4/28 10:56:52
 */

namespace app\exceptions;

class CheckParameterException extends  Base{
    const ILLEGAL_STRING = 11000;
    public static $reasons = [
        self::ILLEGAL_STRING => "非法字符串",

    ];
}