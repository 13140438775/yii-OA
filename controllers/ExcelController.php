<?php

namespace app\controllers;

use app\models\BalanceAccount;
use app\models\PurchaseDetail;
use app\models\WarehouseIn;
use app\models\WarehouseOut;
use app\services\ChargebackService;
use app\services\CustomerOrderService;
use app\services\GoodsService;
use Yii;
use Box\Spout\Common\Type;
use Box\Spout\Writer\WriterFactory;
use app\services\SupplierService;
use app\services\PurchaseService;
use app\services\WarehouseService;
use app\services\OrderEntryService;
use yii\base\Controller;
use app\models\Order;
use yii\helpers\VarDumper;

class ExcelController extends Controller
{

    const pageNum = 3000;

    public function actionExportSupplierExcel() {
        $params = Yii::$app->request->get();
        $searchParam['supplier_name'] = $params['supplier_name'];
        $searchParam['contact_name'] = $params['contact_name'];
        $searchParam['area_id'] = $params['area_id'];
        $searchParam['phone'] = $params['phone'];
        $supplierListQuery       = SupplierService::getSuppliersListQurey($searchParam);
        $count =  $supplierListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("供应商报表.xlsx");
        $excelHead   = [
            '供应商名称',
            '联系人',
            '联系人电话',
            '邮箱',
            '联系区域'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $supplierList  = SupplierService::getSuppliersList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($supplierList['supplier_list'] AS $supplier) {
                $excelData[] = [
                    !empty($supplier['supplier_name'])?$supplier['supplier_name']:'',
                    !empty($supplier['contact_name'])?$supplier['contact_name']:'',
                    !empty($supplier['phone'])?$supplier['phone']:'',
                    !empty($supplier['email'])?$supplier['email']:'',
                    !empty($supplier['area_name'])?$supplier['area_name']:'',
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }


    public function actionExportPurchaseExcel() {
        $params = Yii::$app->request->get();
        $searchParam['purchaseOrSupplier'] = $params['purchaseOrSupplier'];
        $searchParam['purchase_start_time'] = $params['purchase_start_time'];
        $searchParam['purchase_end_time'] = $params['purchase_end_time'];
        $searchParam['finish_start_time'] = $params['finish_start_time'];
        $searchParam['finish_end_time'] = $params['finish_end_time'];
        $searchParam['purchase_status'] = $params['purchase_status'];
        $searchParam['order_id'] = $params['order_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['gym_name'] = $params['gym_name'];
        $purchaseListQuery       = PurchaseService::getPurchasesQuery($searchParam);
        $count =  $purchaseListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("采购单报表.xlsx");
        $excelHead   = [
            '采购单编号',
            '订单编号',
            '入库单编号',
            '门店信息',
            '供应商',
            '实际总金额',
            '采购时间',
            '预计到货时间',
            '订单状态'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $purchaseList  = PurchaseService::getPurchasesList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($purchaseList['purchase_list'] AS $purchase) {
                $excelData[] = [
                    $purchase['purchase_id'],
                    $purchase['order_id'],
                    $purchase['warehouse_in_id'],
                    $purchase['gym_name'],
                    $purchase['supplier_name'],
                    $purchase['actual_amount'],
                    $purchase['purchase_time'],
                    $purchase['finish_time'],
                    PurchaseService::getPurchaseStatus($purchase['purchase_status'])
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseInExcel() {
        $params = Yii::$app->request->get();
        $searchParam['warehouse_in_start_time'] = $params['warehouse_in_start_time'];
        $searchParam['warehouse_in_end_time'] = $params['warehouse_in_end_time'];
        $searchParam['warehouse_status'] = $params['warehouse_status'];
        $searchParam['warehouse_in_id'] = $params['warehouse_in_id'];
        $searchParam['purchase_id'] = $params['purchase_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['supplier_name'] = $params['supplier_name'];
        $warehouseInListQuery       = WarehouseService::getWarehouseInQuery($searchParam);
        $count =  $warehouseInListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("入库单报表.xlsx");
        $excelHead   = [
            '入库单编号',
            '采购单编号',
            '供应商',
            '收货仓库',
            '总金额',
            '应收数量',
            '实收数量',
            '入库时间',
            '入库状态'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseInList  = WarehouseService::getWarehouseInList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseInList['warehouse_in_list'] AS $warehouseIn) {
                $excelData[] = [
                    $warehouseIn['warehouse_in_id'],
                    $warehouseIn['purchase_id'],
                    $warehouseIn['supplier_name'],
                    $warehouseIn['warehouse_name'],
                    $warehouseIn['total_amount'],
                    $warehouseIn['except_num'],
                    $warehouseIn['actual_num'],
                    $warehouseIn['in_time'],
                    $warehouseIn['warehouse_status'] == WarehouseIn::INITIAL_STATUS ? '初始状态' : '已入库'
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseInDetailExcel() {
        $params = Yii::$app->request->get();
        $searchParam['warehouse_in_start_time'] = $params['warehouse_in_start_time'];
        $searchParam['warehouse_in_end_time'] = $params['warehouse_in_end_time'];
        $searchParam['warehouse_status'] = $params['warehouse_status'];
        $searchParam['warehouse_in_id'] = $params['warehouse_in_id'];
        $searchParam['purchase_id'] = $params['purchase_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['supplier_name'] = $params['supplier_name'];
        $searchParam['goods_name'] = $params['goods_name'];
        $warehouseListDetailQuery       = WarehouseService::getWarehouseInDetailQuery($searchParam);
        $count =  $warehouseListDetailQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("入库单明细报表.xlsx");
        $excelHead   = [
            '入库单编号',
            '采购单编号',
            '商品名称',
            '供应商',
            '收货仓库',
            '总金额',
            '应收数量',
            '实收数量',
            '入库时间',
            '入库状态'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseInDetailList  = WarehouseService::getWarehouseInDetailList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseInDetailList['warehouse_in_detail_list'] AS $warehouseInDetail) {
                $excelData[] = [
                    $warehouseInDetail['warehouse_in_id'],
                    $warehouseInDetail['purchase_id'],
                    $warehouseInDetail['goods_name'],
                    $warehouseInDetail['supplier_name'],
                    $warehouseInDetail['warehouse_name'],
                    $warehouseInDetail['total_amount'],
                    $warehouseInDetail['except_num'],
                    $warehouseInDetail['actual_num'],
                    $warehouseInDetail['in_time'],
                    $warehouseInDetail['warehouse_status'] == WarehouseIn::INITIAL_STATUS ? '初始状态' : '已入库'
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }


    public function actionExportWarehouseOutExcel() {
        $params = Yii::$app->request->get();
        $searchParam['warehouse_out_start_time'] = $params['warehouse_out_start_time'];
        $searchParam['warehouse_out_end_time'] = $params['warehouse_out_end_time'];
        $searchParam['out_status'] = $params['out_status'];
        $searchParam['warehouse_out_id'] = $params['warehouse_out_id'];
        $searchParam['order_id'] = $params['order_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['gym_name'] = $params['gym_name'];
        $warehouseOutListQuery       = WarehouseService::getWarehouseOutQuery($searchParam);
        $count =  $warehouseOutListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("出库单报表.xlsx");
        $excelHead   = [
            '出库单编号',
            '订单编号',
            '门店名称',
            '供应商',
            '出库仓库',
            '预计到货时间',
            '出库时间',
            '出库状态'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseOutList  = WarehouseService::getWarehouseOutList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseOutList['warehouse_out_list'] AS $warehouseOut) {
                $excelData[] = [
                    $warehouseOut['warehouse_out_id'],
                    $warehouseOut['order_id'],
                    $warehouseOut['gym_name'],
                    $warehouseOut['supplier_name'],
                    $warehouseOut['warehouse_name'],
                    $warehouseOut['out_time'],
                    $warehouseOut['arrival_time'],
                    $warehouseOut['out_status'] == WarehouseOut::INITIAL_STATUS ? '初始状态' : '已出库'
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseOutDetailExcel() {
        $params = Yii::$app->request->get();
        $searchParam['warehouse_out_start_time'] = $params['warehouse_out_start_time'];
        $searchParam['warehouse_out_end_time'] = $params['warehouse_out_end_time'];
        $searchParam['out_status'] = $params['out_status'];
        $searchParam['warehouse_out_id'] = $params['warehouse_out_id'];
        $searchParam['order_id'] = $params['order_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['goods_name'] = $params['goods_name'];
        $warehouseOutDetailListQuery       = WarehouseService::getWarehouseOutDetailQuery($searchParam);
        $count =  $warehouseOutDetailListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("出库单明细报表.xlsx");
        $excelHead   = [
            '出库单编号',
            '订单编号',
            '门店名称',
            '商品名称',
            '供应商',
            '出库仓库',
            '预计到货时间',
            '出库时间',
            '出库状态'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseOutDetailList  = WarehouseService::getWarehouseOutDetailList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseOutDetailList['warehouse_out_detail_list'] AS $warehouseOutDetail) {
                $excelData[] = [
                    $warehouseOutDetail['warehouse_out_id'],
                    $warehouseOutDetail['order_id'],
                    $warehouseOutDetail['gym_name'],
                    $warehouseOutDetail['goods_name'],
                    $warehouseOutDetail['supplier_name'],
                    $warehouseOutDetail['warehouse_name'],
                    $warehouseOutDetail['out_time'],
                    $warehouseOutDetail['arrival_time'],
                    $warehouseOutDetail['out_status'] == WarehouseOut::INITIAL_STATUS ? '初始状态' : '已出库'
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportDepartmentExcel() {
        $params = Yii::$app->request->get();
        $searchParam['get_goods_start_time'] = $params['get_goods_start_time'];
        $searchParam['get_goods_end_time'] = $params['get_goods_end_time'];
        $searchParam['get_goods_id'] = $params['get_goods_id'];
        $searchParam['department_id'] = $params['department_id'];
        $getGetGoodsListQuery       = WarehouseService::getGetGoodsQuery($searchParam);
        $count =  $getGetGoodsListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("领货单报表.xlsx");
        $excelHead   = [
            '领货单编号',
            '领货人',
            '部门',
            '仓库名称',
            '领货时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $getGetGoodsList  = WarehouseService::getGetGoodsList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($getGetGoodsList['get_goods_list'] AS $getGoods) {
                $excelData[] = [
                    $getGoods['get_goods_id'],
                    $getGoods['get_user_name'],
                    $getGoods['name'],
                    $getGoods['warehouse_name'],
                    $getGoods['get_time'],
                    $getGoods['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportReturnExcel() {
        $params = Yii::$app->request->get();
        $searchParam['return_goods_start_time'] = $params['return_goods_start_time'];
        $searchParam['return_goods_end_time'] = $params['return_goods_end_time'];
        $searchParam['return_id'] = $params['return_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $returnGoodsListQuery       = WarehouseService::getReturnGoodsQuery($searchParam);
        $count =  $returnGoodsListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("退货单报表.xlsx");
        $excelHead   = [
            '退货单编号',
            '入库单编号',
            '供应商',
            '仓库名称',
            '退货时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $returnGoodsList  = WarehouseService::getReturnGoodsList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($returnGoodsList['return_goods_list'] AS $returnGoods) {
                $excelData[] = [
                    $returnGoods['return_id'],
                    $returnGoods['warehouse_in_id'],
                    $returnGoods['supplier_name'],
                    $returnGoods['warehouse_name'],
                    $returnGoods['return_time'],
                    $returnGoods['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportReturnDetailExcel() {
        $params = Yii::$app->request->get();
        $searchParam['return_goods_start_time'] = $params['return_goods_start_time'];
        $searchParam['return_goods_end_time'] = $params['return_goods_end_time'];
        $searchParam['return_id'] = $params['return_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['goods_name'] = $params['goods_name'];
        $returnGoodsDetailListQuery       = WarehouseService::getReturnGoodsDetailQuery($searchParam);
        $count =  $returnGoodsDetailListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("退货单明细报表.xlsx");
        $excelHead   = [
            '退货单编号',
            '入库单编号',
            '商品名称',
            '供应商',
            '仓库名称',
            '退货时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $returnGoodsDetailList  = WarehouseService::getReturnGoodsDetailList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($returnGoodsDetailList['return_goods_detail_list'] AS $returnGoodsDetail) {
                $excelData[] = [
                    $returnGoodsDetail['return_id'],
                    $returnGoodsDetail['warehouse_in_id'],
                    $returnGoodsDetail['goods_name'],
                    $returnGoodsDetail['supplier_name'],
                    $returnGoodsDetail['warehouse_name'],
                    $returnGoodsDetail['return_time'],
                    $returnGoodsDetail['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseCheckExcel() {
        $params = Yii::$app->request->get();
        $searchParam['check_goods_start_time'] = $params['check_goods_start_time'];
        $searchParam['check_goods_end_time'] = $params['check_goods_end_time'];
        $searchParam['check_id'] = $params['check_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $checkGoodsListQuery       = WarehouseService::getCheckGoodsQuery($searchParam);
        $count =  $checkGoodsListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("盘点单报表.xlsx");
        $excelHead   = [
            '盘点单编号',
            '仓库名称',
            '盘点时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $checkGoodsList  = WarehouseService::getWarehouseCheckList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($checkGoodsList['check_goods_list'] AS $checkGoods) {
                $excelData[] = [
                    $checkGoods['check_id'],
                    $checkGoods['warehouse_name'],
                    $checkGoods['check_time'],
                    $checkGoods['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportBalanceExcel() {
        $params = Yii::$app->request->get();
        $searchParam['balance_id'] = $params['balance_id'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['balance_status'] = $params['balance_status'];
        $searchParam['balance_type'] = $params['balance_type'];
        $BalanceAccountListQuery       = WarehouseService::getBalanceAccountListQuery($searchParam);
        $count =  $BalanceAccountListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("对账单报表.xlsx");
        $excelHead   = [
            '对账单号',
            '对账时间',
            '对账状态',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $balanceAccountList  = WarehouseService::getBalanceAccountList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($balanceAccountList['balance_account_list'] AS $balanceAccount) {
                $excelData[] = [
                    $balanceAccount['balance_id'],
                    $balanceAccount['balance_start_time'].'-'.$balanceAccount['balance_end_time'],
                    WarehouseService::getBalanceStatus($balanceAccount['balance_status'],$searchParam['balance_type']),
                    $balanceAccount['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseScrapExcel() {
        $params = Yii::$app->request->get();
        $searchParam['scrap_goods_start_time'] = $params['scrap_goods_start_time'];
        $searchParam['scrap_goods_end_time'] = $params['scrap_goods_end_time'];
        $searchParam['scrap_id'] = $params['scrap_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $warehouseScrapListQuery       = WarehouseService::getScrapListQuery($searchParam);
        $count =  $warehouseScrapListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("报废单报表.xlsx");
        $excelHead   = [
            '报废单编号',
            '仓库名称',
            '报废数量',
            '报废时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseScrapList  = WarehouseService::getWarehouseScrapList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseScrapList['scrap_list'] AS $warehouseScrap) {
                $excelData[] = [
                    $warehouseScrap['scrap_id'],
                    $warehouseScrap['warehouse_name'],
                    $warehouseScrap['scrap_num'],
                    $warehouseScrap['scrap_time'],
                    $warehouseScrap['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseScrapDetailExcel() {
        $params = Yii::$app->request->get();
        $searchParam['scrap_goods_start_time'] = $params['scrap_goods_start_time'];
        $searchParam['scrap_goods_end_time'] = $params['scrap_goods_end_time'];
        $searchParam['scrap_id'] = $params['scrap_id'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['goods_name'] = $params['goods_name'];
        $warehouseScrapDetailListQuery       = WarehouseService::getScrapDetailListQuery($searchParam);
        $count =  $warehouseScrapDetailListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("报废单明细报表.xlsx");
        $excelHead   = [
            '报废单编号',
            '商品名称',
            '仓库名称',
            '报废数量',
            '报废时间',
            '操作人'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseScrapDetailList  = WarehouseService::getWarehouseScrapDetailList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseScrapDetailList['scrap_detail_list'] AS $warehouseScrapDetail) {
                $excelData[] = [
                    $warehouseScrapDetail['scrap_id'],
                    $warehouseScrapDetail['goods_name'],
                    $warehouseScrapDetail['warehouse_name'],
                    $warehouseScrapDetail['scrap_num'],
                    $warehouseScrapDetail['scrap_time'],
                    $warehouseScrapDetail['operator_name']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseExcel() {
        $params = Yii::$app->request->get();
        $searchParam['type_id'] = $params['type_id'];
        $searchParam['goods_name'] = $params['goods_name'];
        $searchParam['is_warn'] = $params['is_warn'];
        $warehouseListQuery       = WarehouseService::getWarehouseListQuery($searchParam);
        $count =  $warehouseListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("总仓库预览报表.xlsx");
        $excelHead   = [
            '商品编码',
//            '图片',
            '商品名称',
            '商品类型',
            '单位',
            '总库存',
            '良品库存',
            '次品库存'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseList  = WarehouseService::getWarehouseList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseList['warehouse_list'] AS $warehouse) {
                $excelData[] = [
                    $warehouse['goods_id'],
//                    !empty($warehouse['img'])?$warehouse['img'][0]['goods_img']:'',
                    $warehouse['goods_name'],
                    $warehouse['type_name'],
                    $warehouse['unit'],
                    $warehouse['total'],
                    $warehouse['inventory_total'],
                    $warehouse['defective_inventory_total']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportWarehouseOneExcel() {
        $params = Yii::$app->request->get();
        $searchParam['type_id'] = $params['type_id'];
        $searchParam['goods_name'] = $params['goods_name'];
        $searchParam['warehouse_id'] = $params['warehouse_id'];
        $searchParam['is_warn'] = $params['is_warn'];
        $warehouseOneListQuery       = WarehouseService::getWarehouseOneListQuery($searchParam);
        $count =  $warehouseOneListQuery->count('1');
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("单仓库预览报表.xlsx");
        $excelHead   = [
            '商品编码',
//            '图片',
            '商品名称',
            '商品类型',
            '单位',
            '总库存',
            '良品库存',
            '次品库存'
        ];
        $writer->addRow($excelHead);
        $page      = 1;
        $pageNum   = self::pageNum;
        do {
            $warehouseOneList  = WarehouseService::getWarehouseOneList($searchParam, $page, $pageNum);
            $excelData = [];
            foreach ($warehouseOneList['warehouse_one_list'] AS $warehouseOne) {
                $excelData[] = [
                    $warehouseOne['goods_id'],
//                    !empty($warehouseOne['img'])?$warehouseOne['img'][0]['goods_img']:'',
                    $warehouseOne['goods_name'],
                    $warehouseOne['type_name'],
                    $warehouseOne['unit'],
                    $warehouseOne['inventory'] + $warehouseOne['defective_inventory'],
                    $warehouseOne['inventory'],
                    $warehouseOne['defective_inventory']
                ];
            }
            $writer->addRows($excelData);
        }
        while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportPurchaseGoods(){
        $purchase_id = \Yii::$app->request->get("purchase_id");
        $purchaseDetailGoodslist = PurchaseDetail::find()
            ->with('goods')
            ->where(['purchase_id'=>$purchase_id,'relation_status'=>AVAILABLE])
            ->asArray()
            ->all();
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("采购商品详情列表.xlsx");
        $excelHead   = [
            '商品编号',
            '商品名称',
            '品牌',
            '型号',
            '单位',
            '含税单价',
            '购买数量',
            '金额'
        ];
        $writer->addRow($excelHead);
        $excelData = [];
        foreach ($purchaseDetailGoodslist as $goods) {
            $excelData[] = [
                $goods['goods']['goods_id'],
                $goods['goods']['goods_name'],
                $goods['goods']['brand'],
                $goods['goods']['model'],
                $goods['goods']['unit'],
                $goods['goods']['purchase_amount'] / 100,
                $goods['purchase_num'],
                $goods['actual_amount'] / 100
            ];
        }
        $writer->addRows($excelData);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportBalanceDetail(){
        $balanceId = \Yii::$app->request->get("balance_id");
        $balanceInfo = WarehouseService::getBalanceAccountInfo($balanceId);
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("对账单详情列表.xlsx");
        $excelHead   = [
            '单号',
            '操作类型',
            '操作时间',
            '仓库名称',
            '数量',
            '金额',
        ];
        $writer->addRow($excelHead);
        foreach ($balanceInfo['balance_list'] as $key=>$value) {
            foreach ($value['supplier_list'] as $k=>$v){
                $writer->addRow([
                    $v['id'],
                    $v['type'],
                    $v['time'],
                    $v['warehouse_name'],
                    $v['total_num'],
                    $v['total_amount']
                ]);
            }
            $writer->addRow([
                '供应商:'.$value['supplier_name'],
                '',
                '',
                '',
                '总数量:'.$value['total_num'],
                '总金额:'.$value['total_amount']
            ]);
        }
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportGoodsExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['goods_name'] = $params['goods_name'];
        $searchParam['type_id'] = $params['type_id'];
        $searchParam['supplier_id'] = $params['supplier_id'];
        $goodsLists = GoodsService::getGoodsList($searchParam, $export = true);
        $count = count($goodsLists);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("商品报表.xlsx");
        $excelHead = [
            '商品编码',
            '商品名称',
            '商品类别',
            '供应商',
            '型号',
            '单位',
            '采购价',
            '销售价',
            '预警量',
            '起订量',
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($goodsLists AS $goods) {
                $excelData[] = [
                    $goods['goods_id'],
                    $goods['goods_name'],
                    $goods['type_name'],
                    $goods['supplier'][0]['supplier_name'],
                    $goods['model'],
                    $goods['unit'],
                    $goods['purchase_amount'] / 100,
                    $goods['price'] / 100,
                    $goods['warn'],
                    $goods['min_sell'],
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportCustomerOrderExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['search'] = $params['search'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['contact_phone'] = $params['contact_phone'];
        $searchParam['contact_name'] = $params['contact_name'];
        $searchParam['operator_name'] = $params['operator_name'];
        $searchParam['last_operator_name'] = $params['last_operator_name'];
        $searchParam['order_id'] = $params['order_id'];
        $searchParam['order_status'] = $params['order_status'];
        $orderLists = CustomerOrderService::getOrderList($searchParam,$addition = null,$export = true);
        $count = count($orderLists);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("客服订单报表.xlsx");
        $excelHead = [
            '订单编号',
            '订单日期',
            '门店名称',
            '联系人',
            '联系方式',
            '总金额',
            '订单状态',
            '制单人',
            '最后操作人'
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($orderLists AS $order) {
                $excelData[] = [
                    $order['order_id'],
                    !empty($chargeOrder['order_time'])?date("Y-m-d H:i:s",$order['order_time']):'',
                    $order['gym_name'],
                    $order['contact_name'],
                    $order['contact_phone'],
                    $order['actual_amount'] / 100,
                    CustomerOrderService::getOrderType($order['order_status']),
                    $order['operator_name'],
                    $order['last_operator_name'],
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportChargeBackExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['search'] = $params['search'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['chargeback_id'] = $params['chargeback_id'];
        $searchParam['chargeback_status'] = $params['chargeback_status'];
        $searchParam['contact_name'] = $params['contact_name'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['operator_name'] = $params['operator_name'];
        $searchParam['last_operator_name'] = $params['last_operator_name'];
        $searchParam['order_id'] = $params['order_id'];
        $chargeOrderLists = ChargebackService::getList($searchParam,$export = true);
        $count = count($chargeOrderLists);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("客服退单报表.xlsx");
        $excelHead = [
            '退单编号',
            '订单编号',
            '退单日期',
            '健身房名称',
            '联系人',
            '退单状态',
            '制单人',
            '最后操作人'
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($chargeOrderLists AS $chargeOrder) {
                $excelData[] = [
                    $chargeOrder['chargeback_id'],
                    $chargeOrder['order_id'],
                    !empty($chargeOrder['chargeback_time'])?date("Y-m-d H:i:s",$chargeOrder['chargeback_time']):'',
                    $chargeOrder['gym_name'],
                    $chargeOrder['contact_name'],
                    ChargebackService::getOrderType($chargeOrder['chargeback_status']),
                    $chargeOrder['operator_name'],
                    $chargeOrder['last_operator_name'],
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportFinOrderExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['search'] = $params['search'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['finance_check_status'] = $params['finance_check_status'];
        $searchParam['gym_name'] = $params['gym_name'];
        $addition = ['>=','finance_check_status',Order::FIN_STATUS_WAIT];
        $finOrderLists = CustomerOrderService::getOrderList($searchParam,$addition,$export = true);
        $count = count($finOrderLists);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("财务订单报表.xlsx");
        $excelHead = [
            '订单编号',
            '门店信息',
            '订单日期',
            '实际总金额',
            '订单状态',
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($finOrderLists as $finOrder) {
                $excelData[] = [
                    $finOrder['order_id'],
                    $finOrder['gym_name'],
                    !empty($finOrder['order_time'])?date("Y-m-d H:i:s",$finOrder['order_time']):'',
                    $finOrder['actual_amount'] / 100,
                    CustomerOrderService::getFinOrderType($finOrder['finance_check_status']),
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportChargebackFinOrderExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['chargeback_id'] = $params['chargeback_id'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['refund_finance_status'] = $params['refund_finance_status'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['fin_status'] = ChargebackService::PUR_STATUS;
        $chargebackFinOrderLists = ChargebackService::getList($searchParam,$export=true);
        $count = count($chargebackFinOrderLists);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("财务退单报表.xlsx");
        $excelHead = [
            '退单编号',
            '门店信息',
            '退单时间',
            '退款金额',
            '订单状态',
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($chargebackFinOrderLists as $chargebackFinOrder) {
                $excelData[] = [
                    $chargebackFinOrder['chargeback_id'],
                    $chargebackFinOrder['gym_name'],
                    !empty($chargebackFinOrder['chargeback_time'])?date("Y-m-d H:i:s",$chargebackFinOrder['chargeback_time']):'',
                    $chargebackFinOrder['refund_amount'] / 100,
                    ChargebackService::getChargebackFinOrderType($chargebackFinOrder['refund_finance_status']),
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportPurchaseOrderExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['order_id'] = $params['order_id'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['order_status'] = $params['order_status'];
        $searchParam['order_name'] = $params['order_name'];
        $searchParam['order_type'] = $params['order_type'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $pur = CustomerOrderService::PURCHASE; //采购中
        $ship = CustomerOrderService::SHIP;    //发货中
        $complete = CustomerOrderService::COMPLETE; //已完成
        $addition = ['order_type'=>$params['order_type'],'order_status' => [$pur,$ship,$complete]];
        $purchaseOrderList = CustomerOrderService::getOrderList($searchParam,$addition,$export=true);
        $count = count($purchaseOrderList);
        if($params['order_type'] == 1){
            $writer = WriterFactory::create(Type::XLSX)
                ->openToBrowser("采购开店订单报表.xlsx");
            $excelHead = [
                '订单编号',
                '订单时间',
                '订单名称',
                '门店名称',
                '总金额',
                '订单状态',
            ];
        }else{
            $writer = WriterFactory::create(Type::XLSX)
                ->openToBrowser("采购补货订单报表.xlsx");
            $excelHead = [
                '订单编号',
                '订单时间',
                '门店名称',
                '总金额',
                '订单状态',
            ];
        }

        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($purchaseOrderList as $purchaseOrder) {
                if($params['order_type'] == 1){
                    $excelData[] = [
                        $purchaseOrder['order_id'],
                        !empty($purchaseOrder['order_time'])?date("Y-m-d H:i:s",$purchaseOrder['order_time']):'',
                        $purchaseOrder['order_name'],
                        $purchaseOrder['gym_name'],
                        $purchaseOrder['actual_amount'] / 100,
                        CustomerOrderService::getOrderType($purchaseOrder['order_status']),
                    ];
                }else{
                    $excelData[] = [
                        $purchaseOrder['order_id'],
                        !empty($purchaseOrder['order_time'])?date("Y-m-d H:i:s",$purchaseOrder['order_time']):'',
                        $purchaseOrder['gym_name'],
                        $purchaseOrder['actual_amount'] / 100,
                        CustomerOrderService::getOrderType($purchaseOrder['order_status']),
                    ];
                }

            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    public function actionExportPurchaseChargebackOrderExcel()
    {
        $params = Yii::$app->request->get();
        $searchParam['chargeback_id'] = $params['chargeback_id'];
        $searchParam['start_time'] = $params['start_time'];
        $searchParam['end_time'] = $params['end_time'];
        $searchParam['orderNumber'] = $params['orderNumber'];
        $searchParam['orderStatus'] = $params['orderStatus'];
        $searchParam['gym_name'] = $params['gym_name'];
        $searchParam['pur_status'] = ChargebackService::PUR_STATUS;
        $purchaseChargebackOrderList = ChargebackService::getList($searchParam,$export=true);
        $count = count($purchaseChargebackOrderList);
        $writer = WriterFactory::create(Type::XLSX)
            ->openToBrowser("采购退单报表.xlsx");
        $excelHead = [
            '退单编号',
            '订单编号',
            '退单时间',
            '门店名称',
            '总金额',
            '订单状态',
        ];
        $writer->addRow($excelHead);
        $page = 1;
        $pageNum = self::pageNum;
        do {
            $excelData = [];
            foreach ($purchaseChargebackOrderList as $purchaseChargebackOrder) {
                $excelData[] = [
                    $purchaseChargebackOrder['chargeback_id'],
                    $purchaseChargebackOrder['order_id'],
                    !empty($purchaseChargebackOrder['chargeback_time'])?date("Y-m-d H:i:s",$purchaseChargebackOrder['chargeback_time']):'',
                    $purchaseChargebackOrder['gym_name'],
                    $purchaseChargebackOrder['refund_amount'] / 100,
                    ChargebackService::getChargebackPurOrderType($purchaseChargebackOrder['refund_pur_status']),
                ];
            }
            $writer->addRows($excelData);
        } while ($count > $pageNum * $page++);
        Yii::$app->response->detachBehavior('ResponseBehavior');
        $writer->close();
    }

    /**
     * 订单列表
     */
    public function actionOrderList()
    {
        $project_id = \Yii::$app->request->get("project_id");
        $order_list = OrderEntryService::orderList($project_id);
        $writer      = WriterFactory::create(Type::XLSX)
            ->openToBrowser("订单详情列表.xlsx");
        $excelHead   = [
            '订单',
            '订单编码',
            '订货数量',
            '订单总额',
            '优惠金额',
            '应付金额'
        ];
        $writer->addRow($excelHead);
        $excelData = [];
        foreach ($order_list['order_list'] as $val) {
            $excelData[] = [
                'order_type' => $val['order_type'],
                'order_id' => $val['order_id'],
                'total_num' => $val['total_num'],
                'total_amount' => $val['total_amount'],
                'coupon_amount' => $val['coupon_amount'],
                'actual_amount' => $val['actual_amount']
            ];
        }
        $writer->addRows($excelData);
        $writer->close();
    }
}
