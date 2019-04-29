<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/1 13:33:23
 */

namespace app\exceptions;


class CustomerException extends Base
{
    const INVALID = 70000;
    const ERR_EXIST = 70001;
    const DOCKING_ONLINE = 70002;

    /**
     * 面试未通过
     */
    const NO_AUDITION = 70003;

    /**
     * 预留地址超过
     */
    const OCCUPANCY_ADDRESS_OVER = 70004;

    /**
     * 已申请面试
     */
    const APPLY_INTERVIEW = 70005;

    public static $reasons = [
        self::INVALID => "无效客户",
        self::ERR_EXIST => "用户已存在",
        self::DOCKING_ONLINE => "对接上限150",
        self::NO_AUDITION=>'面试未通过',
        self::OCCUPANCY_ADDRESS_OVER => '只能预留一个地址',
        self::APPLY_INTERVIEW => '客户处于面试阶段或已经签约，不能被重新指定'
        //self::NO_SIGNING => '未签约成功'
    ];
}