<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/1 13:33:23
 */

namespace app\exceptions;


class StaffException extends Base
{
    const PHONE_EXIST = 71001;
    const EMAIL_EXIST = 71002;
    const DEPARTMENT_EXIST = 71003;
    const NAME_EXIST = 71004;
    const GROUP_EXIST = 71005;

    public static $reasons = [
        self::PHONE_EXIST => "手机号已存在",
        self::EMAIL_EXIST => "账号已存在",
        self::DEPARTMENT_EXIST => "此组名已存在",
        self::NAME_EXIST => "选定的小组已分配组长",
        self::GROUP_EXIST => "小组的名称已经存在",
    ];
}