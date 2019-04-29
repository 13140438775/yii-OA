<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/10 10:37:44
 */

namespace app\exceptions;


class ChargebackException extends Base
{
    const ORDER_STATUS_COMMIT = 63001;




    public static $reasons = [
        self::ORDER_STATUS_COMMIT => "已完成订单才可退单",

    ];
}