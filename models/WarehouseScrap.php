<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class WarehouseScrap extends \yii\db\ActiveRecord{

    public static function tableName()
    {
        return 't_scrap';
    }

    public function getScrapGoodsDetail(){
        return $this->hasMany(WarehouseScrapDetail::className(),['scrap_id' => 'scrap_id'])
            ->select(['scrap_id','goods_id','scrap_inventory_num','scrap_defective_num','inventory','defective_inventory']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name']);
    }

    public function getRemark(){
        return $this->hasOne(Remark::className(),['object_id' => 'scrap_id'])
            ->select(['object_id','remark','create_time']);
    }
}