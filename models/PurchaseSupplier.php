<?php

namespace app\models;

use Yii;

class PurchaseSupplier extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_purchase_supplier';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getPurchaseSupplier($purchaseId){
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