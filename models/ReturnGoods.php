<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class ReturnGoods extends \yii\db\ActiveRecord{

    const INITIAL_STATUS = 1;
    const ALREADT_RETURN_GOODS= 2;

    const NO_CLOSE_BALANCE = 0;
    const CLOSE_BALANCE = 1;

    public static function tableName()
    {
        return 't_return';
    }

    public static function getReturnInfo($warehouseInId){
        $condition = [
            'warehouse_in_id'=>$warehouseInId
        ];
        return self::find()
            ->where($condition)
            ->orderBy('create_time DESC')
            ->one();
    }

    public static function getAllReturn($returnIds){
        $condition = [
            'return_id'=>$returnIds
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }


    public function getReturnGoodsDetail(){
        return $this->hasMany(ReturnGoodsDetail::className(),['return_id' => 'return_id'])
            ->select(['return_id','goods_id','return_inventory_num','return_defective_numCopy','inventory','defective_inventory','warehouse_in_num']);
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
        return $this->hasOne(Remark::className(),['object_id' => 'return_id'])
            ->select(['object_id','remark','create_time']);
    }
}