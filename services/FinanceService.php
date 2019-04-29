<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/19 17:26:41
 */

namespace app\services;

use app\controllers\FinanceController;
use app\exceptions\OrderException;
use app\models\Message;
use app\models\Order;
use app\models\OrderAction;
use app\models\Roles;
use app\models\SaveRemark;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

class FinanceService
{

    const   PAGE = 1;
    const   PAGESIZE = 15;
    const   CHECK_WAIT = 1; //待审核
    const   CHECK_BACK = 2;   //审核驳回
    const   CHECK_COMPLETE = 3; //审核通过
    const   ORDER = 1;//订单
    const   REFUND = 2;//退单


    public static function orderAction($params, $type)
    {
        OrderAction::unavailable($params['relation_id'],$type);
        //$checkStatus = ArrayHelper::getValue($params, 'check_status', self::CHECK_WAIT);
        $model = new OrderAction();
        $model->relation_id = $params['relation_id'];
        $model->remark = empty($params['remark'])?'':$params['remark'];
        $model->check_status = ArrayHelper::getValue($params, 'check_status',self::CHECK_WAIT);
        $model->create_time = time();
        $model->update_time = time();
        $model->order_type = $type;
        $model->gym_id = $params['gym_id'];
        $model->gym_name = $params['gym_name'];
        $model->total_amount = $params['total_amount'] * 100;
        $model->operator_id = \Yii::$app->user->getIdentity()->id;
        $model->operator_name = \Yii::$app->user->getIdentity()->name;
        $model->save();


    }

    public static function orderStatus($orderId, $status,$remark=null)
    {
        if ($status == self::CHECK_BACK) {
            Order::updateStatus($orderId, ['order_status'=>CustomerOrderService::CHECK_BACK,'finance_check_status'=>Order::FIN_STATUS_BACK]);   //  财务驳回更新订单状态
            $manage = \Yii::$app->getAuthManager()->getUserIdsByRole(Roles::CUSTOMER_MANAGER);
            $specialist = \Yii::$app->getAuthManager()->getUserIdsByRole(Roles::CUSTOMER_SPECIALIST);
            $staff = array_merge($manage,$specialist);
            if (!empty($staff)){
                foreach ($staff as $id) {
                    $message[] = [
                        'staff_id' => $id,
                        'title' => '订单被财务驳回',
                        'content' => $orderId . '订单被财务驳回，请及时处理',
                        'message_type' => Message::PURCHASE,
                    ];
                }
                $model = new Message();
                $model->batchInsert($message);
            }
        } elseif ($status == self::CHECK_COMPLETE) {
            CustomerOrderService::split($orderId);            //通过审核订单 生成采购单和出库单
        }
    }

    //财务订单审核
    public static function orderSave($params,$type)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $params['check_status'] = ArrayHelper::getValue($params, 'check_status', self::CHECK_WAIT);
            self::orderAction($params,$type);
            self::orderStatus($params['relation_id'], $params['check_status'],$params['remark']);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
            throw new \app\exceptions\OrderException(OrderException::SAVE_FAIL);
        }
    }


    //财务退单审核
    public static function refundSave($params,$type)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $params['check_status'] = ArrayHelper::getValue($params, 'check_status', self::CHECK_WAIT);
            self::orderAction($params,$type);
            self::orderStatus($params['relation_id'], $params['check_status']);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new \app\exceptions\OrderException(OrderException::SAVE_FAIL);
        }
    }


    public static function checkList($param,$type = null, $unavailable = true)
    {
        $condition = ['AND'];
        if ($unavailable) {
            $condition[] = ['action_status' => AVAILABLE];
        }
        if ($type == self::ORDER){
            $condition[] = ['order_type' => $type];
        }elseif ($type >= self::REFUND){
            $condition[] = ['>=','order_type',self::REFUND];
        }
        if (!empty($param['search'])) {
            $search = ['OR'];
            $search[] = ['LIKE', 'relation_id', $param['search']];
            $search[] = ['LIKE', 'gym_name', $param['search']];
            $condition[] = $search;
        }
        if (!empty($param['check_status'])) {
            $condition[] = array(
                'check_status' => $param['check_status']
            );
        }
        if (!empty($param['relation_id'])) {
            $condition[] = array(
                'relation_id' => $param['relation_id']
            );
        }
        if (!empty($param['start_time'])) {
            $condition[] = ['>=', 'create_time', $param['start_time']];
        }
        if (!empty($param['end_time'])) {
            $condition[] = ['<=', 'create_time', $param['end_time']];
        }
        if (!empty($param['order_type'])){
            $condition[] = ['order_type'=>$param['order_type']];
        }
//        $field = array(
//            'SELECT' => [
//                [
//                    'id',
//                    'relation_id',
//                    'check_status',
//                    'create_time',
//                    'gym_name',
//                    'total_amount',
//                    'order_type'
//                ]
//            ]
//        );
        $model = new OrderAction();
        $page = ArrayHelper::getValue($param, 'page', self::PAGE);
        $pageSize = ArrayHelper::getValue($param, 'page_size', self::PAGESIZE);
        $list = $model->paginate($page, $pageSize, [], $condition);
        OrderAction::convert2string($list['rows'],[]);
        return $list;
    }

}