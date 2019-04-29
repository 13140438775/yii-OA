<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 11:55:13
 */
namespace app\models;

use yii\db\ActiveRecord;

class OrderPayCertificate extends Base {
    public static function tableName()
    {
        return 't_order_pay_certificate';
    }

    public static function unavailable($orderPayInfoId){
        self::updateAll(['certificate_status'=>UNAVAILABLE,'update_time'=>time()],['order_pay_info_id'=>$orderPayInfoId]);
    }


}