<?php

namespace app\models;

use Yii;

class WarehouseIn extends Base
{
    const CONFIRM_WAREHOUSE_IN = 1;
    const UPDATE_WAREHOUSE_IN = 2;
    const INITIAL_STATUS = 1;
    const ALREADT_WAREHOUSE_IN = 2;

    const NO_CLOSE_BALANCE = 0;
    const CLOSE_BALANCE = 1;

    const REAL_LIBRARY = 1;
    const VIRTUAL_LIBRARY = 2;

    const IS_ALL_WAREHOUSE_IN = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_warehouse_in';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getWarehouseInInfo($warehouseInId){
        $condition = [
            'warehouse_in_id'=>$warehouseInId
        ];
        return self::find()
            ->where($condition)
            ->one();
    }

    public function getWarehouseInDetail(){
        return $this->hasMany(WarehouseInDetail::className(),['warehouse_in_id' => 'warehouse_in_id'])
            ->select(['warehouse_in_id','goods_id','except_num','actual_num','is_all_warehouse_in']);
    }

    public function getSupplier(){
        return $this->hasOne(Supplier::className(),['id' => 'supplier_id'])
            ->select(['id','supplier_name','phone','contact_name']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name']);
    }

    public function getRemark(){
        return $this->hasMany(Remark::className(),['object_id' => 'warehouse_in_id'])
            ->select(['object_id','remark','create_time']);
    }

    public function getPurchase(){
        return $this->hasOne(Purchase::className(),['purchase_id' => 'purchase_id'])
            ->select(['purchase_id','purchase_time','order_id']);
    }

    public function getReturn(){
        return $this->hasOne(ReturnGoods::className(),['warehouse_in_id' => 'warehouse_in_id'])
            ->select(['warehouse_in_id','return_id'])->orderBy('create_time DESC');
    }
}