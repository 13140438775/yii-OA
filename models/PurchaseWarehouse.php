<?php

namespace app\models;

use Yii;

class PurchaseWarehouse extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_purchase_warehouse';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getPurchaseWarehouse($purchaseId){
        $condition = [
            'relation_status' =>AVAILABLE,
            'purchase_id'=>$purchaseId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->one();
    }
}