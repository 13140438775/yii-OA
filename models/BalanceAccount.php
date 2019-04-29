<?php

namespace app\models;

use Yii;

class BalanceAccount extends Base
{
    const INITIAL_STATUS = 1;
    const ALREADT_SUBMIT = 2;
    const FINANCE_UNSUBMIT = 3;
    const ALREADY_SHUT_DOWN = 4;
    const ALREADY_CLOSE = 5;

    const INITIAL_STATUS_NAME = '初始状态';
    const ALREADT_SUBMIT_NAME = '已提交';
    const FINANCE_UNSUBMIT_NAME = '财务驳回';
    const ALREADY_SHUT_DOWN_NAME = '已关账';
    const ALREADY_CLOSE_NAME = '已关闭';

    const WAITING_AGREE = '待审核';
    const ALREADT_UNAGREE = '已驳回';
    const FINANCE_OPEARTE = '2';

    const WAREHOUSE_IN= 1;
    const WAREHOUSE_OUT = 2;
    const WAREHOUSE_IN_NAME= '入库';
    const WAREHOUSE_OUT_NAME = '出库';

    public static function tableName()
    {
        return 't_balance_account';
    }

    public function getBalanceAccountDetail(){
        return $this->hasMany(BalanceAccountDetail::className(),['balance_id' => 'balance_id'])
            ->select(['balance_id','number','number_type','number_time','total_num','total_account','supplier_id','warehouse_id'])
            ->orderBy('number_time');
    }

    public static function getBalanceAccountInfo($balanceId){
        $condition = [
            'balance_id'=>$balanceId
        ];
        return self::find()
            ->where($condition)
            ->one();
    }

    public function getRemark(){
        return $this->hasMany(Remark::className(), ['object_id' => 'balance_id'])
            ->select(['object_id','id as remark_id','remark','create_time'])
            ->orderBy('create_time DESC');
    }
}
