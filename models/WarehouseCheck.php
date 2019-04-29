<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class WarehouseCheck extends \yii\db\ActiveRecord{

    public static function tableName()
    {
        return 't_warehouse_check';
    }

    public static function getFirstCheck(){
        return self::find()
            ->orderBy([
               'create_time' => SORT_DESC
            ])
            ->asArray()
            ->one();
    }

    public function getCheckGoodsDetail(){
        return $this->hasMany(WarehouseCheckDetail::className(),['check_id' => 'check_id'])
            ->select(['check_id','goods_id','inventory','check_inventory','defective','check_defective']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name']);
    }

    public function getRemark(){
        return $this->hasOne(Remark::className(),['object_id' => 'check_id'])
            ->select(['object_id','remark','create_time']);
    }
}