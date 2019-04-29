<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/14 14:33:28
 */
namespace app\services;

use app\models\OpenContract;

class OpenContractService {
    public static function save($params)
    {
        $model = new OpenContract();
        $model->setAttributes($params, false);
        return $model->save();
    }
}