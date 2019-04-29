<?php

namespace app\models;

use Yii;

class BalanceAccountDetail extends Base
{
    public static function tableName()
    {
        return 't_balance_account_detail';
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name']);
    }

    public function getSupplier(){
        return $this->hasOne(Supplier::className(),['id' => 'supplier_id'])
            ->select(['id','supplier_name']);
    }

    public static function getBalanceAccountDetail($balanceId){
        $condition = [
            'balance_id'=>$balanceId
        ];
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }
}
