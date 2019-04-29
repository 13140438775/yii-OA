<?php

namespace app\models;

use Yii;

class PurchaseDetail extends Base
{
    const PURCHSE_LOADING = 1;
    const DELIVERY_GOODS_LOADING = 2;
    const PURCHSE_COMOLETE = 3;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_purchase_detail';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getPurchaseDetail($purchaseId){
        $condition = [
            'relation_status' =>AVAILABLE,
            'purchase_id'=>$purchaseId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id' => 'goods_id'])
            ->select(['goods_id','goods_name','brand','model','unit','inventory','purchase_amount']);
    }
}