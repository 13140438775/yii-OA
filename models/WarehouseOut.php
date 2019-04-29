<?php

namespace app\models;

use Yii;

class WarehouseOut extends Base
{
    const CONFIRM_WAREHOUSE_OUT = 1;
    const UPDATE_WAREHOUSE_OUT = 2;
    const INITIAL_STATUS = 1;
    const ALREADT_WAREHOUSE_OUT = 2;

    const IS_WAREHOUSE_OUT_ALL = 1;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_warehouse_out';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getWarehouseOutInfo($warehouseOutId){
        $condition = [
            'warehouse_out_id'=>$warehouseOutId
        ];
        return self::find()
            ->where($condition)
            ->one();
    }

    public function getWarehouseOutDetail(){
        return $this->hasMany(WarehouseOutDetail::className(),['warehouse_out_id' => 'warehouse_out_id'])
            ->select(['warehouse_out_id','goods_id','except_out_num','actual_out_num','is_warehouse_out_all']);
    }

    public function getSupplier(){
        return $this->hasOne(Supplier::className(),['id' => 'supplier_id'])
            ->select(['id','supplier_name','phone','contact_name']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name','warehouse_type']);
    }

    public function getRemark(){
        return $this->hasMany(Remark::className(),['object_id' => 'warehouse_out_id'])
            ->select(['object_id','remark','create_time']);
    }

    public function getGym(){
        return $this->hasOne(OpenProject::className(),['id' => 'gym_id'])
            ->select(['id','gym_name','address']);
    }

    public function getOrder(){
        return $this->hasOne(Order::className(),['order_id' => 'order_id'])
            ->select(['order_id','create_time']);
    }
}