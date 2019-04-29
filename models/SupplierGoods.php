<?php

namespace app\models;

use Yii;

class SupplierGoods extends Base
{
    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function tableName() {
        return 't_supplier_goods';
    }
}