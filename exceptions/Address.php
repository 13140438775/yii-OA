<?php

namespace app\exceptions;

class Address extends Base
{
    const INVALID = 90000;

    public static $reasons = [
        self::INVALID => '无效的地址'
    ];
}