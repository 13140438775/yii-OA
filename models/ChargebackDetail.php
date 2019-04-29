<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/13 16:08:33
 */
namespace app\models;

class ChargebackDetail extends Base{
    public static function tableName()
    {
        return 't_chargeback_detail';
    }

    public static function unavailable($chargebackId){
        self::updateAll(['detail_status'=>UNAVAILABLE,'update_time'=>time()],['chargeback_id'=>$chargebackId]);
    }

    public static function updateNum($attributes,$detailId){
        self::updateAll($attributes,['id'=>$detailId]);
    }

    public  function getOrderDetail(){
        return $this->hasOne(Goods::className(),['goods_id'=>'goods_id'])->viaTable(OrderDetail::tableName(),['id'=>'order_detail_id']);
    }

    public function getDetail(){
        return $this->hasOne(Goods::className(),['goods_id'=>'goods_id']);
    }


}