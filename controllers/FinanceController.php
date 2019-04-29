<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/19 17:31:33
 */
namespace app\controllers;

use app\services\FinanceService;

class FinanceController extends BaseController{
    const   ORDER = 1;
    const   REFUND = 2;
    const   PUR_REFUND = 3;

    public function actionOrderSave(){
        $params = \Yii::$app->request->post();
        FinanceService::orderSave($params,self::ORDER);
    }

    public function actionRefundSave(){
        $params = \Yii::$app->request->post();
        FinanceService::orderAction($params,self::REFUND);
    }

    public function actionOrderList(){
        $params = \Yii::$app->request->post();
        $result = FinanceService::checkList($params);
        return $result;
    }

//    public function actionRefundList(){
//        $params = \Yii::$app->request->post();
//        $result = FinanceService::checkList($params,self::REFUND);
//        return $result;
//    }
}