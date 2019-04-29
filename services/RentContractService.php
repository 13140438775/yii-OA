<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : screamwolf@likingfit.com
 * @CreateTime 2018/3/6 16:50:55
 */

namespace app\services;

use app\models\OpenContract;
use app\models\RentContract;

class RentContractService {

    /**
     * 保存合同
     * @param $params
     *
     * @return bool
     * @CreateTime 2018/3/6 16:55:16
     * @Author     : screamwolf@likingfit.com
     */
    public static function save ($params) {
        $model = new RentContract();
        $model->setAttributes($params, false);
        return $model->save();
    }


}