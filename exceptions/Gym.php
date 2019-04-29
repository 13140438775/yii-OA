<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 上午11:56
 */

namespace app\exceptions;


class Gym extends Base
{
    const SAVE_ERR = 50001;

    const NOT_FOUND = 50002;

    const COULD_NOT_REPLENISHMENT = 50003;

    const CLOSE_ERR = 50004;

    const GYM_SAVE_FAILED = 50005;

    const NO_ACCESS = 50006;

    public static $reasons = [
        self::SAVE_ERR => '场馆保存失败',
        self::NOT_FOUND => "未找到健身房",
        self::COULD_NOT_REPLENISHMENT => "该健身房不在补单阶段内",
        self::GYM_SAVE_FAILED  => "新增健身房失败",
        self::CLOSE_ERR => "该健身房已开店或者已关闭",
        self::NO_ACCESS => "没有权限关闭该健身房"
    ];
}