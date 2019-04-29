<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:46:44
 */
namespace app\controllers;

use app\services\CustomerOrderService;
use Yii;
use app\services\PurchaseService;
use app\services\SupplierService;
use app\services\WarehouseService;

class PurchaseController extends BaseController{

    public function actionGetAddPurchaseData(){
        $result = [];
        $supplierResult = SupplierService::getSupplier();
        $warehouseResult = WarehouseService::getWarehouse();
        $result['supplier_list'] = $supplierResult;
        $result['warehouse_list'] = $warehouseResult;
        $result['operator_name'] = \Yii::$app->user->getIdentity()->name;
        return $result;
    }

    public function actionGetPurchaseGoodsList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:PurchaseService::PURCHASE_GOODS_PAGENUM;
        $purchaseGoodsList= PurchaseService::getPurchaseGoodsList($searchParam,$page,$pageSize);
        return $purchaseGoodsList;
    }

    public function actionAddPurchase(){
        $params = Yii::$app->request->post();
        PurchaseService::addPurchase($params);
        return [];
    }

    public function actionGetPurchaseList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:PurchaseService::PURCHASE_GOODS_LIST_PAGENUM;
        $purchaseList = PurchaseService::getPurchasesList($searchParam,$page,$pageSize);
        return $purchaseList;
    }

    public function actionGetPurchaseListSearchData(){
        $result = [];
        $purchaseStatusResult = Yii::$app->params['purchase_status_arr'];
        $warehouseResult = WarehouseService::getWarehouse();
        $result['purchase_status_list'] = $purchaseStatusResult;
        $result['warehouse_list'] = $warehouseResult;
        return $result;
    }

    public function actionEditPurchase(){
        $params = Yii::$app->request->post();
        PurchaseService::editPurchase($params);
        return [];
    }

    public function actionConfirmGoods(){
        $params = Yii::$app->request->post();
        PurchaseService::confirmGoods($params);
        return [];
    }

    public function actionAdjustGoods(){
        $params = Yii::$app->request->post();
        PurchaseService::adjustGoods($params['purchase_id']);
        return [];
    }

    public function actionGetPurchaseInfo(){
        $params = Yii::$app->request->post();
        $purchaseInfo = PurchaseService::getPurchaseInfo($params['purchase_id']);
        return $purchaseInfo;
    }

    public function actionClosePurchase(){
        $params = Yii::$app->request->post();
        PurchaseService::closePurchase($params['purchase_id']);
        return [];
    }

    public function actionAddGodownEntry(){
        $params = Yii::$app->request->post();
        WarehouseService::addGodownEntry($params['purchase_id']);
        return [];
    }

    //采购订单管理列表
    public function actionPurOrderList(){
        $params = Yii::$app->request->post();
        $result = PurchaseService::getPurOrderList($params);
        return $result;
    }





}

