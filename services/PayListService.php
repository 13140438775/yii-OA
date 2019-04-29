<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/14 18:11:38
 */

namespace app\services;


use app\models\PayList;

class PayListService
{
    /**
     * @param $conditions
     * @param string $with
     * @return null|PayList
     * @CreateTime 18/4/25 13:20:54
     * @Author: fangxing@likingfit.com
     */
    public static function getPayListInfo($conditions, $with="payTask")
    {
        return PayList::find()
            ->where($conditions)
            ->with($with)
            ->one();
    }
}