<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 上午11:56
 */

namespace app\exceptions;


class PurchaseException extends Base
{
    const PURCHASE_ADD = 21001;
    const PURCHASE_EDIT = 21002;
    public static $reasons = [
        self::PURCHASE_ADD => '新增失败',
        self::PURCHASE_EDIT => '编辑失败'
    ];
}