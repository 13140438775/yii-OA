<?php

namespace app\models;

use Yii;

class Warehouse extends Base
{

    const warehouseIn = 1;
    const warehouseReturn = 2;
    const REAL_LIBRARY = 1;
    const VIRTUAL_LIBRARY = 2;

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function tableName() {
        return 't_warehouse';
    }

    public static function getWarehouse(){
        $condition = [
            'warehouse_status' =>AVAILABLE
        ];
        return self::find()
            ->select(['warehouse_id','warehouse_name','address','warehouse_type'])
            ->where($condition)
            ->asArray()
            ->all();
    }

    public static function getWarehouseById($warehouseId){
        $condition = [
            'warehouse_status' =>AVAILABLE,
            'warehouse_id'=>$warehouseId
        ];
        return self::find()
            ->select(['id as warehouse_id','warehouse_name','address','warehouse_type'])
            ->where($condition)
            ->asArray()
            ->one();
    }

    public static function getGoodsList($goodsIds){
        return Goods::find()
            ->select(['goods_id','inventory'])
            ->where(['goods_id'=>$goodsIds])
            ->asArray()
            ->all();
    }

    public static function getGoodsListByWarehouseId($warehouseId,$goodsIds){
        return WarehouseGoods::find()
            ->select(['warehouse_id','goods_id','inventory'])
            ->where(['warehouse_id'=>$warehouseId,'goods_id'=>$goodsIds])
            ->asArray()
            ->all();
    }

    public function getRemark(){
        return $this->hasOne(Remark::className(),['object_id' => 'warehouse_id'])
            ->select(['object_id','remark','create_time']);
    }

    public static function getWarehouseOne($warehouseId){
        $condition = [
            'warehouse_id'=>$warehouseId,
            'warehouse_status' =>AVAILABLE
        ];
        return self::find()
            ->where($condition)
            ->one();
    }
}