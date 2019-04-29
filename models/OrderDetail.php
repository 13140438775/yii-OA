<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 11:55:13
 */
namespace app\models;


class OrderDetail extends Base {
    public static function tableName()
    {
        return 't_order_detail';
    }
    public static function unavailable($orderId){
        self::updateAll(['is_available'=>UNAVAILABLE,'update_time'=>time()],['order_id'=>$orderId]);
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id'=>'goods_id']);
    }

    public function getSupplier(){
        return $this->hasOne(SupplierType::className(),['type_id'=>'type_id'])->select(['supplier_id','type_id','supplier_name','type_name']);
    }

    public function getGoodsSupplier(){
        return $this->hasOne(GoodsSupplier::className(),['goods_id'=>'goods_id'])->select(['supplier_id'])->where(['relation_status'=>AVAILABLE]);
    }

}