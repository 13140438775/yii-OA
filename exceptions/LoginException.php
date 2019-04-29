<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/1 13:33:23
 */

namespace app\exceptions;


class LoginException extends Base
{
    const ERR_LOGIN = 30000;

    const ERR_EMAIL = 30001;

    const ERR_PASSWORD = 30002;

    const ERR_TOKEN = 30003;

    const INVALID_STAFF = 30004;

    const LOCK_STAFF = 30005;

    const LOGIN_INVALID = 30007;

    const UN_LOGIN = 30008;

    const STAFF_NO_EXIST = 30009;

    const NO_VALID = 30010;

    const ERROR_CODE = 30011;

    public static $reasons = [
        self::ERR_LOGIN => "登录失败",
        self::ERR_EMAIL => "用户不存在",
        self::ERR_PASSWORD => "密码错误",
        self::ERR_TOKEN => "token错误",
        self::INVALID_STAFF => "无效用户",
        self::LOCK_STAFF => "用户已锁定",
        self::LOGIN_INVALID => "登录态失效",
        self::UN_LOGIN => "您还没有登录，请登录后重试！",
        self::STAFF_NO_EXIST => "用户不存在，请检查手机号",
        self::NO_VALID => "用户无效或用户已离职",
        self::ERROR_CODE => "验证码错误",
    ];
}