<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 11:55:13
 */
namespace app\models;

use app\services\GoodsService;
use yii\db\ActiveRecord;

class Order extends Base {

    const PURCHASE_LOADING = 5;
    const SHIPPING_LOADING = 6;

    const FIN_STATUS_WAIT = 1;
    const FIN_STATUS_BACK = 2;
    const FIN_STATUS_PASS = 3;

    const FIN_STATUS_WAIT_NAME = '待审核';
    const FIN_STATUS_BACK_NAME = '审核驳回';
    const FIN_STATUS_PASS_NAME = '审核通过';

    const FINNISH_ORDER = 2;

    const UN_SUBMIT = 1;
    const IS_ALREADY = 2;
    const SUB_FINANCE = 3;
    const FINANCE_RETURN = 4;
    const IS_CLOSE = 7;

    const UN_SUBMIT_NAME = '未提交';
    const IS_ALREADY_NAME = '已完成';
    const SUB_FINANCE_NAME = '财务审核';
    const FINANCE_RETURN_NAME = '审核驳回';
    const PURCHASE_LOADING_NAME = '采购中';
    const SHIPPING_LOADING_NAME = '发货中';
    const IS_CLOSE_NAME = '已关闭';

    public static function tableName()
    {
        return 't_order';
    }

    public static function updateStatus($orderId,$attributes){

        self::updateAll($attributes,['order_id'=>$orderId]);
    }

    public static function getOne($orderId){
        return self::findOne(['order_id'=>$orderId]);
    }

    public function getGym(){
        return $this->hasOne(OpenProject::className(),['id'=>'gym_id']);
    }

    public function getGoods(){
        return $this->hasMany(OrderDetail::className(),['order_id'=>'order_id'])
            ->select(['t_goods.*',"t_order_detail.*",'t_order_detail.id AS order_detail_id'])
            ->joinWith('goods',false)
            ->where([OrderDetail::tableName().'.is_available'=>AVAILABLE]);
    }
    public function getPays(){
        return $this->hasMany(OrderPayInfo::className(),['order_id'=>'order_id'])->where(['pay_status'=>AVAILABLE]);
    }

    public function getLog(){
        return $this->hasMany(OrderLog::className(),['order_id'=>'order_id']);
    }

    public function getPurchase(){
        return $this->hasMany(PurchaseDetail::className(),['purchase_id'=>'purchase_id'])
            ->viaTable(Purchase::tableName(),['order_id'=>'order_id'])
            ->joinWith('goods');
    }

    public function getOut(){
        return $this->hasMany(WarehouseOutDetail::className(),['warehouse_out_id'=>'warehouse_out_id'])
            ->viaTable(WarehouseOut::tableName(),['order_id'=>'order_id'])
            ->joinWith('goods');
    }

    public function getDetail(){
        return $this->hasMany(OrderDetail::className(),['order_id'=>'order_id']);
    }

    public function getWarehouseOut(){
        return $this->hasMany(WarehouseOut::className(),['order_id'=>'order_id']);
    }

    public static function convert2string(&$results, $labels = [])
    {
        $labels["update_time"] = function ($val) {
            if ($val){
                return date("Y-m-d H:i:s", $val);
            }

        };
        parent::convert2string($results, $labels);
    }



}