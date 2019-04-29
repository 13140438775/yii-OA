<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 11:45:46
 */

namespace app\services;

use app\controllers\FinanceController;
use app\exceptions\OrderException;
use app\models\Base;
use app\models\Goods;
use app\models\GoodsSupplier;
use app\models\OpenProject;
use app\models\Order;
use app\models\OrderAction;
use app\models\OrderDetail;
use app\models\OrderLog;
use app\models\OrderPayCertificate;
use app\models\OrderPayInfo;
use app\models\SaveRemark;
use app\models\SupplierType;
use app\models\Warehouse;
use app\models\WarehouseOut;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\test\InitDbFixture;

class CustomerOrderService
{
    const REAL = 1;         //实库
    const VIRTUAL = 2;      //虚库
    const COMMIT_STATUS = false;
    const FILTER = ['pay_list_id', 'coupon_amount', 'pay_status', 'is_presale', 'order_time', 'finance_check_status', 'contact_address', 'type_id', 'order_name','finish_time'];
    const WAIT = 1;         //未提交
    const COMPLETE = 2;     //已完成
    const CHECK = 3;        //财务审核
    const CHECK_BACK = 4;   //财务驳回
    const PURCHASE = 5;     //采购中
    const  SHIP = 6;        //发货中
    const CANCEL = 7;       //已关闭

    const PUR_THIRD = 2;//外部订单
    const VIRTUAL_ID = 2;//虚库ID

    const OA_ORDER = 1;     //OA开店订单
    const CUS_ORDER = 2;    //客服补货订单


    /**
     * @param $params
     * @CreateTime ${DATE} ${HOUR}:${MINUTE}:${SECOND}
     * @Author: ${USER}@likingfit.com
     */
    public static function insertOrder($orderId, $params)
    {
        $gym = OpenProject::findOne(['id' => $params['gym_id']]);
        $orderModel = Order::getOne($orderId);
        if (empty($orderModel)) {
            $orderModel = new Order();
            $orderModel->operator_id = \Yii::$app->user->getIdentity()->id;
            $orderModel->operator_name = \Yii::$app->user->getIdentity()->name;
        }
        $orderModel->order_id = $orderId;
        $orderModel->gym_id = $gym['id'];
        $orderModel->gym_name = $gym['gym_name'];
        $orderModel->province_id = $gym['province_id'];
        $orderModel->province_name = $gym['province_name'];
        $orderModel->city_id = $gym['city_id'];
        $orderModel->city_name = $gym['city_name'];
        $orderModel->district_id = $gym['district_id'];
        $orderModel->district_name = $gym['district_name'];
        $orderModel->order_type = $params['order_type'];
        $orderModel->contact_name = $params['contact_name'];
        $orderModel->contact_address = ArrayHelper::getValue($params, "contact_address", '');
        $orderModel->contact_phone = $params['contact_phone'];
        $orderModel->order_status = empty($params['order_status']) ? self::WAIT : $params['order_status'];
        $orderModel->total_amount = ArrayHelper::getValue($params, "total_amount", 0) * 100;
        $orderModel->order_name = ArrayHelper::getValue($params, "order_name", '');
        $orderModel->actual_amount = ArrayHelper::getValue($params, "actual_amount", 0) * 100;
        $orderModel->discount = ArrayHelper::getValue($params, "discount", 100);
        $orderModel->create_time = time();
        $orderModel->update_time = time();
        $orderModel->last_operator_id = \Yii::$app->user->getIdentity()->id;
        $orderModel->last_operator_name = \Yii::$app->user->getIdentity()->name;
        $orderModel->save();
    }

    public static function insertOrderDetail($orderId, $goods)
    {
        OrderDetail::unavailable($orderId);
        $data = [];
        foreach ($goods as $good) {
            $data[] = array(
                'goods_id' => $good['goods_id'],
                'goods_num' => $good['goods_num'],
                'order_id' => $orderId,
                'goods_amount' => $good['goods_amount'] * 100,
                'type_id' => $good['type_id'],
                'create_time' => time(),
                'update_time' => time(),
                'operator_id' => \Yii::$app->user->getIdentity()->id,
                'operator_name' => \Yii::$app->user->getIdentity()->name
            );
        }
        $model = new  OrderDetail();
        $model->batchInsert($data);

    }

    public static function insertPayInfo($orderId, $infos)
    {
        OrderPayInfo::unavailable($orderId);
        foreach ($infos as $info) {
            $payInfoModel = new OrderPayInfo();
            $payInfoModel->order_id = $orderId;
            $payInfoModel->pay_name = ArrayHelper::getValue($info, "pay_name", '');
            $payInfoModel->pay_account = ArrayHelper::getValue($info, "pay_account", '');
            $payInfoModel->pay_amount = ArrayHelper::getValue($info, "pay_amount", 0) * 100;
            $payInfoModel->accept_account = ArrayHelper::getValue($info, "accept_account", '');
            $payInfoModel->phone = empty($info['phone'])?0:$info['phone'];
            $payInfoModel->create_time = time();
            $payInfoModel->update_time = time();
            $payInfoModel->operator_id = \Yii::$app->user->getIdentity()->id;
            $payInfoModel->operator_name = \Yii::$app->user->getIdentity()->name;
            $payInfoModel->save();
            $payId = $payInfoModel->id;
            if (!empty($info['certificate'])) {
                self::insertCertificate($payId, $info['certificate']);
            }

        }
    }

    public static function insertCertificate($payId, $img)
    {
        OrderPayCertificate::unavailable($payId);
        $data = [];
        foreach ($img as &$certificate) {
            $data[] = array(
                'certificate' => $certificate['url'],
                'order_pay_info_id' => $payId,
                'create_time' => time(),
                'update_time' => time(),
            );
        }
        $model = new  OrderPayCertificate();
        $model->batchInsert($data);
    }

    public static function saveOrder($params)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $orderId = empty($params['order_id']) ? generateIntId("ZK") : $params['order_id'];
            self::insertOrder($orderId, $params);
            self::insertOrderDetail($orderId, $params['goods']);
            if (isset($params['pays']) && !empty($params)) {
                self::insertPayInfo($orderId, $params['pays']);
            }
            if (isset($params['order_type']) && $params['order_type'] == self::OA_ORDER){
                self::split($orderId);
            }
            if (isset($params['remark']) && !empty($params['remark'])){
                $remark = new SaveRemark();
                $remark->saveRemark($orderId,$params['remark'],SaveRemark::ORDER_SAVE);
            }
            $transaction->commit();
            return $orderId;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
            throw new \app\exceptions\OrderException(OrderException::SAVE_FAIL);
        }
    }



    public static function getOrderDetail($orderId)
    {
        $order = Order::find()->with('goods')->with(['pays' => function (ActiveQuery $query) {
            $query->with('certificate');
        }])->where(['order_id' => $orderId])->asArray()->one();
        return $order;
    }

    public static function getPurOrderDetail($orderId)
    {
        $gymTable = OpenProject::tableName();
        $order = Order::find()
            ->select(['order_id', 'gym_id', $gymTable . '.gym_name', 'contact_name', 'contact_phone', 'address'])
            ->joinWith('gym', false)
            ->where(['order_id' => $orderId])->asArray()->one();
        $out = Order::find()
            ->select(['t_order.order_id',
                't_warehouse_out.order_id', 't_warehouse_out.out_status', 't_warehouse_out.warehouse_out_id','t_warehouse_out.operator_name',
                't_warehouse_out_detail.except_out_num', 't_warehouse_out_detail.goods_id', 't_warehouse_out_detail.actual_out_num',
                't_goods.id', 't_goods.goods_name', 't_goods.brand', 't_goods.model', 't_goods.unit', 't_goods.inventory',])
            ->joinWith('out', false)
            ->where(['t_order.order_id' => $orderId])
            ->asArray()
            ->all();
        $warehouse = [];
        foreach ($out as $value){
            $value['not_out_num'] = $value['except_out_num'] - $value['actual_out_num'];
            $warehouse[$value['warehouse_out_id']]['warehouse_out_id'] = $value['warehouse_out_id'];
            $warehouse[$value['warehouse_out_id']]['operator_name'] = $value['operator_name'];
            $warehouse[$value['warehouse_out_id']]['out_status'] = $value['out_status'];
            $warehouse[$value['warehouse_out_id']]['detail'][] = $value;
        }
        $data['orderInfo'] = $order;
        $data['outInfo'] = $warehouse;
        Base::convert2string($data['outInfo'], ["out_status" => "purchase.out_status"]);
        return $data;
    }

    public static function getOrderList($params, $addition = null,$export = false)
    {
        $join = [];
        $fields = array(
            'select' => [['id','order_id', 'order_name', 'order_time', 'gym_id', 'gym_name', 'order_status', 'finance_check_status',
                'operator_id', 'operator_name', 'contact_name', 'contact_phone', 'actual_amount', 'order_type','last_operator_id', 'last_operator_name',]
            ]);
        foreach ($fields['select'][0] as &$select) {
            $select = Order::tableName() . '.' . $select;
        }
        $fields['orderBy'] =  [[Order::tableName() .'.id' => SORT_DESC]];
        $model = new Order($fields);
        $condition = ['AND'];
        if (isset($params['search']) &&  !empty($params['search'])) {
            $search = ['OR'];
            $search[] = ['LIKE', Order::tableName() . '.order_id', $params['search']];
            $search[] = ['LIKE', Order::tableName() . '.gym_name', $params['search']];
            $condition[] = $search;
        }

        if (isset($params['order_id']) && !empty($params['order_id'])){
            $condition[] = [ Order::tableName() . '.order_id'=> $params['order_id']];
        }
        if (isset($params['order_status']) && !empty($params['order_status'])){
            $condition[] = [ Order::tableName() . '.order_status'=> $params['order_status']];
        }
        if (isset($params['finance_check_status']) && !empty($params['finance_check_status'])){
            $condition[] = [ Order::tableName() . '.finance_check_status'=> $params['finance_check_status']];
        }
        if (isset($params['order_type']) && !empty($params['order_type'])){
            $condition[] = [ Order::tableName() . '.order_type'=> $params['order_type']];
        }
        if (isset($params['start_time']) && !empty($params['start_time'])){
            $condition[] = ['>=', Order::tableName() . '.order_time', $params['start_time']];
        }
        if (isset($params['end_time']) && !empty($params['end_time'])){
            $condition[] = ['<=', Order::tableName() . '.order_time', $params['end_time']];
        }
        if (isset($params['gym_name']) && !empty($params['gym_name'])){
            $condition[] = ['LIKE',Order::tableName() .'.gym_name',$params['gym_name']];
        }
        if (isset($params['contact_phone']) && !empty($params['contact_phone'])){
            $condition[] = ['LIKE',Order::tableName() .'.contact_phone',$params['contact_phone']];
        }
        if (isset($params['contact_name']) && !empty($params['contact_name'])){
            $condition[] = ['LIKE',Order::tableName() .'.contact_name',$params['contact_name']];
        }
        if (isset($params['operator_name']) && !empty($params['operator_name'])){
            $condition[] = ['LIKE',Order::tableName() .'.operator_name',$params['operator_name']];
        }
        if (isset($params['last_operator_name']) && !empty($params['last_operator_name'])){
            $condition[] = ['LIKE',Order::tableName() .'.last_operator_name',$params['last_operator_name']];
        }
        if (!empty($params['end_operator_id'])) {
            $join = ['log'];
            $condition[] = ['t_order_log.operator_id' => $params['end_operator_id']];
        }
        if (!empty($addition)) {
            $condition[] = $addition;
        }

        $condition[] = [Order::tableName() .".is_available" => AVAILABLE];
        if ($export){
            return $model->getList($condition,$join);
        }
        $page = ArrayHelper::getValue($params, 'page', PAGE);
        $pageSize = ArrayHelper::getValue($params, 'page_size', PAGESIZE);
        $list = $model->paginate($page, $pageSize, $join, $condition);
        $labels["order_time"] = function ($val) {
            if (!empty($val)) {
                return date("Y-m-d H:i:s", $val);
            }
        };
        $labels['finance_check_status'] = "purchase.finance_check_status";
        Base::convert2string($list['rows'], $labels);
        return $list;
    }

    public static function commit($params)
    {
        $orderId = self::saveOrder($params);
        self::checkCommit($orderId, $params['remark']);
    }

    public static function checkCommit($orderId, $remark)
    {
        $order = self::getOrderDetail($orderId);
        foreach ($order as $k => $detail) {
            if (in_array($k, self::FILTER)) {
                continue;
            }
            if (empty($detail)) {
                throw new OrderException(OrderException::PURCHASE_COMMIT);
            } elseif ($detail == 'pays') {
                foreach ($detail as $pays) {
                    foreach ($pays as $key => $value) {
                        if (empty($value)) {
                            throw new OrderException(OrderException::PURCHASE_COMMIT);
                        }
                    }
                }
            }
        }
        Order::updateStatus($orderId, ['order_status' => self::CHECK, 'order_time' => time(), 'finance_check_status' => Order::FIN_STATUS_WAIT]);
        $param = array(
            'relation_id' => $orderId,
            'gym_id' => $order['gym_id'],
            'gym_name' => $order['gym_name'],
            'total_amount' => $order['total_amount'],
            'remark' => $remark
        );
        FinanceService::orderAction($param, FinanceController::ORDER);
    }

    public static function split($orderId)
    {
        $orderDetail = OrderDetail::find()
            ->select([
                OrderDetail::tableName() . '.*',
                Goods::tableName() . '.goods_id', Goods::tableName() . '.goods_type', Goods::tableName() . '.purchase_amount', Goods::tableName() . '.inventory',
                GoodsSupplier::tableName() . '.*'
            ])
            ->joinWith('goods', false)
            ->joinWith('goodsSupplier', false)
            ->where(['order_id' => $orderId, 'is_available' => AVAILABLE])
            ->asArray()
            ->all();
        $order = Order::find()
            ->select(['order_id', 'discount', 'gym_id', 'gym_name'])
            ->where(['order_id' => $orderId])
            ->asArray()
            ->one();
        $real = array();                    //  实库商品
        $virtual = array();                 //  虚库商品
        $purAmount = 0;
        $outNum = 0;
        $warehouse = Warehouse::find()->where(['warehouse_type'=>self::VIRTUAL_ID])->asArray()->one();
        $warehouseId = $warehouse['warehouse_id'];
        foreach ($orderDetail as $detail) {
            $key = $detail['supplier_id'];
            if ($detail['goods_type'] == self::VIRTUAL) {
                //封装新增采购单数据
                $purAmount += ($detail['goods_num'] * $detail['purchase_amount'])/100;
                $virtual[$key] ['purchase'] = array(
                    'order_id' => $detail['order_id'],
                    //'actual_amount' => $purAmount,
                    'discount' => 100,
                    'purchase_type' => self::PUR_THIRD,
                    'supplier_id' => $key,
                    'warehouse_id' =>$warehouseId,
                    'gym_id' => $order['gym_id'],
                    'gym_name' => $order['gym_name'],
                    'operator_id' =>1,// \Yii::$app->user->getIdentity()->id,
                    'operator_name' => 2,//\Yii::$app->user->getIdentity()->name

                );
                $virtual[$key]['purchase_goods'][] = array(
                    'purchase_num' => $detail['goods_num'],
                    'goods_id' => $detail['goods_id'],
                    'purchase_amount' =>  $detail['purchase_amount']/ 100,
                    'actual_amount' => ($detail['goods_num'] * $detail['purchase_amount']) / 100,
                    'warehouse_id' => $warehouseId,
                );
                $virtual[$key]['purchase_supplier'] = array(
                    'supplier_id' => $key
                );
                $virtual[$key]['purchase_warehouse'] = array(
                    'warehouse_id' => $warehouseId
                );
            } else {
                $real[$key]['warehouse_out'] = array(
                    'order_id' => $orderId,
                    'supplier_id' => $key,
                    'gym_id' => $order['gym_id'],
                );
                $real[$key]['warehouse_out_goods'][] = array(
                    'goods_id' => $detail['goods_id'],
                    'except_out_num' => $detail['goods_num'],
                    'inventory' => $detail['inventory']
                );
            }

        }
        $orderStatus = empty($virtual) ? self::SHIP : self::PURCHASE;
        if (!empty($real)){
            foreach ($real as $out) {
                WarehouseService::createWarehouseOut($out);
                //调用出库单
            }
        }
        if (!empty($virtual)){
            foreach ($virtual as $purchase) {
                //计算采购单总价
                $actualArr = array_column($purchase['purchase_goods'],"actual_amount");
                $actualAmount = array_sum($actualArr);
                $purchase['purchase']['actual_amount'] = $actualAmount;
                //调采购单
                PurchaseService::addPurchase($purchase);

            }
        }

        Order::updateStatus($orderId, ['order_status' => $orderStatus, 'finance_check_status' => Order::FIN_STATUS_PASS]);     //更新订单状态
        OrderDetail::updateAll(['detail_status' => $orderStatus],['order_id'=>$orderId]);    //更新订单状态
    }

    public static function orderStatus($orderId)
    {
        $param = ['relation_id' => $orderId];
        $action = FinanceService::checkList($param, FinanceController::ORDER, false);
        //调用采购单
        $purchase = Order::find()
            ->select(['t_order.order_id',
                't_purchase.order_id', 't_purchase.purchase_status', 't_purchase.operator_id', 't_purchase.operator_name',
                't_purchase_detail.purchase_num', 't_purchase_detail.goods_id', 't_purchase_detail.update_time',
                't_goods.goods_name'])
            ->joinWith('purchase', false, "INNER JOIN")
            ->where(['t_order.order_id' => $orderId])
            ->asArray()
            ->all();
        //调用出库单

        $detail = Order::find()
            ->select(
                [
                    't_order.order_id','t_order.order_status','t_order.order_time',
                    't_warehouse_out.order_id','t_warehouse_out.out_status','t_warehouse_out.operator_name',
                    't_warehouse_out_detail.goods_id','t_warehouse_out_detail.except_out_num','t_warehouse_out_detail.actual_out_num','t_warehouse_out_detail.warehouse_out_id',
                    't_goods.goods_id','t_goods.goods_name'
                ])
            ->joinWith(['warehouseOut.warehouseOutDetail.goods',],false)
            ->where(['t_order.order_id'=>$orderId,'t_order.is_available'=>AVAILABLE])
            ->asArray()
            ->all();
        $orderDetail = OrderDetail::find()->select(['goods_id','detail_status','finish_time'])->where(['order_id'=>$orderId,'is_available'=>AVAILABLE])->asArray()->all();
        foreach ($detail as &$all){
            foreach ($orderDetail as $value){
                if ($all['goods_id'] == $value['goods_id'] ){
                    $all['finish_time'] = $value['finish_time'];
                    $all['detail_status'] = $value['detail_status'];
                }
            }
        }

        if (!empty($detail)) {
            $orderTime = $detail[0]['order_time'];
        }

        foreach ($action['rows'] as &$row) {
            $row['create_time'] = date('Y-m-d H:i:s', $row['create_time']);
            $row['update_time'] = date('Y-m-d H:i:s', $row['update_time']);
            $row['action_name'] = OrderAction::ORDER_ACTION[$row['check_status']];
        }
        $data['action'] = $action['rows'];
        $data['purchase'] = $purchase;
        $data['out'] = $detail;
        $data['order_time'] = $orderTime;
        Order::convert2string($data['purchase'], ['purchase_status' => "purchase.purchase_status"]);
        Order::convert2string($data['out'], ['order_status' => "purchase.order_status",'detail_status' => "purchase.detail_status"]);
        return $data;
    }

    public static function finishOrder($params)
    {
        $attributes = array(
            'finish_time' => empty($params['finish_time']) ? time() : $params['finish_time'],
            'detail_status' => self::COMPLETE,
            'update_time' => time()
        );
        OrderDetail::updateAll($attributes, ['order_id' => $params['order_id'],'goods_id'=>$params['goods_id']]);
        $condition = ['AND'];
        $condition[] = ['order_id' => $params['order_id']];
        $condition[] = ['!=', 'detail_status', self::COMPLETE];
        $condition[] = ['is_available'=>AVAILABLE];
        $status = OrderDetail::find()->where($condition)->asArray()->all();
        if (empty($status)){
            Order::updateStatus($params['order_id'], ['order_status'=>self::COMPLETE]);
        }
    }

    //一键确认
    public static function allFinishOrder($params){
        foreach ($params as $goods){
            self::finishOrder($goods);
        }
    }

    public static function getOrderStatus(){
        $status = \Yii::$app->params['purchase']['order_status'];
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
            case Order::UN_SUBMIT:{
                $orderType = Order::UN_SUBMIT_NAME;
                break;
            }
            case Order::IS_ALREADY:{
                $orderType = Order::IS_ALREADY_NAME;
                break;
            }
            case Order::SUB_FINANCE:{
                $orderType = Order::SUB_FINANCE_NAME;
                break;
            }
            case Order::FINANCE_RETURN:{
                $orderType = Order::FINANCE_RETURN_NAME;
                break;
            }
            case Order::PURCHASE_LOADING:{
                $orderType = Order::PURCHASE_LOADING_NAME;
                break;
            }
            case Order::SHIPPING_LOADING:{
                $orderType = Order::SHIPPING_LOADING_NAME;
                break;
            }
            case Order::IS_CLOSE:{
                $orderType = Order::IS_CLOSE_NAME;
                break;
            }
        }
        return $orderType;
    }

    public static function getFinOrderType($orderType){
        switch ($orderType){
            case Order::FIN_STATUS_WAIT:{
                $orderType = Order::FIN_STATUS_WAIT_NAME;
                break;
            }
            case Order::FIN_STATUS_BACK:{
                $orderType = Order::FIN_STATUS_BACK_NAME;
                break;
            }
            case Order::FIN_STATUS_PASS:{
                $orderType = Order::FIN_STATUS_PASS_NAME;
                break;
            }
        }
        return $orderType;
    }

}