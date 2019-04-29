<?php

namespace app\models;

use Yii;

class PurchaseType extends Base
{
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_purchase_type';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }
}