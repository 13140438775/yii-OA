<?php

namespace app\models;

use Yii;

class Supplier extends Base
{


    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function tableName() {
        return 't_supplier';
    }

    public static function getSupplier(){
        $condition = [
            'supplier_status' =>AVAILABLE
        ];
        return self::find()
            ->select(['id','supplier_name','phone'])
            ->where($condition)
            ->asArray()
            ->all();
    }

    public static function getSupplierInfo($supplierId){
        $condition = [
            'supplier_status' =>AVAILABLE,
            'id'=>$supplierId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->one();
    }

    public static function getSupplierByName($supplierName){
        $condition = [
            'supplier_status' =>AVAILABLE,
            'supplier_name'=>$supplierName
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->one();
    }

    public static function checkSupplier($supplier_id,$supplierName){
        $condition = [
            'supplier_status' =>AVAILABLE,
            'supplier_name'=>$supplierName
        ];
        return self::find()
            ->where($condition)
            ->andWhere([
                '!=',
                'id',
                $supplier_id
            ])
            ->asArray()
            ->one();
    }

    public function getRegion(){
        $condition = [
            'relation_status'=>AVAILABLE
        ];
        return  $this->hasMany(SupplierArea::className(),['supplier_id'=>'id'])
            ->select(['supplier_id','area_id'])->where($condition);
    }

    public function getSupplierType(){
        $condition = [
            'relation_status' =>AVAILABLE
        ];
        return  $this->hasMany(SupplierType::className(),['supplier_id'=>'id'])
            ->select(['supplier_id','type_id'])->where($condition);
    }

    public function getSupplierAccount(){
        $condition = [
            'account_status' =>AVAILABLE
        ];
        return  $this->hasMany(SupplierAccount::className(),['supplier_id'=>'id'])
            ->select(['supplier_id','account_name','bank_name','bank_number'])->where($condition);
    }

    public function getRemark(){
        return $this->hasOne(Remark::className(),['object_id' => 'id'])
            ->select(['object_id','remark'])->orderBy('create_time');
    }
}