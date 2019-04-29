<?php

namespace app\models;

use Yii;

class WarehouseGoods extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_warehouse_goods';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getWarehouseGoodsInfo($warehouseId,$goodsId){
        $condition = [
            'warehouse_id'=>$warehouseId,
            'goods_id'=>$goodsId
        ];
        return self::find()
            ->where($condition)
            ->one();
    }

    public static function getWarehouseGoodsInfos($warehouseId,$goodsIds){
        $condition = [
            'warehouse_id'=>$warehouseId,
            'goods_id'=>$goodsIds
        ];
        return self::find()
            ->where($condition)
            ->indexBy('goods_id')
            ->asArray()
            ->all();
    }

    public function getImg(){
        $condition = [
            'img_status'=>AVAILABLE
        ];
        return  $this->hasMany(GoodsImg::className(),['goods_id'=>'goods_id'])
            ->select(['id AS img_id','goods_img','goods_id'])->where($condition);
    }


}