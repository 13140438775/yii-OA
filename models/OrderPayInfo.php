<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 11:55:13
 */
namespace app\models;

use yii\db\ActiveRecord;

class OrderPayInfo extends ActiveRecord{
    public static function tableName()
    {
        return 't_order_pay_info';
    }
    public static function unavailable($orderId){
        self::updateAll(['pay_status'=>UNAVAILABLE,'update_time'=>time()],['order_id'=>$orderId]);
    }

    public function getCertificate()
    {
        return $this->hasMany(OrderPayCertificate::className(),['order_pay_info_id'=>'id'])->where(['certificate_status' =>AVAILABLE]);
    }
}