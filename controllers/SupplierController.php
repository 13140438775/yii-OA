<?php

namespace app\controllers;

use Yii;
use app\services\PurchaseService;
use app\services\SupplierService;

class SupplierController extends BaseController
{

    public function actionGetSupplier(){
        $supplierResult = SupplierService::getSupplier();
        $result['supplier_list'] = $supplierResult;
        return $result;
    }

    public function actionGetSearchData(){
        $areaResult = Yii::$app->params['area_list'];
        $result['area_list'] = $areaResult;
        return $result;
    }

    public function actionGetSuppliersList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:SupplierService::SUPPLIERS_LIST_PAGENUM;
        $suppliersList = SupplierService::getSuppliersList($searchParam,$page,$pageSize);
        return $suppliersList;
    }

    public function actionGetSupplierAddData(){
        $typeResult = PurchaseService::getType();
        $areaResult = Yii::$app->params['area_list'];
        $result['type_list'] = $typeResult;
        $result['area_list'] = $areaResult;
        return $result;

    }

    public function actionAdd(){
        $param = Yii::$app->request->post();
        SupplierService::addSupplier($param);
        return [];
    }

    public function actionGetSupplierInfo(){
        $supplierId = Yii::$app->request->post('supplier_id');
        $supplierInfo = SupplierService::getSupplierInfo($supplierId);
        return $supplierInfo;
    }

    public function actionEdit(){
        $param = Yii::$app->request->post();
        SupplierService::editSupplier($param);
        return [];
    }


}