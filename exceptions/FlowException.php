<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/3 14:25:35
 */

namespace app\exceptions;


class FlowException extends Base
{
    const INVALID_COMMAND = 40001;

    const INVALID_METHOD = 40002;

    public static $reasons = [
        self::INVALID_COMMAND => '未找到流程命令字',
        self::INVALID_METHOD => '无效的方法'
    ];
}