<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/1 14:29:09
 */
namespace app\models;

use Yii;

class Remark extends \yii\db\ActiveRecord{

    const WAREHOUSE = 1;
    const WAREHOUSE_IN = 2;
    const WAREHOUSE_OUT = 3;
    const GET_GOODS = 4;
    const RETURN_GOODS = 5;
    const CHECK_GOODS = 6;
    const SCRAP_GOODS = 7;
    const SUPPLIER_BALANCE_ACCOUNT = 8;
    const SUPPLIER = 9;
    const PURCHASE = 10;
    const ORDER_RECENT = 11;
    const GOODS = 12;
    const ORDER_CHARGE = 13;
    const ORDER_CHARGEBACK = 14;

    public static function tableName()
    {
        return 't_remark';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getObjectRemark($objectId,$objectType){
        $condition = [
            'remark_status' =>AVAILABLE,
            'object_id'=>$objectId,
            'object_type'=>$objectType
        ];
        return self::find()
            ->where($condition)
            ->orderBy(['create_time' => SORT_DESC])
            ->asArray()
            ->all();
    }

    public static function saveRemark($objectId,$objectType,$remark){
        $remarkModel = new Remark();
        $remarkModel->object_id = $objectId;
        $remarkModel->object_type = $objectType;
        $remarkModel->remark = $remark;
        $remarkModel->create_time = time();
        $remarkModel->update_time = time();
        $remarkModel->save();
        return $remarkModel;
    }

    public static function getRemarkOne($objectId){
        $condition = [
            'remark_status' =>AVAILABLE,
            'object_id'=>$objectId
        ];
        return self::find()
            ->where($condition)
            ->orderBy(['create_time' => SORT_DESC])
            ->one();
    }

}