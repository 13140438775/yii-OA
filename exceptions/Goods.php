<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 上午11:56
 */

namespace app\exceptions;


class Goods extends Base
{
    const INVENTORY_SHORTAGE = 20001;
    const GOODS_ADD = 21001;
    const GOOD_NOT_FOUND = 20002;

    public static $reasons = [
        self::INVENTORY_SHORTAGE => '商品库存不足',
        self::GOODS_ADD => '新增失败',
        self::GOOD_NOT_FOUND => "存在错误商品信息"
    ];
}