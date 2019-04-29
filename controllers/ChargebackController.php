<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/13 16:12:48
 */
namespace app\controllers;


use app\models\Chargeback;
use app\models\SaveRemark;
use app\services\ChargebackService;
use app\services\CustomerOrderService;

class ChargebackController extends BaseController{
    const   WAIT = 1;       //未提交
    const   COMMIT = 2;     //已提交


    public function actionAdd(){
        $params = \Yii::$app->request->post();
        ChargebackService::insertChargeback($params,self::WAIT);
    }

    public function actionGetList(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::getList($params);
        return $result;
    }
    public function actionGetDetail(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::getDetail($params['chargeback_id']);
        return $result;
    }

    //采购订单列表
    public function actionGetPurList(){
        $params = \Yii::$app->request->post();
        $params['pur_status'] = ChargebackService::PUR_STATUS;
        $result = ChargebackService::getList($params);
        return $result;
    }

    //财务订单列表
    public function actionGetFinList(){
        $params = \Yii::$app->request->post();
        $params['fin_status'] = ChargebackService::PUR_STATUS;
        $result = ChargebackService::getList($params);
        return $result;
    }

    public function actionCommit(){
        $params = \Yii::$app->request->post();
        ChargebackService::commit($params);
    }

    //采购审核
   public function actionPurCheck(){
       $params = \Yii::$app->request->post();
       $data = $params['data'];
       $status = $params['status'];
       ChargebackService::updateStatus($data,ChargebackService::PUR,$status);
   }

   public function actionFinCheck(){
       $params = \Yii::$app->request->post();
       $data = $params['data'];
       $status = $params['status'];
       ChargebackService::updateStatus($data,ChargebackService::FINANCE,$status);
   }

    public function actionStatus(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::orderStatus($params['chargeback_id']);
        return $result;
    }

    //明细
    public function actionPurChargeDetail(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::purChargeDetail($params);
        return $result;
    }

    //更新退单已完成
    public function actionComplete(){
        $params = \Yii::$app->request->post();
        $result = Chargeback::updateStatus([ 'chargeback_status' => ChargebackService::COMPLETE], $params['chargeback_id']);
        return $result;
    }

    //更新退单状态
    public function actionUpdateStatus(){
        $params = \Yii::$app->request->post();
        $result = Chargeback::updateStatus([ 'chargeback_status' => $params['status']], $params['chargeback_id']);
        return $result;
    }

    //财务驳回状态更新收款人信息
    public function actionUpdateRefund(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::updateRefund($params);
        return $result;
    }

    //获取退单商品详情
    public function actionOrderDetail(){
        $params = \Yii::$app->request->post();
        $result = ChargebackService::getOrderDetail($params);
        return $result;
    }

    //获取退单所有状态
    public function actionChargeStatus(){
        $result = ChargebackService::getChargeStatus();
        return $result;
    }

    //获取保存备注
    public function actionGetRemark(){
        $orderId = \Yii::$app->request->post('chargeback_id');
        $result =   SaveRemark::getRemark($orderId,SaveRemark::CHARGE_SAVE);
        return $result;
    }

}