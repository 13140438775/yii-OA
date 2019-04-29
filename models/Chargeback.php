<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/13 16:08:33
 */
namespace app\models;

class Chargeback extends Base{

    const REFUND_FINANCE_STATUS = 1;
    const REFUND_FINANCE_BACK = 2;
    const REFUND_FINANCE_COMPLETE = 3;

    const REFUND_FINANCE_STATUS_NAME = '待退款';
    const REFUND_FINANCE_BACK_NAME = '退款驳回';
    const REFUND_FINANCE_COMPLETE_NAME = '已退款';

    const REFUND_PURCHASE_STATUS = 1;
    const REFUND_PURCHASE_BACK = 2;
    const REFUND_PURCHASE_COMPLETE = 3;

    const REFUND_PURCHASE_STATUS_NAME = '初始状态';
    const REFUND_PURCHASE_BACK_NAME = '已驳回';
    const REFUND_PURCHASE_COMPLETE_NAME = '已入库';

    const UN_SUBMIT = 1;
    const CHARGE_BACK_LOADING = 2;
    const UN_CHARGE_BACK = 3;
    const FINANCE_MONEY = 4;
    const FINANCE_MONEY_BACK = 5;
    const FINANCE_MONEY_LOADING = 6;
    const IS_FINISF = 7;
    const IS_CLOSE = 8;
    const IS_CANCEL = 9;

    const UN_SUBMIT_NAME = '未提交';
    const CHARGE_BACK_LOADING_NAME = '退货中';
    const UN_CHARGE_BACK_NAME = '退货驳回';
    const FINANCE_MONEY_NAME = '财务打款';
    const FINANCE_MONEY_BACK_NAME = '退款驳回';
    const FINANCE_MONEY_LOADING_NAME = '退款中';
    const IS_FINISF_NAME = '已完成';
    const IS_CLOSE_NAME = '已关闭';
    const IS_CANCEL_NAME = '已取消';

    public static function tableName()
    {
        return 't_chargeback';
    }

    public static function unavailable($chargebackId){
        self::updateAll(['is_available'=>UNAVAILABLE],['chargeback_id'=>$chargebackId]);
    }

    public function getOrder(){
        return $this->hasOne(Order::className(),['order_id'=>'order_id'])->select(['gym_id','gym_name','contact_name','contact_phone']);
    }

    public static function updateStatus($attributes,$chargebackId){
        self::updateAll($attributes,['chargeback_id'=>$chargebackId]);
    }

    public  function getDetail(){
        return $this->hasMany(ChargebackDetail::className(),['chargeback_id'=>'chargeback_id']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(),['warehouse_id'=>'warehouse_id']);
    }

    public static function convert2string(&$results, $labels = [])
    {
        $labels["update_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        $labels["create_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        $labels["order_time"] = function ($val) {
            if (!empty($val)){
                return date("Y-m-d H:i:s", $val);
            }
        };
        $labels["chargeback_time"] = function ($val) {
            if (!empty($val)){
                return date("Y-m-d H:i:s", $val);
            }
        };
        parent::convert2string($results, $labels);
    }



}