<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 上午11:56
 */

namespace app\exceptions;


class SupplierException extends Base
{
    const SUPPLIER_ADD = 21001;
    const SUPPLIER_EDIT = 21002;
    const SUPPLIER_NAME_IS_EXITS = 21003;
    public static $reasons = [
        self::SUPPLIER_ADD => '新增失败',
        self::SUPPLIER_EDIT => '编辑失败',
        self::SUPPLIER_NAME_IS_EXITS => '供应商名称已存在,请更换供应商名称'
    ];
}