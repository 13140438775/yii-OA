<?php

namespace app\models;

use Yii;

class WarehouseOutDetail extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_warehouse_out_detail';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id' => 'goods_id'])
            ->select(['goods_id','goods_name','brand','model','unit','inventory']);
    }

    public static function getWarehouseOutDetailGoods($warehouseOutId){
        return self::find()
            ->where(['warehouse_out_id'=>$warehouseOutId])
            ->asArray()
            ->all();
    }
}