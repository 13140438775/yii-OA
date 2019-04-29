<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/1 14:29:09
 */
namespace app\models;

use Yii;

class SupplierArea extends \yii\db\ActiveRecord{

    public static function tableName()
    {
        return 't_supplier_area';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getSupplierArea($supplierId){
        $condition = [
            'relation_status' =>AVAILABLE,
            'supplier_id'=>$supplierId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }

    public static function getSupplierAreaBySupplierIds($supplierIds){
        $condition = [
            'relation_status'=>AVAILABLE,
            'supplier_id'=>$supplierIds
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }

}