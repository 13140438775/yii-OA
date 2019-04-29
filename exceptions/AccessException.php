<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/20 16:09:37
 */

namespace app\exceptions;


class AccessException extends Base
{
    const NO_ACCESS = 80000;

    public static $reasons = [
        self::NO_ACCESS => "您没有权限访问"
    ];
}