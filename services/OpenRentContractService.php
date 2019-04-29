<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/14 14:19:49
 */

namespace app\services;

use app\models\OpenRentContract;

class OpenRentContractService{
    public static function save($params)
    {
        $model = new OpenRentContract();
        $model->setAttributes($params, false);
        return $model->save();
    }
}