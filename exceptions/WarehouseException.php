<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 上午11:56
 */

namespace app\exceptions;


class WarehouseException extends Base
{
    const WAREHOUSE_ADD = 21001;
    const WAREHOUSE_EDIT = 21002;
    const CHANGE_GOODS_NUM = 21003;
    const BALANCE_CHECK_TIME = 21004;
    const WAREHOUSE_EMPTY = 21005;
    const INVENTORY_UN_ENOUGH = 21006;
    const DEFECTIVE_INVENTORY_UN_ENOUGH = 21007;
    const WAREHOUSE_NOT_IN = 21008;
    const WAREHOUSE_IS_BALANCE = 21009;
    public static $reasons = [
        self::WAREHOUSE_ADD => '新增失败',
        self::WAREHOUSE_EDIT => '编辑失败',
        self::CHANGE_GOODS_NUM => '更新商品总数失败',
        self::BALANCE_CHECK_TIME => '该时间段已对账',
        self::WAREHOUSE_EMPTY => '该时间段无入库单以及退货单',
        self::INVENTORY_UN_ENOUGH => '良品库存不足,无法完成操作',
        self::DEFECTIVE_INVENTORY_UN_ENOUGH => '次品库存不足,无法完成操作',
        self::WAREHOUSE_NOT_IN => '入库单还未入库，无法退单',
        self::WAREHOUSE_IS_BALANCE => '该入库单已对账,无法退单',
    ];
}