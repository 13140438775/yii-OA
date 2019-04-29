<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/1 11:19:15
 */

namespace app\models;

class GoodsSupplier extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 't_goods_supplier';
    }
    public function getGoods(){
      return  $this->hasMany(Goods::className(),['goods_id'=>'goods_id']);
    }

    public static function unavailable($goodsId){
        self::updateAll(['relation_status'=>UNAVAILABLE,'update_time'=>time()],['goods_id'=>$goodsId]);
    }



}