<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/1 14:29:09
 */
namespace app\models;

use Yii;

class SupplierType extends \yii\db\ActiveRecord{

    public static function tableName()
    {
        return 't_supplier_type';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getSupplierType($supplierId){
        $condition = [
            'relation_status' =>AVAILABLE,
            'supplier_id'=>$supplierId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }

}