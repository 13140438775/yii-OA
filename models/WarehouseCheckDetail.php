<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class WarehouseCheckDetail extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 't_warehouse_check_detail';
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id' => 'goods_id'])
            ->select(['goods_id','goods_name','brand','model','unit','inventory','purchase_amount']);
    }
}