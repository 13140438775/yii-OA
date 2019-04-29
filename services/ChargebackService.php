<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/13 16:16:34
 */

namespace app\services;

use app\controllers\FinanceController;
use app\exceptions\ChargebackException;
use app\models\Base;
use app\models\Chargeback;
use app\models\ChargebackAction;
use app\models\ChargebackDetail;
use app\models\Goods;
use app\models\Message;
use app\models\Order;
use app\models\OrderAction;
use app\models\OrderDetail;
use app\models\Roles;
use app\models\SaveRemark;
use app\models\Warehouse;
use app\models\WarehouseGoods;
use yii\base\Action;
use yii\helpers\ArrayHelper;

class ChargebackService
{
    const   PAGE = 1;
    const   PAGESIZE = 15;
    const   PUR_STATUS = 1;     // 采购可查看退单
    const   FIN_STATUS = 1;     // 财务可查看退单
    const   REFUND_ZERO = 0;   //退货数量为0

    const   FINANCE = 2;    //财务
    const   PUR = 3;        //采购

    const   ORIGIN = 1;     //初始状态
    const   REFUND = 2;     //驳回
    const   CONFIRM = 3;    //确认

    const   WAIT = 1;       //未提交
    const   PUR_ING = 2;     //退货中
    const   PUR_RETURN = 3;     //退货驳回
    const   FIN_CHECK = 4;    //财务打款
    const   FIN_RETURN = 5;     //财务退款驳回
    const   FIN_ING = 6;    //退款中
    const   COMPLETE = 7;    //已完成
    const   CLOSE = 8;   //已关闭
    const   CANCEL = 9;    //已取消


    public static function insertChargeback($params, $status,$chargeTime=null)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $orderStatus = Order::find()->where(['order_id'=>$params['order_id'],'order_status'=>CustomerOrderService::COMPLETE,'is_available'=>AVAILABLE])->asArray()->one();
            if (empty($orderStatus)){
                throw  new ChargebackException(ChargebackException::ORDER_STATUS_COMMIT);
            }
            $chargebackId = empty($params['chargeback_id'])?generateIntId("TH"):$params['chargeback_id'];
            $backModel = Chargeback::findOne(['chargeback_id' => $chargebackId]);
            if (empty($backModel)) {
                $backModel = new Chargeback();
                self::insertDetail($chargebackId, $params['order_id'], $params['detail']);//新增
            }else{
                foreach ($params['detail'] as $detail){
                    $att  = ['refund_num' => $detail['refund_num'],'update_time' => time()];
                    ChargebackDetail::updateNum($att,$detail['id']);
                }
            }
            $attributes = array(
                'order_id' => $params['order_id'],
                'chargeback_id' => $chargebackId,
                'create_time' => time(),
                'update_time' => time(),
                'chargeback_time' => empty($chargeTime) ? 0 : $chargeTime,
                'chargeback_status' => $status,
                'refund_pur_status' => ArrayHelper::getValue($params, 'refund_pur_status', 0),
                'refund_name' => ArrayHelper::getValue($params, 'refund_name', ''),
                'refund_account' => ArrayHelper::getValue($params, 'refund_account', ''),
                'refund_amount' => ArrayHelper::getValue($params, 'refund_amount', '') * 100,
                'remark' => empty($params['remark'])?'':$params['remark'],
                'operator_id' =>\Yii::$app->user->getIdentity()->id,
                'operator_name' =>\Yii::$app->user->getIdentity()->name,
                'last_operator_id' =>\Yii::$app->user->getIdentity()->id,
                'last_operator_name' =>\Yii::$app->user->getIdentity()->name,
            );
            $backModel->setAttributes($attributes, false);
            $backModel->save();
            if (!empty($chargeTime)){
                self::commitUpdateGoods($chargebackId);
            }
            if (isset($params['remark']) && !empty($params['remark'])){
                $remark = new SaveRemark();
                $remark->saveRemark($chargebackId,$params['remark'],SaveRemark::CHARGE_SAVE);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }

    }

    public static function insertDetail($chargebackId, $orderId, $details)
    {
        ChargebackDetail::unavailable($chargebackId);
        $model = new ChargebackDetail();
        $data = array();
        foreach ($details as $detail) {
            $data[] = array(
                'chargeback_id' => $chargebackId,
                'order_id' => $orderId,
                'goods_id' => $detail['goods_id'],
                'goods_name' => $detail['goods_name'],
                'refund_num' => $detail['refund_num'],
                'refund_amount' => ArrayHelper::getValue($detail, 'refund_amount', 0) * 100,
                'order_detail_id' => $detail['order_detail_id'],
                'goods_num' => $detail['goods_num'],
                'goods_amount' => $detail['goods_amount'] * 100,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        return $model->batchInsert($data);
    }

    //提交退单时去除退货为0的商品
    public static function commitUpdateGoods($chargebackId){
        ChargebackDetail::updateAll(['detail_status'=>UNAVAILABLE,'update_time'=>time()],['chargeback_id'=>$chargebackId,'refund_num'=>self::REFUND_ZERO]);
    }

    public static function getList($params,$export=false)
    {
        $condition = ['AND'];
        if (isset($params['pur_status']) && $params['pur_status'] == self::PUR_STATUS) {
            $condition[] = ['>=', Chargeback::tableName() . '.refund_pur_status', self::PUR_STATUS];
            $condition[] = ['NOT',[Chargeback::tableName() . '.chargeback_status'=>[self::CLOSE,self::CANCEL]]];
        }
        if (isset($params['fin_status']) && $params['fin_status'] == self::PUR_STATUS) {
            $condition[] = ['>=', Chargeback::tableName() . '.refund_finance_status', self::FIN_STATUS];
            $condition[] = ['NOT',[Chargeback::tableName() . '.chargeback_status'=>[self::CLOSE,self::CANCEL]]];
        }
        if (!empty($params['search'])) {
            $search = ['OR'];
            $search[] = ['LIKE', Chargeback::tableName() . '.chargeback_id', $params['search']];
            $search[] = ['LIKE', Order::tableName() . '.gym_name', $params['search']];
            $condition[] = $search;
        }
        if (!empty($params['start_time']) && isset($params['start_time'])) {
            $condition[] = ['>=', Chargeback::tableName().".chargeback_time", $params['start_time']];
        }
        if (!empty($params['end_time']) && isset($params['end_time'])) {
            $condition[] = ['<=', Chargeback::tableName().".chargeback_time", $params['end_time']];
        }
        if (!empty($params['gym_name']) && isset($params['gym_name'])) {
            $condition[] = ['LIKE', Order::tableName() . '.gym_name', $params['gym_name']];
        }
        if (!empty($params['contact_name']) && isset($params['contact_name'])) {
            $condition[] = ['LIKE', Order::tableName() . '.contact_name', $params['contact_name']];
        }
        if (!empty($params['order_id']) && isset($params['order_id'])) {
            $condition[] = [ Chargeback::tableName() . '.order_id'=> $params['order_id']];
        }
        if (!empty($params['chargeback_id']) && isset($params['chargeback_id'])) {
            $condition[] = [ Chargeback::tableName() . '.chargeback_id'=> $params['chargeback_id']];
        }
        if (!empty($params['chargeback_status']) && isset($params['chargeback_status'])) {
            $condition[] = [ Chargeback::tableName() . '.chargeback_status'=> $params['chargeback_status']];
        }
        if (!empty($params['refund_finance_status']) && isset($params['refund_finance_status'])) {
            $condition[] = [ Chargeback::tableName() . '.refund_finance_status'=> $params['refund_finance_status']];
        }
        if (!empty($params['refund_pur_status']) && isset($params['refund_pur_status'])) {
            $condition[] = [ Chargeback::tableName() . '.refund_pur_status'=> $params['refund_pur_status']];
        }
        if (!empty($params['operator_name']) && isset($params['operator_name'])) {
            $condition[] = ['LIKE', Chargeback::tableName() . '.operator_name', $params['operator_name']];
        }
        if (!empty($params['last_operator_name']) && isset($params['last_operator_name'])) {
            $condition[] = [ 'LIKE',Chargeback::tableName() . '.last_operator_name', $params['last_operator_name']];
        }

        $model = new Chargeback(
            [
                'select' =>[
                    [
                        Chargeback::tableName().".*",
                        Order::tableName().".gym_id",Order::tableName().".gym_name",Order::tableName().".contact_name",Order::tableName().".order_time"
                    ]
                ],
                'orderBy' => [[Chargeback::tableName().".id" => SORT_DESC]]
            ]
        );
        if ($export){
            return $model->getList($condition,[['order'],false]);
        }
        $page = ArrayHelper::getValue($params, 'page', self::PAGE);
        $pageSize = ArrayHelper::getValue($params, 'page_size', self::PAGESIZE);
        $result = $model->paginate($page, $pageSize,[['order'],false] , $condition);
        $labels = [
            'chargeback_status'=>'purchase.chargeback_status',
            'refund_pur_status'=>'purchase.refund_pur_status',
            'refund_finance_status' => 'purchase.refund_finance_status'
        ];
        Chargeback::convert2string($result['rows'],$labels);
        return $result;
    }

    public static function getDetail($chargebackId)
    {
        $backTable = Chargeback::tableName();
        $detailTable = ChargebackDetail::tableName();
        $orderTable = Order::tableName();
        $goodsTable = Goods::tableName();
        $warehouse = Warehouse::tableName();
        $condition = array(
            $detailTable . '.chargeback_id' => $chargebackId,
            $detailTable . '.detail_status' => AVAILABLE

        );
        //['chargeback_id' => $chargebackId]
        $charge = Chargeback::find()
            ->select([
                $backTable.'.chargeback_id',$backTable.'.order_id',$backTable.'.chargeback_time',$backTable.'.refund_name',$backTable.'.warehouse_id',
                $backTable.'.refund_amount',$backTable.'.refund_account',$backTable.'.warehouse_in_time',
                $orderTable.'.gym_id',$orderTable.'.gym_name',$orderTable.'.order_time',
                $warehouse.'.warehouse_id',$warehouse.'.warehouse_name',
            ])
            ->joinWith(['order','warehouse'],false)
            ->where(['chargeback_id' => $chargebackId])
            ->asArray()
            ->all();

        $detail = ChargebackDetail::find()
            ->select([$detailTable . '.id', $detailTable . '.chargeback_id', $detailTable . '.goods_id', $detailTable . '.goods_num',$detailTable . '.inventory',$detailTable . '.defective_inventory',
                $detailTable . '.goods_amount', $detailTable . '.refund_num', $detailTable . '.refund_amount',$detailTable.'.order_detail_id',
                $goodsTable . '.goods_id', $goodsTable . '.goods_name', $goodsTable . '.model', $goodsTable . '.weight', $goodsTable . '.param',
                $goodsTable . '.price', $goodsTable . '.unit', $goodsTable . '.brand'
            ])
            ->leftJoin($goodsTable, $goodsTable . '.goods_id = ' . $detailTable . '.goods_id')
            ->where($condition)
            ->asArray()
            ->all();
        $labels["chargeback_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        Base::convert2string($charge,$labels);
        $charge = $charge[0];
        $charge['warehouse_id'] = empty($charge['warehouse_id'])?'':$charge['warehouse_id'];
        $charge['warehouse_name'] = empty($charge['warehouse_name'])?'':$charge['warehouse_name'];
        $result = array(
            'chargeback' => $charge,
            'detail' => $detail
        );
        return $result;
    }

    public static function commit($params)
    {
        $params['refund_pur_status'] = self::ORIGIN;                //采购审核为初始状态
        self::insertChargeback($params, self::PUR_ING,time());       //更新退单状态到退货中
    }


    public static function updateStatus($params, $type, $status)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $chargebackId = $params['chargeback_id'];
            $params['relation_id'] = $chargebackId;
            $params['check_status'] = $status;
            //更新退单表数据状态
            if ($type == self::PUR) {   //采购审核
                if ($status == self::REFUND) {      //采购驳回
                    Chargeback::updateStatus(['refund_pur_status' => $status, 'chargeback_status' => self::PUR_RETURN], $chargebackId);
                    FinanceService::orderAction($params, $type);
                    self::message($chargebackId,'退单被采购驳回','退单被采购驳回，请及时处理');
                } elseif ($status == self::CONFIRM) {    //采购通过
                    FinanceService::orderAction($params, $type);
                    Chargeback::updateStatus(['refund_pur_status' => $status, 'refund_finance_status' => self::WAIT, 'chargeback_status' => self::FIN_CHECK,
                        'warehouse_in_time'=>$params['warehouse_in_time'],'warehouse_id' => $params['warehouse_id'],],
                        $chargebackId);
                    self::purPass($params);
                }
            } elseif ($type == self::FINANCE) {       //财务审核
                if ($status == self::REFUND) {      //财务驳回
                    Chargeback::updateStatus(['refund_finance_status' => $status, 'chargeback_status' => self::FIN_RETURN], $chargebackId);
                    FinanceService::orderAction($params, $type);
                    self::message($chargebackId,'退单被财务驳回','退单被财务驳回，请及时处理');
                } elseif ($status == self::CONFIRM) {    //财务通过
                    FinanceService::orderAction($params, $type);
                    Chargeback::updateStatus(['refund_finance_status' => self::CONFIRM, 'chargeback_status' => self::FIN_ING], $chargebackId);
                }
            }
            $transaction->commit();
        }catch (\Exception $e) {
            throw $e;
            $transaction->rollBack();
        }
    }

    public static function message($relationId,$title,$content){
        $manage = \Yii::$app->getAuthManager()->getUserIdsByRole(Roles::CUSTOMER_MANAGER);
        $specialist = \Yii::$app->getAuthManager()->getUserIdsByRole(Roles::CUSTOMER_SPECIALIST);
        $staff = array_merge($manage,$specialist);
        if (!empty($staff)){
            foreach ($staff as $id) {
                $message[] = [
                    'staff_id' => $id,
                    'title' => $title,
                    'content' => $relationId . $content,
                    'message_type' => Message::PURCHASE,
                ];
            }
            $model = new Message();
            $model->batchInsert($message);
        }
    }

    //采购确认通过退货单更新库存
    public static function purPass($param){
        $detail = $param['detail'];
        $warehouseId = $param['warehouse_id'];
        foreach ($detail as $refund){
            ChargebackDetail::updateAll(['inventory'=>$refund['inventory'],'defective_inventory' => $refund['defective_inventory']],['id'=>$refund['id']]);
            Goods::updateAllCounters(['inventory'=>$refund['inventory']],['goods_id'=>$refund['goods_id']]);
            $model = WarehouseGoods::find()->where(['warehouse_id'=>$warehouseId,'goods_id'=>$refund['goods_id']])->asArray()->one();
            if (empty($model)){
                $model = new WarehouseGoods();
                $attributes = [
                    'warehouse_id' => $warehouseId,
                    'goods_id' => $refund['goods_id'],
                    'inventory' => $refund['inventory'],
                    'defective_inventory' => $refund['defective_inventory'],
                    'create_time' => time(),
                    'update_time' => time(),
                ];
                $model->setAttributes($attributes, false);
                $model->save();
            }else{
                WarehouseGoods::updateAllCounters(['inventory'=>$refund['inventory'],'defective_inventory'=>$refund['defective_inventory']],['goods_id'=>$refund['goods_id'],'warehouse_id' => $warehouseId]);
            }

        }
    }

    public static function orderStatus($chargebackId)
    {
        $param = ['relation_id' => $chargebackId];
        $action = FinanceService::checkList($param, FinanceController::REFUND, false);
        foreach ($action['rows'] as &$row){
            $row['type_label'] = OrderAction::ORDER_TYPE[$row['order_type']];
            $row['status_label'] = OrderAction::STATUS_ACTION[$row['check_status']];
        }
        return $action['rows'];
    }


    public static function purChargeDetail($param)
    {
        $chargeTable = Chargeback::tableName();
        $detailTable = ChargebackDetail::tableName();
        $orderTable = Order::tableName();
        $condition = ['AND'];

        if (!empty($param['chargeback_id'])) {
            $condition[] = [$chargeTable . '.chargeback_id' => $param['chargeback_id']];
        }
        if (!empty($param['order_id'])) {
            $condition[] = [$orderTable . '.chargeback_id' => $param['chargeback_id']];
        }
        if (!empty($param['refund_pur_status'])) {
            $condition[] = [$chargeTable . '.refund_pur_status' => $param['refund_pur_status']];
        }
        if (!empty($param['charge_start_time'])) {
            $condition[] = ['>=', $chargeTable . '.chargeback_time', $param['charge_start_time']];
        }
        if (!empty($param['charge_end_time'])) {
            $condition[] = ['<=', $chargeTable . '.chargeback_time', $param['charge_end_time']];
        }
        if (!empty($param['order_start_time'])) {
            $condition[] = ['>=', $orderTable . '.order_time', $param['order_start_time']];
        }
        if (!empty($param['order_end_time'])) {
            $condition[] = ['<=', $orderTable . '.order_time', $param['order_end_time']];
        }
        if (!empty($param['goods_name'])){
            $condition[] = ['LIKE',$detailTable.'.goods_name',$param['goods_name']];
        }
        if (!empty($param['gym_name'])){
            $condition[] = ['LIKE',$orderTable.'.gym_name',$param['gym_name']];
        }
        if (!empty($param['search'])){
            $search = ['OR'];
            $search[] = ['LIKE', $detailTable . '.goods_name', $param['search']];
            $search[] = ['LIKE', $orderTable . '.gym_name', $param['search']];
            $condition[] = $search;
        }

        $model = new Chargeback(['select' => [
            [
                $chargeTable . '.chargeback_id', $chargeTable . '.order_id', $chargeTable . '.chargeback_time', $chargeTable . '.operator_id',$chargeTable . '.chargeback_status',
                $chargeTable . '.operator_name', $chargeTable . '.refund_pur_status',$chargeTable . '.warehouse_in_time',$chargeTable . '.remark',
                $detailTable . '.goods_id', $detailTable . '.goods_name', $detailTable . '.refund_num',
                $orderTable . '.gym_id', $orderTable . '.gym_name', $orderTable . '.order_time'
            ]
        ]
        ]);
        $page = ArrayHelper::getValue($param, 'page', PAGE);
        $pageSize = ArrayHelper::getValue($param, 'page_size', PAGESIZE);
        $result = $model->paginate($page, $pageSize, [['detail', 'order'], false], $condition);
        $labels["order_time"] = function ($val) {
            if (empty($val)){
                return '';
            }
            return date("Y-m-d H:i:s", $val);
        };
        $labels["chargeback_time"] = function ($val) {
            if (empty($val)){
                return '';
            }
            return date("Y-m-d H:i:s", $val);
        };
        $labels["warehouse_in_time"] = function ($val) {
            if (empty($val)){
                return '';
            }
            return date("Y-m-d H:i:s", $val);
        };
        $labels['refund_pur_status'] = 'purchase.refund_pur_status';
        $labels['chargeback_status'] = 'purchase.chargeback_status';
        Base::convert2string($result['rows'],$labels);
        return $result;
    }


    public static function updateRefund($param){
        $attributes = [
            'refund_name' => $param['refund_name'],
            'refund_account' => $param['refund_account'],
            'refund_finance_status' => self::WAIT,
            'chargeback_status' => self::FIN_CHECK
        ];
        return Chargeback::updateStatus($attributes,$param['chargeback_id']);

    }

    public static function getOrderDetail($orderId){
        $orderStatus = Order::find()->where(['order_id'=>$orderId,'order_status'=>CustomerOrderService::COMPLETE,'is_available'=>AVAILABLE])->asArray()->one();
        if (empty($orderStatus)){
            throw  new ChargebackException(ChargebackException::ORDER_STATUS_COMMIT);
        }
        return CustomerOrderService::getOrderDetail($orderId);
    }

    //获取退单所有状态
    public static function getChargeStatus(){
        $status = \Yii::$app->params['purchase']['chargeback_status'];
        $data = [];
        foreach ($status as $k => $val){
            $data[] = array(
                'value' => $k,
                'label' => $val
            );
        }
        return $data;
    }

    public static function getOrderType($orderType){
        switch ($orderType){
            case Chargeback::UN_SUBMIT:{
                $orderType = Chargeback::UN_SUBMIT_NAME;
                break;
            }
            case Chargeback::CHARGE_BACK_LOADING:{
                $orderType = Chargeback::CHARGE_BACK_LOADING_NAME;
                break;
            }
            case Chargeback::UN_CHARGE_BACK:{
                $orderType = Chargeback::UN_CHARGE_BACK_NAME;
                break;
            }
            case Chargeback::FINANCE_MONEY:{
                $orderType = Chargeback::FINANCE_MONEY_NAME;
                break;
            }
            case Chargeback::FINANCE_MONEY_BACK:{
                $orderType = Chargeback::FINANCE_MONEY_BACK_NAME;
                break;
            }
            case Chargeback::FINANCE_MONEY_LOADING:{
                $orderType = Chargeback::FINANCE_MONEY_LOADING_NAME;
                break;
            }
            case Chargeback::IS_FINISF:{
                $orderType = Chargeback::IS_FINISF_NAME;
                break;
            }
            case Chargeback::IS_CLOSE:{
                $orderType = Chargeback::IS_CLOSE_NAME;
                break;
            }
            case Chargeback::IS_CANCEL:{
                $orderType = Chargeback::IS_CANCEL_NAME;
                break;
            }
        }
        return $orderType;
    }

    public static function getChargebackFinOrderType($orderType){
        switch ($orderType){
            case Chargeback::REFUND_FINANCE_STATUS:{
                $orderType = Chargeback::REFUND_FINANCE_STATUS_NAME;
                break;
            }
            case Chargeback::REFUND_FINANCE_BACK:{
                $orderType = Chargeback::REFUND_FINANCE_BACK_NAME;
                break;
            }
            case Chargeback::REFUND_FINANCE_COMPLETE:{
                $orderType = Chargeback::REFUND_FINANCE_COMPLETE_NAME;
                break;
            }
        }
        return $orderType;
    }

    public static function getChargebackPurOrderType($orderType){
        switch ($orderType){
            case Chargeback::REFUND_PURCHASE_STATUS:{
                $orderType = Chargeback::REFUND_PURCHASE_STATUS_NAME;
                break;
            }
            case Chargeback::REFUND_PURCHASE_BACK:{
                $orderType = Chargeback::REFUND_PURCHASE_BACK_NAME;
                break;
            }
            case Chargeback::REFUND_PURCHASE_COMPLETE:{
                $orderType = Chargeback::REFUND_PURCHASE_COMPLETE_NAME;
                break;
            }
        }
        return $orderType;
    }

}