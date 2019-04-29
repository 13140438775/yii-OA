<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/10 10:37:44
 */

namespace app\exceptions;


class OrderException extends Base
{
    const NO_REPLENISHMENT = 60001;

    const NO_ORDER = 60002;

    const AGREE_ORDER = 60003;

    const STASH_ORDER = 60004;

    const SAVE_FAIL = 60005;

    const PURCHASE_COMMIT = 61001;

    const ALREADY_PRESALE = 60006;

    const CLOSE_EXCEPTION = 60007;

    const NO_ORDER_GOOD = 60008;

    const ORDER_CLOSE = 60009;

    const REJECT_CLOSE = 60010;

    const TIME_ERROR = 60011;

    const CONTAINER_ZERO = 60012;

    public static $reasons = [
        self::NO_REPLENISHMENT => "该健身房已经在指定流程点之外，不允许补货",
        self::NO_ORDER => "未找到订单相关信息",
        self::AGREE_ORDER => "该订单已通过审核，不能修改",
        self::SAVE_FAIL => "订单保存失败",
        self::STASH_ORDER => "该订单还是处于保存状态，请先提交该订单",
        self::PURCHASE_COMMIT => "订单信息不完整",
        self::ALREADY_PRESALE => "已经存在预售订单",
        self::CLOSE_EXCEPTION => "已经确认发货，不能关闭该订单",
        self::NO_ORDER_GOOD => "该订单没有商品，请先选择商品",
        self::ORDER_CLOSE => "订单已经关闭",
        self::REJECT_CLOSE => "你没有权限关闭该订单",
        self::TIME_ERROR => "实际到货时间不能早于实际发货时间",
        self::CONTAINER_ZERO => "采购的商品数量不能为0"
    ];
}