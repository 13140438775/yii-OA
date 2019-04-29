<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/7 09:57:23
 */
namespace app\controllers;

use app\models\Order;
use app\models\SaveRemark;
use app\services\CustomerOrderService;

class CustomerOrderController extends BaseController{


    public function actionSaveOrder(){
        $param = \Yii::$app->request->post();
        CustomerOrderService::saveOrder($param);
    }

    public function actionOrderDetail(){
        $param = \Yii::$app->request->post();
        $result = CustomerOrderService::getOrderDetail($param['order_id']);
        return $result;
    }

    public function actionPurOrderDetail()
    {
        $param = \Yii::$app->request->post();
        $result = CustomerOrderService::getPurOrderDetail($param['order_id']);
        return $result;

    }

    public function actionGetList(){
        $params = \Yii::$app->request->post();
        $result = CustomerOrderService::getOrderList($params);
        return $result;
    }
    public function actionPurGetList(){
        $params = \Yii::$app->request->post();
        $addition = ['order_status' => [CustomerOrderService::PURCHASE,CustomerOrderService::SHIP,CustomerOrderService::COMPLETE]];
        $result = CustomerOrderService::getOrderList($params,$addition);
        return $result;
    }

    public function actionGetFinList(){
        $params = \Yii::$app->request->post();
        $addition = ['>=','finance_check_status',Order::FIN_STATUS_WAIT];
        $result = CustomerOrderService::getOrderList($params,$addition);
        return $result;
    }

    public function actionCommit(){
        $params = \Yii::$app->request->post();
        CustomerOrderService::commit($params);
    }

    public function actionStatus(){
        $params = \Yii::$app->request->post();
        $result = CustomerOrderService::orderStatus($params['order_id']);
        return $result;
    }

    public function actionFinish(){
        $params = \Yii::$app->request->post();
        CustomerOrderService::finishOrder($params);
    }

    public function actionAllFinish(){
        $params = \Yii::$app->request->post();
        CustomerOrderService::allFinishOrder($params);
    }

    public function actionOrderStatus(){
        $result = CustomerOrderService::getOrderStatus();
        return $result;
    }

    public function actionClose(){
        $orderId = \Yii::$app->request->post('order_id');
        Order::updateStatus($orderId,['order_status' => CustomerOrderService::CANCEL]);
    }

    public function actionGetRemark(){
        $orderId = \Yii::$app->request->post('order_id');
        $result =   SaveRemark::getRemark($orderId,SaveRemark::ORDER_SAVE);
        return $result;
    }



}