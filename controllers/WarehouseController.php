<?php

namespace app\controllers;

use app\models\WarehouseCheck;
use Yii;
use app\services\WarehouseService;

class WarehouseController extends BaseController
{
    /**
     * 获取仓库数据
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouse(){
        $warehouseResult = WarehouseService::getWarehouse();
        $result['warehouse_list'] = $warehouseResult;
        return $result;
    }

    /**
     * 获取仓库商品
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetGoods(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_GOOD_LISTS;
        $warehouseGoodsList = WarehouseService::getGoodsList($searchParam,$page,$pageSize);
        $result['warehouse_goods_list'] = $warehouseGoodsList;
        return $result;
    }

    /**
     * 获取入库单列表搜索基础数据
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseInSearchData(){
        $warehouseResult = WarehouseService::getWarehouse();
        $warehouseInStatusResult = Yii::$app->params['warehouse_in_status_arr'];
        $result['warehouse_list'] = $warehouseResult;
        $result['warehouse_in_status_list'] = $warehouseInStatusResult;
        return $result;
    }

    /**
     * 获取入库单列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseInList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_IN_LIST_PAGENUM;
        $warehouseInList = WarehouseService::getWarehouseInList($searchParam,$page,$pageSize);
        return $warehouseInList;
    }

    /**
     * 获取入库单详情
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseInInfo(){
        $params = Yii::$app->request->post();
        $warehouseInList = WarehouseService::getWarehouseInInfo($params['warehouse_in_id']);
        $result['warehouse_in_info'] = $warehouseInList;
        return $result;
    }

    /**
     * 入库单确认入库
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionConfirmWarehouseIn(){
        $params = Yii::$app->request->post();
        WarehouseService::confirmWarehouseIn($params);
        return [];
    }

    /**
     * 入库单调整入库
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionAdjustingInventory(){
        $params = Yii::$app->request->post();
        WarehouseService::adjustWarehouseIn($params['warehouse_in_id']);
        return [];
    }

    /**
     * 获取入库单详情列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseInDetailList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_IN_LIST_DETAIL_PAGENUM;
        $warehouseInDetailList = WarehouseService::getWarehouseInDetailList($searchParam,$page,$pageSize);
        return $warehouseInDetailList;
    }

    /**
     * 获取出库单列表搜索基础数据
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseOutSearchData(){
        $warehouseResult = WarehouseService::getWarehouse();
        $warehouseOutStatusResult = Yii::$app->params['warehouse_out_status_arr'];
        $result['warehouse_list'] = $warehouseResult;
        $result['warehouse_out_status_list'] = $warehouseOutStatusResult;
        return $result;
    }

    /**
     * 获取出库单列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseOutList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_OUT_LIST_PAGENUM;
        $warehouseOutList = WarehouseService::getWarehouseOutList($searchParam,$page,$pageSize);
        return $warehouseOutList;
    }

    /**
     * 获取出库单详情
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseOutInfo(){
        $params = Yii::$app->request->post();
        $warehouseOutInfo = WarehouseService::getWarehouseOutInfo($params['warehouse_out_id'],$params['warehouse_id']);
        $result['warehouse_out_info'] = $warehouseOutInfo;
        return $result;
    }

    /**
     * 获取出库单商品库存数量
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetGoodsInventoryNum(){
        $params = Yii::$app->request->post();
        $goodsWarehouseNum = WarehouseService::getWarehouseGoodsInventory($params);
        return $goodsWarehouseNum;
    }

    /**
     * 确认出库
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionConfirmWarehouseOut(){
        $params = Yii::$app->request->post();
        WarehouseService::confirmWarehouseOut($params);
        return [];
    }

    /**
     * 获取仓库商品
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseGoods(){
        $params = Yii::$app->request->post();
        $goodsList = WarehouseService::getWarehouseGoods($params['warehouse_id'],$params['goods_id']);
        return $goodsList;
    }

    /**
     * 调整出库单
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionAdjustingInventoryOut(){
        $params = Yii::$app->request->post();
         WarehouseService::adjustWarehouseOut($params['warehouse_out_id']);
        return [];
    }

    /**
     * 获取出库单详情列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseOutDetailList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_OUT_LIST_DETAIL_PAGENUM;
        $warehouseOutDetailList = WarehouseService::getWarehouseOutDetailList($searchParam,$page,$pageSize);
        return $warehouseOutDetailList;
    }

    /**
     * 获取领货单列表基础数据
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetGetGoodsSearchData(){
        $departmentResult = WarehouseService::getDepartmentList();
        $result['department_list'] = $departmentResult;
        return $result;
    }

    /**
     * 获取领货单列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetGetGoodsList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::GET_GOODS_LIST_PAGENUM;
        $getGoodsList = WarehouseService::getGetGoodsList($searchParam,$page,$pageSize);
        return $getGoodsList;
    }

    /**
     * 获取新增领货单基础信息
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetAddGoodsData(){
        $departmentResult = WarehouseService::getDepartmentList();
        $warehouseResult = WarehouseService::getWarehouse();
        $result['warehouse_list'] = $warehouseResult;
        $result['department_list'] = $departmentResult;
        $result['operator_name'] = \Yii::$app->user->getIdentity()->name;
        //获取第一次盘点时间
        $check = WarehouseCheck::getFirstCheck();
        $result['check_time'] = !empty($check)?$check['check_time']:'';
        return $result;
    }

    /**
     * 新增领货单
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionAddGetGoods(){
        $params = Yii::$app->request->post();
        WarehouseService::addGetGoods($params);
        return [];
    }

    /**
     * 获取领货单详情
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetGetGoodsInfo(){
        $params = Yii::$app->request->post();
        $getGoodsInfo = WarehouseService::getGetGoodsInfo($params['get_goods_id']);
        $result['get_goods_info'] = $getGoodsInfo;
        return $result;
    }

    /**
     * 退货单获取入库单详情
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetWarehouseInInfos(){
        $params = Yii::$app->request->post();
        $warehouseInList = WarehouseService::getWarehouseInInfos($params['warehouse_in_id']);
        $result['warehouse_in_info'] = $warehouseInList;
        return $result;
    }

    /**
     * 退货单新增
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionAddReturnGoods(){
        $params = Yii::$app->request->post();
        WarehouseService::addReturnGoods($params);
        return [];
    }

    /**
     * 获取退货单详情
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetReturnGoodsInfo(){
        $params = Yii::$app->request->post();
        $returnGoodsInfo = WarehouseService::getReturnGoodsInfo($params['return_id']);
        $result['return_info'] = $returnGoodsInfo;
        return $result;
    }

    /**
     * 获取退货单列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetReturnGoodsList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::RETURN_GOODS_LIST_PAGENUM;
        $returnGoodsList = WarehouseService::getReturnGoodsList($searchParam,$page,$pageSize);
        return $returnGoodsList;
    }

    /**
     * 获取退货单详情列表
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetReturnGoodsDetailList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::RETURN_GOODS_LIST_DETAIL_PAGENUM;
        $returnGoodsDetailList = WarehouseService::getReturnGoodsDetailList($searchParam,$page,$pageSize);
        return $returnGoodsDetailList;
    }

    /**
     * 获取所有仓库以及操作人名称
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionGetAllWarehouse(){
        $warehouseResult = WarehouseService::getWarehouse();
        $result['warehouse_list'] = $warehouseResult;
        $result['operator_name'] = \Yii::$app->user->getIdentity()->name;
        return $result;
    }

    /**
     * 新增盘点单
     * @CreateTime 2018/05/07 17:38:14
     * @Author     : huangyuhao@likingfit.com
     */
    public function actionAddWarehouseCheck(){
        $params = Yii::$app->request->post();
        WarehouseService::addWarehouseCheck($params);
        return [];
    }

    public function actionGetWarehouseCheckInfo(){
        $params = Yii::$app->request->post();
        $checkInfo = WarehouseService::getWarehouseCheckInfo($params['check_id']);
        $result['warehouse_check_info'] = $checkInfo;
        return $result;
    }

    public function actionGetWarehouseCheckList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::CHECK_GOODS_LIST_PAGENUM;
        $checkGoodsDetailList = WarehouseService::getWarehouseCheckList($searchParam,$page,$pageSize);
        return $checkGoodsDetailList;
    }

    public function actionAddWarehouseScrap(){
        $params = Yii::$app->request->post();
        WarehouseService::addWarehouseScrap($params);
        return [];
    }

    public function actionGetWarehouseScrapInfo(){
        $params = Yii::$app->request->post();
        $scrapInfo = WarehouseService::getWarehouseScrapInfo($params['scrap_id']);
        $result['warehouse_scrap_info'] = $scrapInfo;
        return $result;
    }

    public function actionGetWarehouseScrapList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::SCRAP_GOODS_LIST_PAGENUM;
        $scrapList = WarehouseService::getWarehouseScrapList($searchParam,$page,$pageSize);
        return $scrapList;
    }

    public function actionGetWarehouseScrapDetailList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::SCRAP_GOODS_LIST_DETAIL_PAGENUM;
        $scrapDetailList = WarehouseService::getWarehouseScrapDetailList($searchParam,$page,$pageSize);
        return $scrapDetailList;
    }

    public function actionAddWarehouse(){
        $params = Yii::$app->request->post();
        WarehouseService::addWarehouse($params);
        return [];
    }

    public function actionEditWarehouse(){
        $params = Yii::$app->request->post();
        WarehouseService::editWarehouse($params);
        return [];
    }

    public function actionGetWarehouseList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_LIST_PAGENUM;
        $warehouseList = WarehouseService::getWarehouseList($searchParam,$page,$pageSize);
        return $warehouseList;
    }

    public function actionGetGoodsWarehouseNum(){
        $params = Yii::$app->request->post();
        $goodsWarehouseNum = WarehouseService::getGoodsWarehouseNum($params['goods_id']);
        $result['goods_num'] = $goodsWarehouseNum;
        return $result;
    }

    public function actionGetWarehouseInfo(){
        $params = Yii::$app->request->post();
        $warehouseInfo = WarehouseService::getWarehouseInfo($params['warehouse_id']);
        $result['warehouse_info'] = $warehouseInfo;
        return $result;
    }

    public function actionGetWarehouseOneList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::WAREHOUSE_LIST_DETAIL_PAGENUM;
        $warehouseOneList = WarehouseService::getWarehouseOneList($searchParam,$page,$pageSize);
        return $warehouseOneList;
    }

    public function actionAddBalanceAccount(){
        $params = Yii::$app->request->post();
        $balanceInfo = WarehouseService::addBalanceAccount($params);
        return $balanceInfo;
    }

    public function actionGetBalanceAccountList(){
        $params = Yii::$app->request->post();
        $searchParam = $params['search_param'];
        $page = !empty($params['page'])?$params['page']:1;
        $pageSize = !empty($params['page_size'])?$params['page_size']:WarehouseService::BALANCE_ACCOUNT_PAGENUM;
        $balanceAccountList = WarehouseService::getBalanceAccountList($searchParam,$page,$pageSize);
        return $balanceAccountList;
    }

    public function actionGetSearchBalanceAccountList(){
        $balanceAccountStatusList = Yii::$app->params['balance_status_list'];
        $balanceFinanceAccountStatusList = Yii::$app->params['balance_finance_status_list'];
        $result['balance_status_list'] = $balanceAccountStatusList;
        $result['balance_finance_status_list'] = $balanceFinanceAccountStatusList;
        return $result;
    }

    public function actionGetBalanceAccountInfo(){
        $params = Yii::$app->request->post();
        $balanceAccountInfo = WarehouseService::getBalanceAccountInfo($params['balance_id']);
        $result['balance_account_info'] = $balanceAccountInfo;
        return $result;
    }

    public function actionCloseBalanceAccount(){
        $params = Yii::$app->request->post();
        WarehouseService::closeBalanceAccount($params['balance_id']);
        return [];
    }

    public function actionChangeBalanceAccount(){
        $params = Yii::$app->request->post();
        WarehouseService::changeBalanceAccount($params);
        return [];
    }

    public function actionGetWarehouseOutGoods(){
        $params = Yii::$app->request->post();
        $result = WarehouseService::getWarehouseOutGoods($params['order_id']);
        return $result;
    }

}