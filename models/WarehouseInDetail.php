<?php

namespace app\models;

use Yii;

class WarehouseInDetail extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_warehouse_in_detail';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public function getWarehouseInDetailInfo($warehouseInId,$goodsId){
        return self::find()
            ->where(['warehouse_in_id'=>$warehouseInId,'goods_id'=>$goodsId])
            ->one();
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id' => 'goods_id'])
            ->select(['goods_id','goods_name','brand','model','unit']);
    }

    public function getWarehouseIn(){
        return $this->hasOne(WarehouseIn::className(),['warehouse_in_id' => 'warehouse_in_id'])
            ->select(['warehouse_in_id','in_time','purchase_id','warehouse_status']);
    }

    public static function getWarehouseInDetailGoods($warehouseInId){
        return self::find()
            ->where(['warehouse_in_id'=>$warehouseInId])
            ->asArray()
            ->all();
    }
}