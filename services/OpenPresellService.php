<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : screamwolf@likingfit.com
 * @CreateTime 2018/3/6 17:18:04
 */

namespace app\services;

use app\models\OpenPresell;

class OpenPresellService {

    /**
     * @param $params
     *
     * @return mixed
     * @CreateTime 2018/3/6 17:18:23
     * @Author     : screamwolf@likingfit.com
     */
    public static function save ($params) {
        $model = new OpenPresell();
        $model->setAttributes($params, false);
        return $model->save();
    }
}