<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/19 17:00:52
 */

namespace app\models;

class ChargebackAction extends Base{
    public static function tableName()
    {
        return 't_chargeback_action';
    }

    public static function unavailable($orderId){
        self::updateAll(['action_status'=>UNAVAILABLE,'update_time'=>time()],['order_id'=>$orderId]);
    }
}