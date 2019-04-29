<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class GetGoods extends \yii\db\ActiveRecord{

    const INITIAL_STATUS = 1;
    const ALREADT_GET_GOODS= 2;

    public static function tableName()
    {
        return 't_get_goods';
    }

    public function getGetGoodsDetail(){
        return $this->hasMany(GetGoodsDetail::className(),['get_goods_id' => 'get_goods_id'])
            ->select(['get_goods_id','goods_id','request_goods_num','inventory']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name']);
    }

    public function getRemark(){
        return $this->hasOne(Remark::className(),['object_id' => 'get_goods_id'])
            ->select(['object_id','remark','create_time']);
    }

    public function getDepartment(){
        return $this->hasOne(Department::className(),['id' => 'department_id'])
            ->select(['id','name as department_name']);
    }
}