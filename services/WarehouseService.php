<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:53:22
 */
namespace app\services;

use Yii;
use app\models\Goods;
use app\models\ReturnGoods;
use app\models\ReturnGoodsDetail;
use app\models\WarehouseCheck;
use app\models\WarehouseCheckDetail;
use app\models\WarehouseScrap;
use app\models\WarehouseScrapDetail;
use app\models\Warehouse;
use app\models\WarehouseIn;
use app\models\WarehouseInDetail;
use app\models\Purchase;
use app\models\Remark;
use app\models\WarehouseGoods;
use app\models\WarehouseOut;
use app\models\WarehouseOutDetail;
use app\models\Department;
use app\models\GetGoods;
use app\models\GetGoodsDetail;
use app\models\OrderDetail;
use app\models\BalanceAccount;
use app\models\BalanceAccountDetail;
use yii\helpers\ArrayHelper;
use app\helpers\Lock;

class WarehouseService{

    const WAREHOUSE_GOOD_LISTS = 15;
    const WAREHOUSE_IN_LIST_PAGENUM = 15;
    const WAREHOUSE_IN_LIST_DETAIL_PAGENUM = 15;
    const WAREHOUSE_OUT_LIST_PAGENUM = 15;
    const WAREHOUSE_OUT_LIST_DETAIL_PAGENUM =15;
    const GET_GOODS_LIST_PAGENUM = 15;
    const RETURN_GOODS_LIST_PAGENUM = 15;
    const RETURN_GOODS_LIST_DETAIL_PAGENUM = 15;
    const CHECK_GOODS_LIST_PAGENUM = 15;
    const SCRAP_GOODS_LIST_PAGENUM = 15;
    const SCRAP_GOODS_LIST_DETAIL_PAGENUM = 15;
    const WAREHOUSE_LIST_PAGENUM = 15;
    const WAREHOUSE_LIST_DETAIL_PAGENUM = 15;
    const BALANCE_ACCOUNT_PAGENUM = 15;
    const CLOSE_BALANCE = 1;
    const OPEN_BALANCE = 2;
    const WAREHOUSE_ID = '';

    public static function getWarehouse(){
        $warehouse = Warehouse::getWarehouse();
        return $warehouse;
    }

    public static function getGoodsList($searchParam,$page,$pageNum){
        $warehouseGoodsQuery = self::getWarehouseGoodsQuery($searchParam);
        $totalCount = $warehouseGoodsQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $warehouseGoodsQuery = $warehouseGoodsQuery->offset($offset)->limit($pageNum)->orderBy([
            't_warehouse_goods.create_time' => SORT_DESC
        ]);
        $warehouseGoodsList = $warehouseGoodsQuery->asArray()->all();
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_goods_list'  => $warehouseGoodsList,
        ];
    }

    public static function getWarehouseGoodsQuery($searchParam){
        $fields = [
            't_goods.goods_id',
            't_goods.goods_name',
            't_goods.brand',
            't_goods.model',
            't_goods.unit',
            't_goods.purchase_amount',
            't_warehouse_goods.inventory',
            't_warehouse_goods.defective_inventory',
            't_supplier.supplier_name',
            't_supplier.id as supplier_id'
        ];
        $warehouseGoodsQuery = WarehouseGoods::find()
            ->select($fields)
            ->leftJoin('t_goods', 't_goods.goods_id = t_warehouse_goods.goods_id')
            ->leftJoin('t_goods_supplier', 't_goods.goods_id = t_goods_supplier.goods_id')
            ->leftJoin('t_supplier', 't_supplier.id = t_goods_supplier.supplier_id')
            ->where([
                't_goods_supplier.relation_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['goods_id'])){
            $warehouseGoodsQuery->andWhere(['t_goods.goods_id'=>$searchParam['goods_id']]);
        }
        if(!empty($searchParam['goods_name'])){
            $warehouseGoodsQuery->andWhere(['LIKE', 't_goods.goods_name', $searchParam['goods_name']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $warehouseGoodsQuery->andWhere(['t_warehouse_goods.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        return $warehouseGoodsQuery;
    }

    //入库单
    public static function addGodownEntry($purchaseId){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $purchase = PurchaseService::getPurchaseInfo($purchaseId);
            if(!empty($purchase)){
                PurchaseService::updatePurchase($purchaseId);
                $warehouseInId = generateIntId('RK');
                $purchaseTotal = 0;
                foreach ($purchase['purchaseDetail'] as $key=>$detail){
                    $purchase['purchaseDetail'][$key]['order_id'] = $purchase['order_id'];
                    $purchaseTotal +=  $detail['arrival_num'];
                }
                $purchaseParams['order_id'] = $purchase['order_id'];
                $purchaseParams['purchase_id'] = $purchase['purchase_id'];
                $purchaseParams['supplier_id'] = $purchase['supplier_id'];
                $purchaseParams['warehouse_id'] = $purchase['warehouse_id'];
                $purchaseParams['warehouse_name'] = $purchase['warehouse']['warehouse_name'];
                $purchaseParams['except_num'] = $purchaseTotal;
                if(!empty($purchase['order_id'])){
                    $purchaseParams['actual_num'] = $purchaseTotal;
                }else{
                    $purchaseParams['actual_num'] = 0;
                }
                $purchaseParams['total_amount'] = $purchase['actual_amount'];
                self::insertWarehouseIn($warehouseInId,$purchaseParams);
                self::insertWarehouseInDetail($warehouseInId,$purchase['purchaseDetail']);
                //如果是订单同时生成出库单
                if(!empty($purchase['order_id'])){
                    //直接新增入库数量
                    foreach ($purchase['purchaseDetail'] as $newGoods){
                        self::changeWarehouseGooodsNum($newGoods['goods_id'],$newGoods['arrival_num'],0,$purchase['warehouse_id']);
                        self::changeGooodsNum($newGoods['goods_id'],$newGoods['arrival_num']);
                    }
                    $warehouseOutParam['warehouse_out'] = [
                        'order_id'=>$purchase['order_id'],
                        'supplier_id'=>$purchase['supplier_id'],
                        'gym_id'=>$purchase['gym_id'],
                        'warehouse_id'=>$purchase['warehouse_id'],
                    ];
                    foreach ($purchase['purchaseDetail'] as $value){
                        $warehouseOutParam['warehouse_out_goods'][] = [
                            'goods_id'=>$value['goods_id'],
                            'except_out_num'=>$value['arrival_num'],
                            'warehouse_id'=>$value['warehouse_id'],
                        ];
                    }
                    self::createWarehouseOut($warehouseOutParam);
                    PurchaseService::changeOrderStatus($purchase['order_id']);
                }
            }
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertWarehouseIn($warehouseInId,$params){
        $warehouseInModel = new WarehouseIn();
        $warehouseInModel->warehouse_in_id = $warehouseInId;
        $warehouseInModel->purchase_id = $params['purchase_id'];
        $warehouseInModel->supplier_id = $params['supplier_id'];
        $warehouseInModel->warehouse_id = $params['warehouse_id'];
        if(!empty($params['order_id'])){
            $warehouseInModel->warehouse_status = WarehouseIn::ALREADT_WAREHOUSE_IN;
            $warehouseInModel->in_time = time();
        }
        $warehouseInModel->except_num = $params['except_num'];
        $warehouseInModel->actual_num = $params['actual_num'];
        $warehouseInModel->total_amount = $params['total_amount'] * 100;
        $warehouseInModel->create_time = time();
        $warehouseInModel->update_time = time();
        $warehouseInModel->save();
    }

    public static function insertWarehouseInDetail($warehouseInId,$params){
        foreach ($params as $warehouseIns) {
            $info[] = array(
                'warehouse_in_id' => $warehouseInId,
                'goods_id' => $warehouseIns['goods_id'],
                'warehouse_id' => $warehouseIns['warehouse_id'],
                'total_amount' => $warehouseIns['actual_amount'] * 100,
                'except_num' => $warehouseIns['arrival_num'],
                'actual_num' => !empty($warehouseIns['order_id'])?$warehouseIns['arrival_num']:0,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseInDetail::getDb()
            ->createCommand()
            ->batchInsert(WarehouseInDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function getWarehouseInList($searchParam,$page,$pageNum){
        $warehouseInListQuery = self::getWarehouseInQuery($searchParam);
        $totalCount = $warehouseInListQuery->count('1');
        if(!empty($page)){
            $offset = ($page - 1) * $pageNum;
            $warehouseInListQuery = $warehouseInListQuery->offset($offset)->limit($pageNum);
        }
        $warehouseInListQuery = $warehouseInListQuery->orderBy([
            't_warehouse_in.create_time' => SORT_DESC
        ]);
        $warehouseInList = $warehouseInListQuery->asArray()->all();
        if(!empty($warehouseInList)){
            foreach ($warehouseInList as $key=>$value){
                $warehouseInList[$key]['purchase_order_type'] = empty($value['order_id'])?WarehouseIn::REAL_LIBRARY:WarehouseIn::VIRTUAL_LIBRARY;
                $warehouseInList[$key]['in_time'] = !empty($value['in_time'])?date("Y-m-d",$value['in_time']):'-';
                $warehouseInList[$key]['total_amount'] = !empty($value['total_amount'])?$value['total_amount'] / 100:$value['total_amount'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_in_list'  => $warehouseInList,
        ];
    }

    public static function getWarehouseInQuery($searchParam){
        $fields = [
            't_warehouse_in.warehouse_in_id',
            't_warehouse_in.purchase_id',
            't_warehouse_in.warehouse_id',
            't_warehouse_in.supplier_id',
            't_warehouse_in.in_time',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_warehouse_in.total_amount',
            't_warehouse_in.except_num',
            't_warehouse_in.actual_num',
            't_warehouse_in.warehouse_status',
            't_purchase.purchase_time',
            't_warehouse_in.total_amount',
            't_purchase.order_id'
        ];
        $warehouseInListQuery = WarehouseIn::find()
            ->select($fields)
            ->leftJoin('t_supplier', 't_supplier.id = t_warehouse_in.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_in.warehouse_id')
            ->leftJoin('t_purchase', 't_purchase.purchase_id = t_warehouse_in.purchase_id')
            ->where([
                't_warehouse.warehouse_status'=>AVAILABLE,
                't_supplier.supplier_status'=>AVAILABLE
            ])
        ;

        if(!empty($searchParam['warehouse_in_start_time'])){
            $warehouseInListQuery->andWhere(['>=', 't_warehouse_in.in_time', $searchParam['warehouse_in_start_time']]);
        }

        if(!empty($searchParam['warehouse_in_end_time'])){
            $warehouseInListQuery->andWhere(['<=', 't_warehouse_in.in_time', $searchParam['warehouse_in_end_time']]);
        }

        if(!empty($searchParam['warehouse_status'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_status'=>$searchParam['warehouse_status']]);
        }

        if(!empty($searchParam['warehouse_in_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_in_id'=>$searchParam['warehouse_in_id']]);
        }

        if(!empty($searchParam['purchase_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.purchase_id'=>$searchParam['purchase_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['supplier_name'])){
            $warehouseInListQuery->andWhere([
                'like',
                't_supplier.supplier_name',
                $searchParam['supplier_name'],
            ]);
        }

        return $warehouseInListQuery;
    }

    public static function getWarehouseInInfo($warehouseInId){
        $warehouseInInfo = WarehouseIn::find()
            ->with(["warehouseInDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('supplier')
            ->with('warehouse')
            ->with('purchase')
            ->with('remark')
            ->where(['warehouse_in_id'=>$warehouseInId])
            ->asArray()
            ->one();
        if(!empty($warehouseInInfo)){
            $warehouseInInfo['in_time'] = !empty($warehouseInInfo['in_time'])?date("Y-m-d",$warehouseInInfo['in_time']):'';
            $warehouseInInfo['purchase']['purchase_time'] = !empty($warehouseInInfo['purchase']['purchase_time'])?date("Y-m-d",$warehouseInInfo['purchase']['purchase_time']):'';
            foreach ($warehouseInInfo['warehouseInDetail'] as $key=>$value){
                $warehouseInInfo['warehouseInDetail'][$key]['fee_num'] = $value['except_num'] - $value['actual_num'];
            }
        }
        if(!empty($warehouseInInfo['remark'])){
            foreach ($warehouseInInfo['remark'] as $key=>$value){
                $warehouseInInfo['remark'][$key]['create_time'] =  date("Y-m-d H:i:s",$value['create_time']);
            }
        }else{
            $warehouseInInfo['remark'] = [];
        }
        $warehouseInInfo['purchase_order_type'] = empty($warehouseInInfo['purchase']['order_id']) ? WarehouseIn::REAL_LIBRARY:WarehouseIn::VIRTUAL_LIBRARY;
        return $warehouseInInfo;
    }

    public static function confirmWarehouseIn($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $totalNum = 0;
            $totalAmount = 0;
            $warehouseInInfo = WarehouseIn::getWarehouseInInfo($params['warehouse_in_id']);
            //首先修改关联的采购单状态
            Purchase::updateAll([
                'purchase_status'=>Purchase::WAREHOUSE_COMPLETE,
                'update_time' =>time()
            ], [
                'purchase_id' => $warehouseInInfo->purchase_id
            ]);
            foreach ($params['goods'] as $good){
                $goodsId[] = $good['goods_id'];
                $totalNum += $good['actual_num'];
                if($good['except_num'] == $good['actual_num']){
                    WarehouseInDetail::updateAll([
                        'is_all_warehouse_in'=>WarehouseIn::IS_ALL_WAREHOUSE_IN,
                    ], [
                        'goods_id' => $good['goods_id'],
                        'warehouse_in_id'=>$params['warehouse_in_id']
                    ]);
                }
            }
            $goodsInfo = Goods::getGoodsInfo($goodsId);
            foreach ($params['goods'] as $k=>$g){
                $params['goods'][$k]['total_amount'] = $goodsInfo[$g['goods_id']]['purchase_amount'] * $g['actual_num'];
                $totalAmount += $goodsInfo[$g['goods_id']]['purchase_amount'] * $g['actual_num'];
            }
            if(!empty($warehouseInInfo)){
                $warehouseInInfo->total_amount = $totalAmount;
                $warehouseInInfo->warehouse_status = WarehouseIn::ALREADT_WAREHOUSE_IN;
                $warehouseInInfo->warehouse_use_status = WarehouseIn::UPDATE_WAREHOUSE_IN;
                $warehouseInInfo->actual_num = $totalNum;
                $warehouseInInfo->in_time = $params['in_time'];
            }
            $warehouseInInfo->save();
            self::changeWarehouseIn($params['warehouse_in_id'],$warehouseInInfo->warehouse_id,$params['goods']);
            if(!empty($params['remark'])){
                Remark::saveRemark($params['warehouse_in_id'],Remark::WAREHOUSE_IN,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function changeWarehouseIn($warehouseInId,$warehouseId,$goodsArr){
        $oldGoodsArr = WarehouseInDetail::getWarehouseInDetailGoods($warehouseInId);
        $newGoodsArr = [];
        foreach ($oldGoodsArr as $oldGoods){
            foreach ($goodsArr as $goods){
                if($oldGoods['goods_id'] == $goods['goods_id']){
                    $newGoodsArr[] = [
                        'actual_num'=>$goods['actual_num'],
                        'total_amount'=>$goods['total_amount'],
                        'goods_id'=>$goods['goods_id'],
                        'change_warehouse_num'=>$goods['actual_num'] - $oldGoods['actual_num']
                    ];
                    break;
                }
            }
        }
        foreach ($newGoodsArr as $newGoods){
            WarehouseInDetail::updateAll([
                'actual_num'=>$newGoods['actual_num'],
                'total_amount'=>$newGoods['total_amount'],
                'update_time' =>time()
            ], [
                'goods_id' => $newGoods['goods_id'],
                'warehouse_in_id'=>$warehouseInId
            ]);
            self::changeWarehouseGooodsNum($newGoods['goods_id'],$newGoods['change_warehouse_num'],0,$warehouseId);
            self::changeGooodsNum($newGoods['goods_id'],$newGoods['change_warehouse_num']);
        }
        return true;
    }

    public static function changeWarehouseGooodsNum($goodsId,$cInventory,$cDefectiveInventory,$warehouseId){
         $directory = "warehouse/".date('Ymd');
         $fileName = 'app_'.$warehouseId.$goodsId;
         $lock = new Lock($fileName,$directory);
         $lock->lock();
         $warehouseGoodsInfo = WarehouseGoods::getWarehouseGoodsInfo($warehouseId,$goodsId);
         if(!empty($warehouseGoodsInfo)){
             $inventory = $warehouseGoodsInfo->inventory;
             $newNum = $inventory + $cInventory;
             $defectiveInventory = $warehouseGoodsInfo->defective_inventory;
             $newDefectiveNum = $defectiveInventory + $cDefectiveInventory;
             //这边去做库存判断
             if($newNum < 0 ){
                 $lock->unlock();
                 throw new \app\exceptions\WarehouseException(21006);
             }
             if($newDefectiveNum < 0 ){
                 $lock->unlock();
                 throw new \app\exceptions\WarehouseException(21007);
             }
             $warehouseGoodsInfo->inventory = $newNum;
             $warehouseGoodsInfo->defective_inventory = $newDefectiveNum;
             $warehouseGoodsInfo->save();
             $lock->unlock();
         }else{
             $saveWarehouseGooods = [];
             $saveWarehouseGooods['warehouse_id'] = $warehouseId;
             $saveWarehouseGooods['goods_id'] = $goodsId;
             $saveWarehouseGooods['inventory'] = $cInventory;
             $saveWarehouseGooods['defective_inventory'] = $cDefectiveInventory;
             self::insertWarehouseGooods($saveWarehouseGooods);
             $lock->unlock();
         }
            return true;
    }

    public static function insertWarehouseGooods($params){
        $warehouseGoodsModel = new WarehouseGoods();
        $warehouseGoodsModel->warehouse_id = $params['warehouse_id'];
        $warehouseGoodsModel->goods_id = $params['goods_id'];
        $warehouseGoodsModel->inventory = $params['inventory'];
        $warehouseGoodsModel->save();
        return true;
    }

    public static function changeGooodsNum($goodsId,$actualNum){
        $directory = "warehouse_goods/".date('Ymd');
        $fileName = 'warehouse_'.$goodsId;
        $lock = new Lock($fileName,$directory);
        $lock->lock();
        $goodsInfo = Goods::getOne($goodsId);
        if(!empty($goodsInfo)){
            $totalNum = ($goodsInfo->inventory) + $actualNum;

            if($totalNum < $goodsInfo->warn && $goodsInfo->is_warn == Goods::N_EARLY_WARNING){
                $goodsInfo->is_warn = Goods::Y_EARLY_WARNING;
            }
            if($totalNum > $goodsInfo->warn && $goodsInfo->is_warn == Goods::Y_EARLY_WARNING){
                $goodsInfo->is_warn = Goods::N_EARLY_WARNING;
            }
            $goodsInfo->inventory = $totalNum;
            $goodsInfo->save();
        }
        $lock->unlock();
        return true;
    }

    public static function adjustWarehouseIn($warehouseInId){
        $warehouseInInfo = WarehouseIn::getWarehouseInInfo($warehouseInId);
        if(!empty($warehouseInInfo)){
            $warehouseInInfo->warehouse_use_status = WarehouseIn::CONFIRM_WAREHOUSE_IN;
        }
        $warehouseInInfo->operator_id = \Yii::$app->user->getIdentity()->id;
        $warehouseInInfo->operator_name = \Yii::$app->user->getIdentity()->name;
        $warehouseInInfo->save();
        return true;
    }

    public static function getWarehouseInDetailList($searchParam,$page,$pageNum){
        $warehouseInDetailListQuery = self::getWarehouseInDetailQuery($searchParam);
        $totalCount = $warehouseInDetailListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $warehouseInDetailListQuery = $warehouseInDetailListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_warehouse_in.create_time' => SORT_DESC
        ]);
        $warehouseInDetailList = $warehouseInDetailListQuery->asArray()->all();
        if(!empty($warehouseInDetailList)){
            foreach ($warehouseInDetailList as $key=>$value){
                $warehouseInDetailList[$key]['in_time'] = !empty($value['in_time'])?date("Y-m-d",$value['in_time']):'-';
                $warehouseInDetailList[$key]['total_amount'] = !empty($value['total_amount'])?$value['total_amount'] / 100:$value['total_amount'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_in_detail_list'  => $warehouseInDetailList,
        ];
    }

    public static function getWarehouseInDetailQuery($searchParam){
        $fields = [
            't_warehouse_in.warehouse_in_id',
            't_warehouse_in.purchase_id',
            't_warehouse_in.in_time',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_warehouse_in_detail.total_amount',
            't_warehouse_in_detail.except_num',
            't_warehouse_in_detail.actual_num',
            't_warehouse_in.warehouse_status',
            't_purchase.purchase_time',
            't_goods.goods_name'
        ];
        $warehouseInListQuery = WarehouseInDetail::find()
            ->select($fields)
            ->leftJoin('t_goods','t_goods.goods_id = t_warehouse_in_detail.goods_id')
            ->leftJoin('t_warehouse_in', 't_warehouse_in.warehouse_in_id = t_warehouse_in_detail.warehouse_in_id')
            ->leftJoin('t_supplier', 't_supplier.id = t_warehouse_in.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_in.warehouse_id')
            ->leftJoin('t_purchase', 't_purchase.purchase_id = t_warehouse_in.purchase_id')
            ->where([
                't_warehouse.warehouse_status'=>AVAILABLE,
                't_supplier.supplier_status'=>AVAILABLE
            ]);
        if(!empty($searchParam['warehouse_in_start_time'])){
            $warehouseInListQuery->andWhere(['>=', 't_warehouse_in.in_time', $searchParam['warehouse_in_start_time']]);
        }

        if(!empty($searchParam['warehouse_in_end_time'])){
            $warehouseInListQuery->andWhere(['<=', 't_warehouse_in.in_time', $searchParam['warehouse_in_end_time']]);
        }

        if(!empty($searchParam['warehouse_status'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_status'=>$searchParam['warehouse_status']]);
        }

        if(!empty($searchParam['warehouse_in_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_in_id'=>$searchParam['warehouse_in_id']]);
        }

        if(!empty($searchParam['purchase_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.purchase_id'=>$searchParam['purchase_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $warehouseInListQuery->andWhere(['t_warehouse_in.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['supplier_name'])){
            $warehouseInListQuery->andWhere([
                'like',
                't_purchase.supplier_name',
                $searchParam['supplier_name'],
            ]);
        }
        if(!empty($searchParam['goods_name'])){
            $warehouseInListQuery->andWhere([
                'like',
                't_goods.goods_name',
                $searchParam['goods_name'],
            ]);
        }
        return $warehouseInListQuery;
    }

    //出库单
    public static function createWarehouseOut($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $warehouseOutId = generateIntId('CK');
            self::insertWarehouseOut($warehouseOutId,$params['warehouse_out']);
            self::insertWarehouseOutDetail($warehouseOutId,$params['warehouse_out_goods']);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertWarehouseOut($warehouseOutId,$params){
        $warehouseOutModel = new WarehouseOut();
        $warehouseOutModel->warehouse_out_id = $warehouseOutId;
        $warehouseOutModel->order_id = $params['order_id'];
        $warehouseOutModel->warehouse_id = ArrayHelper::getValue($params,'warehouse_id',self::WAREHOUSE_ID);
        $warehouseOutModel->supplier_id = $params['supplier_id'];
        $warehouseOutModel->gym_id = $params['gym_id'];
        $warehouseOutModel->create_time = time();
        $warehouseOutModel->update_time = time();
        $warehouseOutModel->save();
    }

    public static function insertWarehouseOutDetail($warehouseOutId,$params){
        foreach ($params as $warehouseOuts) {
            $info[] = array(
                'warehouse_out_id' => $warehouseOutId,
                'goods_id' => $warehouseOuts['goods_id'],
                'except_out_num' => $warehouseOuts['except_out_num'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseOutDetail::getDb()
            ->createCommand()
            ->batchInsert(WarehouseOutDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function getWarehouseOutList($searchParam,$page,$pageNum){
        $warehouseOutListQuery = self::getWarehouseOutQuery($searchParam);
        $totalCount = $warehouseOutListQuery->count('1');
        if(!empty($page)){
            $offset = ($page - 1) * $pageNum;
            $warehouseOutListQuery = $warehouseOutListQuery->offset($offset)->limit($pageNum);
        }
        $warehouseOutListQuery = $warehouseOutListQuery->orderBy([
            't_warehouse_out.create_time' => SORT_DESC
        ]);
        $warehouseOutList = $warehouseOutListQuery->asArray()->all();
        if(!empty($warehouseOutList)){
            foreach ($warehouseOutList as $key=>$value){
                $warehouseOutList[$key]['out_time'] = !empty($value['out_time'])?date("Y-m-d",$value['out_time']):'-';
                $warehouseOutList[$key]['arrival_time'] = !empty($value['arrival_time'])?date("Y-m-d",$value['arrival_time']):'-';
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_out_list'  => $warehouseOutList,
        ];
    }

    public static function getWarehouseOutQuery($searchParam){
        $fields = [
            't_warehouse_out.warehouse_out_id',
            't_warehouse_out.order_id',
            't_warehouse_out.out_status',
            't_warehouse_out.out_time',
            't_warehouse_out.arrival_time',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_warehouse_out.out_use_status',
            't_order.create_time',
            't_open_project.gym_name',
            't_open_project.address',
        ];
        $warehouseOutListQuery = WarehouseOut::find()
            ->select($fields)
            ->leftJoin('t_supplier', 't_supplier.id = t_warehouse_out.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_out.warehouse_id')
            ->leftJoin('t_order', 't_order.order_id = t_warehouse_out.order_id')
            ->leftJoin('t_open_project', 't_warehouse_out.gym_id = t_open_project.id')
            ->where([
                't_supplier.supplier_status'=>AVAILABLE
            ])
        ;
        if(!empty($searchParam['warehouse_out_start_time'])){
            $warehouseOutListQuery->andWhere(['>=', 't_warehouse_out.out_time', $searchParam['warehouse_out_start_time']]);
        }

        if(!empty($searchParam['warehouse_out_end_time'])){
            $warehouseOutListQuery->andWhere(['<=', 't_warehouse_out.out_time', $searchParam['warehouse_out_end_time']]);
        }

        if(!empty($searchParam['out_status'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.out_status'=>$searchParam['out_status']]);
        }

        if(!empty($searchParam['warehouse_out_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.warehouse_out_id'=>$searchParam['warehouse_out_id']]);
        }

        if(!empty($searchParam['order_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.order_id'=>$searchParam['order_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['gym_name'])){
            $warehouseOutListQuery->andWhere([
                'like',
                't_open_project.gym_name',
                $searchParam['gym_name'],
            ]);
        }

        return $warehouseOutListQuery;
    }

    public static function getWarehouseOutInfo($warehouseOutId,$warehouseId){
        $warehouseOutInfo = WarehouseOut::find()
            ->with(["warehouseOutDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('supplier')
            ->with('gym')
            ->with('order')
            ->with('warehouse')
            ->with('remark')
            ->where(['warehouse_out_id'=>$warehouseOutId])
            ->asArray()
            ->one();
        if(!empty($warehouseOutInfo)){
            $warehouseOutInfo['out_time'] = !empty($warehouseOutInfo['out_time'])?date("Y-m-d",$warehouseOutInfo['out_time']):'';
            $warehouseOutInfo['arrival_time'] = !empty($warehouseOutInfo['arrival_time'])?date("Y-m-d",$warehouseOutInfo['arrival_time']):'';
            $warehouseOutInfo['order']['create_time'] = !empty($warehouseOutInfo['order']['create_time'])?strtotime(date("Y-m-d",$warehouseOutInfo['order']['create_time'])):'';
            $warehouseOutInfo['order']['create_time_format'] = !empty($warehouseOutInfo['order']['create_time'])?date("Y-m-d",$warehouseOutInfo['order']['create_time']):'';
            foreach ($warehouseOutInfo['warehouseOutDetail'] as $key=>$value){
                $warehouseOutInfo['warehouseOutDetail'][$key]['fee_num'] = $value['except_out_num'] - $value['actual_out_num'];
                $goodsIds[] = $value['goods_id'];
            }
            if(empty($warehouseId)){
                $warehouseId = $warehouseOutInfo['warehouse_id'];
                $warehouseOutInfo['choose_warehouse_id'] = $warehouseOutInfo['warehouse_id'];
            }else{
                $warehouseOutInfo['choose_warehouse_id'] = $warehouseId;
            }
            $goodsIdsInfo = WarehouseGoods::getWarehouseGoodsInfos($warehouseId,$goodsIds);
            foreach ($warehouseOutInfo['warehouseOutDetail'] as $key=>$value){
                $warehouseOutInfo['warehouseOutDetail'][$key]['inventory'] = !isset($goodsIdsInfo[$value['goods_id']])?0:$goodsIdsInfo[$value['goods_id']]['inventory'];
            }
        }
        if(!empty($warehouseOutInfo['remark'])){
            foreach ($warehouseOutInfo['remark'] as $key=>$value){
                $warehouseOutInfo['remark'][$key]['create_time'] =  date("Y-m-d H:i:s",$value['create_time']);
            }
        }else{
            $warehouseOutInfo['remark'] = [];
        }
        //取仓库
        if(empty($warehouseOutInfo['warehouse']) || $warehouseOutInfo['warehouse']['warehouse_type'] == Warehouse::REAL_LIBRARY){
            $warehouseOutInfo['warehouse_list'] = Warehouse::find()->where(['warehouse_type'=>Warehouse::REAL_LIBRARY])->asArray()->all();
        }else{
            $warehouseOutInfo['warehouse_list'] = Warehouse::find()->where(['warehouse_type'=>Warehouse::VIRTUAL_LIBRARY])->asArray()->all();
        }
        return $warehouseOutInfo;
    }

    public static function getWarehouseGoodsInventory($params){
        $goodsArr = [];
        $goodsWarehouseNum = WarehouseGoods::getWarehouseGoodsInfos($params['warehouse_id'],$params['goods_id']);
        foreach ($params['goods_id'] as $key=>$value){
            if(isset($goodsWarehouseNum[$value])){
                $goodsArr[$value] =  $goodsWarehouseNum[$value];
            }else{
                $goodsArr[$value]['inventory'] = 0;
            }
        }
        return $goodsArr;
    }

    public static function confirmWarehouseOut($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $warehouseOutInfo = WarehouseOut::getWarehouseOutInfo($params['warehouse_out_id']);
            $totalNum = 0;
            foreach ($params['goods'] as $good){
                $totalNum += $good['actual_out_num'];
                if($good['actual_out_num'] == $good['except_out_num']){
                    WarehouseOutDetail::updateAll([
                        'is_warehouse_out_all'=>WarehouseOut::IS_WAREHOUSE_OUT_ALL,
                    ], [
                        'goods_id' => $good['goods_id'],
                        'warehouse_out_id'=>$params['warehouse_out_id']
                    ]);
                }
            }
            if(!empty($warehouseOutInfo)){
                $warehouseOutInfo->warehouse_id = $params['warehouse_id'];
                $warehouseOutInfo->out_time = $params['out_time'];
                $warehouseOutInfo->arrival_time = $params['arrival_time'];
                $warehouseOutInfo->out_status = WarehouseOut::ALREADT_WAREHOUSE_OUT;
                $warehouseOutInfo->out_use_status = WarehouseOut::UPDATE_WAREHOUSE_OUT;
                $warehouseOutInfo->total_out_num = $totalNum;
                $warehouseOutInfo->operator_id = \Yii::$app->user->getIdentity()->id;
                $warehouseOutInfo->operator_name = \Yii::$app->user->getIdentity()->name;
            }
            $warehouseOutInfo->save();
            self::changeWarehouseOut($params['warehouse_out_id'],$params['warehouse_id'],$params['goods']);
            if(!empty($params['remark'])){
                Remark::saveRemark($params['warehouse_out_id'],Remark::WAREHOUSE_OUT,$params['remark']);
            }
            if(!empty($warehouseOutInfo->order_id)){
                PurchaseService::changeOrderStatus($warehouseOutInfo->order_id);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function changeWarehouseOut($warehouseOutId,$warehouseId,$goodsArr){
        $oldGoodsArr = WarehouseOutDetail::getWarehouseOutDetailGoods($warehouseOutId);
        $newGoodsArr = [];
        foreach ($oldGoodsArr as $oldGoods){
            foreach ($goodsArr as $goods){
                if($oldGoods['goods_id'] == $goods['goods_id']){
                    $newGoodsArr[] = [
                        'actual_out_num'=>$goods['actual_out_num'],
                        'goods_id'=>$goods['goods_id'],
                        'change_warehouse_num'=>$goods['actual_out_num'] - $oldGoods['actual_out_num']
                    ];
                    break;
                }
            }
        }
        foreach ($newGoodsArr as $newGoods){
            WarehouseOutDetail::updateAll([
                'actual_out_num'=>$newGoods['actual_out_num'],
                'update_time' =>time()
            ], [
                'goods_id' => $newGoods['goods_id'],
                'warehouse_out_id'=>$warehouseOutId
            ]);
            $changeWarehouseNum = self::plus_minus_conversion($newGoods['change_warehouse_num']);
            self::changeWarehouseGooodsNum($newGoods['goods_id'],$changeWarehouseNum,0,$warehouseId);
            self::changeGooodsNum($newGoods['goods_id'],$changeWarehouseNum);
        }
        return true;
    }

    public static function plus_minus_conversion($number = 0){
        return $number > 0 ? -1 * $number : abs($number);
    }

    public static function getWarehouseGoods($warehouseId,$goods_id){
        if(empty($warehouseId)){
            $goodsLists =  Warehouse::getGoodsList($goods_id);
        }else{
            $goodsLists =  Warehouse::getGoodsListByWarehouseId($warehouseId,$goods_id);
        }

        return $goodsLists;
    }

    public static function adjustWarehouseOut($warehouseOutId){
        $warehouseOutInfo = WarehouseOut::getWarehouseOutInfo($warehouseOutId);
        if(!empty($warehouseOutInfo)){
            $warehouseOutInfo->out_use_status = WarehouseOut::CONFIRM_WAREHOUSE_OUT;
        }
        $warehouseOutInfo->operator_id = \Yii::$app->user->getIdentity()->id;
        $warehouseOutInfo->operator_name = \Yii::$app->user->getIdentity()->name;
        $warehouseOutInfo->save();
        return true;
    }

    public static function getWarehouseOutDetailList($searchParam,$page,$pageNum){
        $warehouseOutDetailListQuery = self::getWarehouseOutDetailQuery($searchParam);
        $totalCount = $warehouseOutDetailListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $warehouseOutDetailListQuery = $warehouseOutDetailListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_warehouse_out.create_time' => SORT_DESC
        ]);
        $warehouseOutDetailList = $warehouseOutDetailListQuery->asArray()->all();
        if(!empty($warehouseOutDetailList)){
            foreach ($warehouseOutDetailList as $key=>$value){
                $warehouseOutDetailList[$key]['out_time'] = !empty($value['out_time'])?date("Y-m-d",$value['out_time']):'-';
                $warehouseOutDetailList[$key]['arrival_time'] = !empty($value['arrival_time'])?date("Y-m-d",$value['arrival_time']):'-';
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_out_detail_list'  => $warehouseOutDetailList,
        ];
    }

    public static function getWarehouseOutDetailQuery($searchParam){
        $fields = [
            't_warehouse_out.warehouse_out_id',
            't_warehouse_out.order_id',
            't_warehouse_out.out_time',
            't_warehouse_out.arrival_time',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_warehouse_out_detail.except_out_num',
            't_warehouse_out_detail.actual_out_num',
            't_warehouse_out.out_status',
            't_order.create_time',
            't_open_project.gym_name',
            't_goods.goods_name'
        ];
        $warehouseOutListQuery = WarehouseOutDetail::find()
            ->select($fields)
            ->leftJoin('t_goods','t_goods.goods_id = t_warehouse_out_detail.goods_id')
            ->leftJoin('t_warehouse_out', 't_warehouse_out.warehouse_out_id = t_warehouse_out_detail.warehouse_out_id')
            ->leftJoin('t_supplier', 't_supplier.id = t_warehouse_out.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_out.warehouse_id')
            ->leftJoin('t_order', 't_order.order_id = t_warehouse_out.order_id')
            ->leftJoin('t_open_project', 't_open_project.id = t_warehouse_out.gym_id')
            ->where([
                't_supplier.supplier_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['warehouse_out_start_time'])){
            $warehouseOutListQuery->andWhere(['>=', 't_warehouse_out.out_time', $searchParam['warehouse_out_start_time']]);
        }

        if(!empty($searchParam['warehouse_out_end_time'])){
            $warehouseOutListQuery->andWhere(['<=', 't_warehouse_out.out_time', $searchParam['warehouse_out_end_time']]);
        }

        if(!empty($searchParam['out_status'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.out_status'=>$searchParam['out_status']]);
        }

        if(!empty($searchParam['warehouse_out_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.warehouse_out_id'=>$searchParam['warehouse_out_id']]);
        }

        if(!empty($searchParam['order_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.order_id'=>$searchParam['order_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $warehouseOutListQuery->andWhere(['t_warehouse_out.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['gym_name'])){
            $warehouseOutListQuery->andWhere([
                'like',
                't_open_project.gym_name',
                $searchParam['gym_name'],
            ]);
        }
        if(!empty($searchParam['goods_name'])){
            $warehouseOutListQuery->andWhere([
                'like',
                't_goods.goods_name',
                $searchParam['goods_name'],
            ]);
        }
        return $warehouseOutListQuery;
    }


    //oa 出库单 begin
    public static function getWarehouseOut($orderId){
        $warehouseOutStatus = WarehouseOut::INITIAL_STATUS;
        $totalOrderNum = self::getOderGoodsNum($orderId);
        $totalWarehouseOutNum = self::getWarehouseOutNum($orderId);
        $warehouseOutList = self::getWarehouseOutByOrderId($orderId);
        if(!empty($warehouseOutList)){
            $warehouseOutStatus = WarehouseOut::ALREADT_WAREHOUSE_OUT;
        }
        $list['order_id'] = $orderId;
        $list['out_status'] = $warehouseOutStatus;
        $list['order_totle'] = isset($totalOrderNum['goods_num_total']) ? $totalOrderNum['goods_num_total']:0;
        $list['order_out'] = isset($totalWarehouseOutNum['goods_out_num_total']) ? $totalWarehouseOutNum['goods_out_num_total']:0;
        $list['order_wait'] = $list['order_totle'] - $list['order_out'];
        $list['out_order_list'] = !empty($warehouseOutList)?$warehouseOutList:[];
        return $list;
    }

    public static function getOderGoodsNum($orderId){
        $goodsNum = OrderDetail::find()
            ->select(['sum(goods_num) as goods_num_total'])
            ->where(['order_id'=>$orderId])
            ->groupBy(['order_id'])
            ->asArray()
            ->one();
        return $goodsNum;
    }

    public static function getWarehouseOutNum($orderId){
        $goodsNum = WarehouseOutDetail::find()
            ->select(['sum(t_warehouse_out_detail.actual_out_num) as goods_out_num_total'])
            ->leftJoin('t_warehouse_out','t_warehouse_out.warehouse_out_id = t_warehouse_out_detail.warehouse_out_id')
            ->where(['t_warehouse_out.order_id'=>$orderId,'t_warehouse_out.out_status'=>WarehouseOut::ALREADT_WAREHOUSE_OUT])
            ->groupBy(['t_warehouse_out.order_id'])
            ->asArray()
            ->one();
        return $goodsNum;
    }

    public static function getWarehouseOutByOrderId($orderId){
        $list = WarehouseOut::find()
            ->select(['warehouse_out_id','out_time','arrival_time'])
            ->where(['t_warehouse_out.order_id'=>$orderId,'t_warehouse_out.out_status'=>WarehouseOut::ALREADT_WAREHOUSE_OUT])
            ->orderBy([
                't_warehouse_out.arrival_time' => SORT_DESC
            ])
            ->asArray()
            ->all();
        return $list;
    }

    public static function getOrderGoods($orderId){
        $orderGoodsList =  OrderDetail::find()
            ->select([
                't_order_detail.goods_id',
                't_order_detail.goods_num',
                't_goods.goods_id',
                't_goods.goods_name',
                't_goods.model',
            ])
            ->leftJoin('t_goods','t_goods.goods_id = t_order_detail.goods_id')
            ->where(['t_order_detail.order_id'=>$orderId])
            ->asArray()
            ->all();
        return $orderGoodsList;
    }

    public static function getWarehouseOutGoodsByOrderId($orderId){
        $warehouseOutGoodsList = WarehouseOutDetail::find()
            ->select(['t_warehouse_out_detail.goods_id','t_warehouse_out_detail.actual_out_num'])
            ->leftJoin('t_warehouse_out','t_warehouse_out.warehouse_out_id = t_warehouse_out_detail.warehouse_out_id')
            ->where(['t_warehouse_out.order_id'=>$orderId])
            ->asArray()
            ->all();
        return $warehouseOutGoodsList;
    }

    public static function getWarehouseOutGoods($orderId){
        $goodsArr = [];
        $goodsOrderList = self::getOrderGoods($orderId);
        if(!empty($goodsOrderList)){
            foreach ($goodsOrderList as $goods){
                $goodsArr[] = [
                    'goods_id'=>$goods['goods_id'],
                    'goods_name'=>$goods['goods_name'],
                    'model'=>$goods['model'],
                    'goods_num'=>$goods['goods_num'],
                    'actual_out_num'=>0
                ];
            }
            $warehouseOutGoodsList = self::getWarehouseOutGoodsByOrderId($orderId);
            if(!empty($warehouseOutGoodsList)){
                foreach ($warehouseOutGoodsList as $val){
                    foreach($goodsArr as $k=>$item){
                        if($val['goods_id'] == $item['goods_id']){
                            $goodsArr[$k]['actual_out_num'] = $val['actual_out_num'];
                            break;
                        }
                    }
                }
            }
        }
        return $goodsArr;
    }

    //领货单
    public static function getDepartmentList(){
        return Department::find()
            ->select(['id','name'])
            ->where(['parent_id'=>0,'department_status'=>AVAILABLE])
            ->asArray()
            ->all();
    }

    public static function getGetGoodsList($searchParam,$page,$pageNum){
        $getGoodsListQuery = self::getGetGoodsQuery($searchParam);
        $totalCount = $getGoodsListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $getGoodsListQuery = $getGoodsListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_get_goods.create_time' => SORT_DESC
        ]);
        $getGoodsList = $getGoodsListQuery->asArray()->all();
        if(!empty($getGoodsList)){
            foreach ($getGoodsList as $key=>$val){
                $getGoodsList[$key]['get_time'] = date('Y-m-d', $val['get_time']);
            }
        }

        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'get_goods_list'  => $getGoodsList,
        ];
    }

    public static function getGetGoodsQuery($searchParam){
        $fields = [
            't_get_goods.get_goods_id',
            't_get_goods.get_user_name',
            't_department.name',
            't_get_goods.get_time',
            't_warehouse.warehouse_name',
            't_get_goods.operator_name',
        ];
        $getGoodsListQuery = GetGoods::find()
            ->select($fields)
            ->leftJoin('t_department','t_department.id = t_get_goods.department_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_get_goods.warehouse_id')
            ->where([
                't_department.department_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['get_goods_start_time'])){
            $getGoodsListQuery->andWhere(['>=', 't_get_goods.get_time', $searchParam['get_goods_start_time']]);
        }

        if(!empty($searchParam['get_goods_end_time'])){
            $getGoodsListQuery->andWhere(['<=', 't_get_goods.get_time', $searchParam['get_goods_end_time']]);
        }

        if(!empty($searchParam['get_goods_id'])){
            $getGoodsListQuery->andWhere(['t_get_goods.get_goods_id'=>$searchParam['get_goods_id']]);
        }

        if(!empty($searchParam['department_id'])){
            $getGoodsListQuery->andWhere(['t_get_goods.department_id'=>$searchParam['department_id']]);
        }

        return $getGoodsListQuery;
    }

    public static function addGetGoods($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $getGoodsId = generateIntId('LH');
            self::insertGetGoods($getGoodsId,$params['get_goods']);
            self::insertGetGoodsDetail($getGoodsId,$params['get_goods_detail']);
            self::changeGetGoods($params['get_goods']['warehouse_id'],$params['get_goods_detail']);
            if(!empty($params['remark'])){
                Remark::saveRemark($getGoodsId,Remark::GET_GOODS,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertGetGoods($getGoodsId,$params){
        $getGoodsModel = new GetGoods();
        $getGoodsModel->get_goods_id = $getGoodsId;
        $getGoodsModel->get_user_name = $params['get_user_name'];
        $getGoodsModel->department_id = $params['department_id'];
        $getGoodsModel->warehouse_id = $params['warehouse_id'];
        $getGoodsModel->get_status = GetGoods::ALREADT_GET_GOODS;
        $getGoodsModel->get_time = $params['get_time'];
        $getGoodsModel->operator_id = \Yii::$app->user->getIdentity()->id;
        $getGoodsModel->operator_name = \Yii::$app->user->getIdentity()->name;
        $getGoodsModel->create_time = time();
        $getGoodsModel->update_time = time();
        $getGoodsModel->save();
    }

    public static function insertGetGoodsDetail($getGoodsId,$params){
        foreach ($params as $getGoods) {
            $info[] = array(
                'get_goods_id' => $getGoodsId,
                'goods_id' => $getGoods['goods_id'],
                'request_goods_num' => $getGoods['request_goods_num'],
                'inventory' => $getGoods['inventory'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        GetGoodsDetail::getDb()
            ->createCommand()
            ->batchInsert(GetGoodsDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function changeGetGoods($warehouseId,$goodsArr){
        foreach ($goodsArr as $goods){
            $actual_num = self::plus_minus_conversion($goods['request_goods_num']);
            self::changeWarehouseGooodsNum($goods['goods_id'],$actual_num,0,$warehouseId);
            self::changeGooodsNum($goods['goods_id'],$actual_num);
        }
        return true;
    }

    public static function getGetGoodsInfo($getGoodsId){
        $getGoodsInfo = GetGoods::find()
            ->with(["getGoodsDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('warehouse')
            ->with('remark')
            ->with('department')
            ->where(['get_goods_id'=>$getGoodsId])
            ->asArray()
            ->one();
        $getGoodsInfo['get_time'] = !empty($getGoodsInfo['get_time'])?date('Y-m-d',$getGoodsInfo['get_time']):$getGoodsInfo['get_time'];
        if(!empty($getGoodsInfo['remark'])){
            $getGoodsInfo['remark']['create_time'] = !empty($getGoodsInfo['remark']['create_time'])?date('Y-m-d',$getGoodsInfo['remark']['create_time']):$getGoodsInfo['remark']['create_time'];
        }
        return $getGoodsInfo;
    }

    //退货单
    public static function getWarehouseInInfos($warehouseInId){
        $warehouseReturnInfo = self::getWarehouseReturnInfo($warehouseInId);
        $returnArr = [];
        if($warehouseReturnInfo['warehouse_status'] == WarehouseIn::INITIAL_STATUS){
            throw new \app\exceptions\WarehouseException(21008);
        }
        if($warehouseReturnInfo['is_balance'] == WarehouseIn::CLOSE_BALANCE){
            throw new \app\exceptions\WarehouseException(21009);
        }
        if(!empty($warehouseReturnInfo)){
            $warehouseReturnInfo['in_time'] = !empty($warehouseReturnInfo['in_time'])?date('Y-m-d',$warehouseReturnInfo['in_time']):$warehouseReturnInfo['in_time'];
            if(!empty($warehouseReturnInfo['return'])){
                foreach ($warehouseReturnInfo['return']['returnGoodsDetail'] as $value){
                    $returnArr[$value['goods_id']]['goods_id'] = $value['goods_id'];
                    $returnArr[$value['goods_id']]['warehouse_in_num'] = $value['warehouse_in_num'] - $value['return_inventory_num'] - $value['return_defective_numCopy'];
                }
            }
            $goodsIds = [];
            foreach ($warehouseReturnInfo['warehouseInDetail'] as $val){
                $goodsIds[] = $val['goods_id'];
            }
            $goodsIdsInfo = WarehouseGoods::getWarehouseGoodsInfos($warehouseReturnInfo['warehouse_id'],$goodsIds);
            foreach ($warehouseReturnInfo['warehouseInDetail'] as $key=>$value){
                $warehouseReturnInfo['warehouseInDetail'][$key]['inventory'] = $goodsIdsInfo[$value['goods_id']]['inventory'];
                $warehouseReturnInfo['warehouseInDetail'][$key]['defective_inventory'] = $goodsIdsInfo[$value['goods_id']]['defective_inventory'];
                $warehouseReturnInfo['warehouseInDetail'][$key]['actual_num'] = !empty($returnArr)?$returnArr[$value['goods_id']]['warehouse_in_num']:$value['actual_num'];
            }
        }
        return $warehouseReturnInfo;
    }

    public static function getWarehouseReturnInfo($warehouseInId){
        $goodsInfo = WarehouseIn::find()
            ->with(["warehouseInDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with(["return" => function(\yii\db\ActiveQuery $query){
                $query->with("returnGoodsDetail");
            }])
            ->with('supplier')
            ->with('warehouse')
            ->where(['warehouse_in_id'=>$warehouseInId])
            ->asArray()
            ->one();
        return $goodsInfo;
    }

    public static function addReturnGoods($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $returnGoodsId = generateIntId('TH');
            $totalReturnNum = 0;
            $totalAmount = 0;
            foreach ($params['return_goods_detail'] as $key=>$goods){
                $goodsId[] = $goods['goods_id'];
                $totalReturnNum += $goods['total_return_num'];
            }
            $goodsInfo = Goods::getGoodsInfo($goodsId);
            foreach ($params['return_goods_detail'] as $k=>$g){
                $params['return_goods_detail'][$k]['total_amount'] = $goodsInfo[$g['goods_id']]['purchase_amount'] * $g['actual_num'];
                $totalAmount += $goodsInfo[$g['goods_id']]['purchase_amount'] * $g['actual_num'];
            }
            $params['return_goods']['return_total_num'] = $totalReturnNum;
            $params['return_goods']['return_amount'] = $totalAmount;
            self::insertReturnGoods($returnGoodsId,$params['return_goods']);
            self::insertReturnGoodsDetail($returnGoodsId,$params['return_goods_detail']);
//            self::changeWarehouseInNum($params['return_goods']['warehouse_in_id'],$params['return_goods_detail']);
            self::changeReturnGoods($params['return_goods']['warehouse_id'],$params['return_goods_detail']);
            if(!empty($params['remark'])){
                Remark::saveRemark($returnGoodsId,Remark::RETURN_GOODS,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertReturnGoods($returnGoodsId,$params){
        $returnGoodsModel = new ReturnGoods();
        $returnGoodsModel->return_id = $returnGoodsId;
        $returnGoodsModel->warehouse_in_id = $params['warehouse_in_id'];
        $returnGoodsModel->supplier_id = $params['supplier_id'];
        $returnGoodsModel->warehouse_id = $params['warehouse_id'];
        $returnGoodsModel->return_status = ReturnGoods::ALREADT_RETURN_GOODS;
        $returnGoodsModel->return_total_num = $params['return_total_num'];
        $returnGoodsModel->return_amount = $params['return_amount'];
        $returnGoodsModel->return_time = $params['return_time'];
        $returnGoodsModel->operator_id = \Yii::$app->user->getIdentity()->id;
        $returnGoodsModel->operator_name = \Yii::$app->user->getIdentity()->name;
        $returnGoodsModel->create_time = time();
        $returnGoodsModel->update_time = time();
        $returnGoodsModel->save();
    }

    public static function insertReturnGoodsDetail($returnGoodsId,$params){
        foreach ($params as $returnGoods) {
            $info[] = array(
                'return_id' => $returnGoodsId,
                'goods_id' => $returnGoods['goods_id'],
                'return_inventory_num' => $returnGoods['return_inventory_num'],
                'return_defective_numCopy' => $returnGoods['return_defective_numCopy'],
                'inventory'=>$returnGoods['inventory'],
                'defective_inventory'=>$returnGoods['defective_inventory'],
                'warehouse_in_num'=>$returnGoods['actual_num'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        ReturnGoodsDetail::getDb()
            ->createCommand()
            ->batchInsert(ReturnGoodsDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function changeReturnGoods($warehouseId,$goodsArr){
        foreach ($goodsArr as $goods){
            if(!empty($goods['return_inventory_num']) || !empty($goods['return_defective_numCopy'])){
                $returnInventoryNum = self::plus_minus_conversion($goods['return_inventory_num']);
                $returnDefectiveNumCopy = self::plus_minus_conversion($goods['return_defective_numCopy']);
                self::changeWarehouseGooodsNum($goods['goods_id'],$returnInventoryNum,$returnDefectiveNumCopy,$warehouseId);
                self::changeGooodsNum($goods['goods_id'],$goods['return_inventory_num']);
            }
        }
        return true;
    }

    public static function getReturnGoodsList($searchParam,$page,$pageNum){
        $returnGoodsListQuery = self::getReturnGoodsQuery($searchParam);
        $totalCount = $returnGoodsListQuery->count('1');
        if(!empty($page)){
            $offset = ($page - 1) * $pageNum;
            $returnGoodsListQuery = $returnGoodsListQuery->offset($offset)->limit($pageNum);
        }
        $returnGoodsListQuery = $returnGoodsListQuery->orderBy([
            't_return.create_time' => SORT_DESC
        ]);
        $returnGoodsList = $returnGoodsListQuery->asArray()->all();
        if(!empty($returnGoodsList)){
            foreach ($returnGoodsList as $key=>$value){
                $returnGoodsList[$key]['return_time'] = !empty(date("Y-m-d",$value['return_time']))?date("Y-m-d",$value['return_time']):$value['return_time'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'return_goods_list'  => $returnGoodsList,
        ];
    }

    public static function getReturnGoodsQuery($searchParam){
        $fields = [
            't_return.warehouse_in_id',
            't_return.supplier_id',
            't_return.return_id',
            't_return.warehouse_id',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_return.return_status',
            't_return.return_total_num',
            't_return.return_time',
            't_return.return_amount',
            't_return.operator_name'
        ];
        $returnGoodsListQuery = ReturnGoods::find()
            ->select($fields)
            ->leftJoin('t_supplier','t_supplier.id = t_return.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_return.warehouse_id')
            ->where([
                't_supplier.supplier_status'=>AVAILABLE,
                't_warehouse.warehouse_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['return_goods_start_time'])){
            $returnGoodsListQuery->andWhere(['>=', 't_return.return_time', $searchParam['return_goods_start_time']]);
        }

        if(!empty($searchParam['return_goods_end_time'])){
            $returnGoodsListQuery->andWhere(['<=', 't_return.return_time', $searchParam['return_goods_end_time']]);
        }

        if(!empty($searchParam['return_id'])){
            $returnGoodsListQuery->andWhere(['t_return.return_id'=>$searchParam['return_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $returnGoodsListQuery->andWhere(['t_return.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['return_status'])){
            $returnGoodsListQuery->andWhere(['t_return.return_status'=>$searchParam['return_status']]);
        }

        return $returnGoodsListQuery;
    }

    public static function getReturnGoodsInfo($returnId){
        $returnInfo = ReturnGoods::find()
            ->with(["returnGoodsDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('supplier')
            ->with('warehouse')
            ->with('remark')
            ->where(['return_id'=>$returnId])
            ->asArray()
            ->one();
        $returnInfo['return_time'] = !empty($returnInfo['return_time'])?date('Y-m-d',$returnInfo['return_time']):$returnInfo['return_time'];
        if(!empty($returnInfo['remark'])){
            $returnInfo['remark']['create_time'] = !empty($returnInfo['remark']['create_time'])?date('Y-m-d',$returnInfo['remark']['create_time']):$returnInfo['remark']['create_time'];
        }
        return $returnInfo;
    }

    public static function getReturnGoodsDetailList($searchParam,$page,$pageNum){
        $returnGoodsDetailListQuery = self::getReturnGoodsDetailQuery($searchParam);
        $offset = ($page - 1) * $pageNum;
        $returnGoodsDetailListQuery = $returnGoodsDetailListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_return.create_time' => SORT_DESC
        ]);
        $totalCount = $returnGoodsDetailListQuery->count('1');
        $returnGoodsDetailList = $returnGoodsDetailListQuery->asArray()->all();
        if(!empty($returnGoodsDetailList)){
            foreach ($returnGoodsDetailList as $key=>$value){
                $returnGoodsDetailList[$key]['return_time'] = !empty(date("Y-m-d",$value['return_time']))?date("Y-m-d",$value['return_time']):$value['return_time'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'return_goods_detail_list'  => $returnGoodsDetailList,
        ];
    }

    public static function getReturnGoodsDetailQuery($searchParam){
        $fields = [
            't_return.warehouse_in_id',
            't_return.return_id',
            't_return.return_time',
            't_warehouse.warehouse_name',
            't_supplier.supplier_name',
            't_return.operator_name',
            't_goods.goods_name',
            't_return.operator_name'
        ];
        $returnGoodsDetailListQuery = ReturnGoodsDetail::find()
            ->select($fields)
            ->leftJoin('t_goods','t_goods.goods_id = t_return_detail.goods_id')
            ->leftJoin('t_return', 't_return.return_id = t_return_detail.return_id')
            ->leftJoin('t_supplier', 't_supplier.id = t_return.supplier_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_return.warehouse_id')
            ->where([
                't_supplier.supplier_status'=>AVAILABLE,
                't_warehouse.warehouse_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['return_goods_start_time'])){
            $returnGoodsDetailListQuery->andWhere(['>=', 't_return.return_time', $searchParam['return_goods_start_time']]);
        }

        if(!empty($searchParam['return_goods_end_time'])){
            $returnGoodsDetailListQuery->andWhere(['<=', 't_return.return_time', $searchParam['return_goods_end_time']]);
        }

        if(!empty($searchParam['return_id'])){
            $returnGoodsDetailListQuery->andWhere(['t_return.return_id'=>$searchParam['return_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $returnGoodsDetailListQuery->andWhere(['t_return.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['goods_name'])){
            $returnGoodsDetailListQuery->andWhere([
                'like',
                't_goods.goods_name',
                $searchParam['goods_name'],
            ]);
        }

        return $returnGoodsDetailListQuery;
    }

    //盘点单
    public static function addWarehouseCheck($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $warehouseCheckId = generateIntId('PD');
            self::insertWarehouseCheck($warehouseCheckId,$params['warehouse_check']);
            self::insertWarehouseCheckDetail($warehouseCheckId,$params['warehouse_check_detail']);
            self::changeWarehouseCheckGoods($params['warehouse_check']['warehouse_id'],$params['warehouse_check_detail']);
            self::changeCheckGoods($params['warehouse_check_detail']);
            if(!empty($params['remark'])){
                Remark::saveRemark($warehouseCheckId,Remark::CHECK_GOODS,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertWarehouseCheck($checkId,$params){
        $warehouseCheckModel = new WarehouseCheck();
        $warehouseCheckModel->check_id = $checkId;
        $warehouseCheckModel->warehouse_id = $params['warehouse_id'];
        $warehouseCheckModel->operator_id = \Yii::$app->user->getIdentity()->id;
        $warehouseCheckModel->operator_name = \Yii::$app->user->getIdentity()->name;
        $warehouseCheckModel->check_time = strtotime(date("Y-m-d",time()));
        $warehouseCheckModel->create_time = time();
        $warehouseCheckModel->update_time = time();
        $warehouseCheckModel->save();
    }

    public static function insertWarehouseCheckDetail($checkId,$params){
        foreach ($params as $warehouseCheck) {
            $info[] = array(
                'check_id' => $checkId,
                'goods_id' => $warehouseCheck['goods_id'],
                'inventory'=>$warehouseCheck['inventory'],
                'check_inventory'=>$warehouseCheck['check_inventory'],
                'defective'=>$warehouseCheck['defective_inventory'],
                'check_defective'=>$warehouseCheck['check_defective'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseCheckDetail::getDb()
            ->createCommand()
            ->batchInsert(WarehouseCheckDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function changeWarehouseCheckGoods($warehouseId,$params){
       foreach ($params as $warehouseCheckGoods){
           $warehouseGoodsInfo = WarehouseGoods::getWarehouseGoodsInfo($warehouseId,$warehouseCheckGoods['goods_id']);
           if(!empty($warehouseGoodsInfo)){
               $warehouseGoodsInfo->inventory = $warehouseCheckGoods['check_inventory'];
               $warehouseGoodsInfo->defective_inventory = $warehouseCheckGoods['check_defective'];
               $warehouseGoodsInfo->save();
           }
       }
       return true;
    }

    public static function changeCheckGoods($params){
        foreach ($params as $warehouseCheckGoods){
            $goodsInfo = Goods::getOne($warehouseCheckGoods['goods_id']);
            if(!empty($goodsInfo)){
                $goodsNum = self::getGoodsTotal($warehouseCheckGoods['goods_id']);
                $goodsInfo->inventory = $goodsNum['total_inventory'];
                $goodsInfo->save();
            }
        }
        return true;
    }

    public static function getGoodsTotal($goodsId){
        $goodsNum = WarehouseGoods::find()
            ->select(['sum(inventory) as total_inventory'])
            ->where(['goods_id'=>$goodsId])
            ->groupBy(['goods_id'])
            ->asArray()
            ->one();
        return $goodsNum;
    }

    public static function getWarehouseCheckInfo($checkId){
        $checkInfo = WarehouseCheck::find()
            ->with(["checkGoodsDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('warehouse')
            ->with('remark')
            ->where(['check_id'=>$checkId])
            ->asArray()
            ->one();
        if(!empty($checkInfo)){
            $checkInfo['check_time'] = !empty($checkInfo['check_time'])?date("Y-m-d",$checkInfo['check_time']):$checkInfo['check_time'];
        }
        if(!empty($checkInfo['remark'])){
            $checkInfo['remark']['create_time'] = date("Y-m-d",$checkInfo['remark']['create_time']);
        }
        return $checkInfo;
    }

    public static function getWarehouseCheckList($searchParam,$page,$pageNum){
        $checkGoodsListQuery = self::getCheckGoodsQuery($searchParam);
        $totalCount = $checkGoodsListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $checkGoodsListQuery = $checkGoodsListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_warehouse_check.create_time' => SORT_DESC
        ]);
        $checkGoodsList = $checkGoodsListQuery->asArray()->all();
        if(!empty($checkGoodsList)){
            foreach ($checkGoodsList as $key=>$value){
                $checkGoodsList[$key]['check_time'] = !empty($value['check_time'])?date("Y-m-d",$value['check_time']):$value['check_time'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'check_goods_list'  => $checkGoodsList,
        ];
    }

    public static function getCheckGoodsQuery($searchParam){
        $fields = [
            't_warehouse_check.check_id',
            't_warehouse_check.check_time',
            't_warehouse.warehouse_name',
            't_warehouse_check.operator_name'
        ];
        $checkGoodsListQuery = WarehouseCheck::find()
            ->select($fields)
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_check.warehouse_id')
            ->where([
                't_warehouse.warehouse_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['check_goods_start_time'])){
            $checkGoodsListQuery->andWhere(['>=', 't_warehouse_check.check_time', $searchParam['check_goods_start_time']]);
        }

        if(!empty($searchParam['check_goods_end_time'])){
            $checkGoodsListQuery->andWhere(['<=', 't_warehouse_check.check_time', $searchParam['check_goods_end_time']]);
        }

        if(!empty($searchParam['check_id'])){
            $checkGoodsListQuery->andWhere(['t_warehouse_check.check_id'=>$searchParam['check_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $checkGoodsListQuery->andWhere(['t_warehouse_check.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        return $checkGoodsListQuery;
    }

    //报废单
    public static function addWarehouseScrap($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $warehouseScarpId = generateIntId('BF');
            $totalNum = 0;
            foreach ($params['warehouse_scrap_detail'] as $good){
                $totalNum += $good['scrap_num'];
            }
            $params['warehouse_scrap']['total_scrap_num'] = $totalNum;
            self::insertWarehouseScrap($warehouseScarpId,$params['warehouse_scrap']);
            self::insertWarehouseScrapDetail($warehouseScarpId,$params['warehouse_scrap_detail']);
            self::changeWarehouseScrapGoods($params['warehouse_scrap']['warehouse_id'],$params['warehouse_scrap_detail']);
            if(!empty($params['remark'])){
                Remark::saveRemark($warehouseScarpId,Remark::SCRAP_GOODS,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertWarehouseScrap($scrapId,$params){
        $warehouseScrapModel = new WarehouseScrap();
        $warehouseScrapModel->scrap_id = $scrapId;
        $warehouseScrapModel->scrap_num = $params['total_scrap_num'];
        $warehouseScrapModel->warehouse_id = $params['warehouse_id'];
        $warehouseScrapModel->scrap_time = $params['scrap_time'];
        $warehouseScrapModel->operator_id = \Yii::$app->user->getIdentity()->id;
        $warehouseScrapModel->operator_name = \Yii::$app->user->getIdentity()->name;
        $warehouseScrapModel->create_time = time();
        $warehouseScrapModel->update_time = time();
        $warehouseScrapModel->save();
    }

    public static function insertWarehouseScrapDetail($scrapId,$params){
        foreach ($params as $warehouseScrap) {
            $info[] = array(
                'scrap_id' => $scrapId,
                'goods_id' => $warehouseScrap['goods_id'],
                'scrap_inventory_num'=>$warehouseScrap['scrap_inventory_num'],
                'scrap_defective_num'=>$warehouseScrap['scrap_defective_num'],
                'inventory'=>$warehouseScrap['inventory'],
                'defective_inventory'=>$warehouseScrap['defective_inventory'],
                'operator_id'=>\Yii::$app->user->getIdentity()->id,
                'operator_name' => \Yii::$app->user->getIdentity()->name,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseScrapDetail::getDb()
            ->createCommand()
            ->batchInsert(WarehouseScrapDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function changeWarehouseScrapGoods($warehouseId,$goodsArr){
        foreach ($goodsArr as $goods){
            if(!empty($goods['scrap_inventory_num']) || !empty($goods['scrap_defective_num'])){
                $actual_num = self::plus_minus_conversion($goods['scrap_inventory_num']);
                $actual_defective_num = self::plus_minus_conversion($goods['scrap_defective_num']);
                self::changeGooodsNum($goods['goods_id'],$actual_num);
                $warehouseGoodsInfo = WarehouseGoods::getWarehouseGoodsInfo($warehouseId,$goods['goods_id']);
                if(!empty($warehouseGoodsInfo)){
                    $inventory = $warehouseGoodsInfo->inventory;
                    $newNum = $inventory + $actual_num;
                    if($newNum < 0){
                        throw new \app\exceptions\WarehouseException(21006);
                    }
                    $warehouseGoodsInfo->inventory = $newNum;
                    $defectiveInventory = $warehouseGoodsInfo->defective_inventory;
                    $newDefectiveNum = $defectiveInventory + $actual_defective_num;
                    if($newDefectiveNum < 0){
                        throw new \app\exceptions\WarehouseException(21007);
                    }
                    $warehouseGoodsInfo->defective_inventory = $newDefectiveNum;
                    $damageInventory = $warehouseGoodsInfo->damage_inventory;
                    $newDamageNum = $damageInventory + $goods['scrap_inventory_num'] + $goods['scrap_defective_num'];
                    $warehouseGoodsInfo->damage_inventory = $newDamageNum;
                    $warehouseGoodsInfo->save();
                }
            }
        }
        return true;
    }

    public static function getWarehouseScrapInfo($scrapId){
        $scrapInfo = WarehouseScrap::find()
            ->with(["scrapGoodsDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('warehouse')
            ->with('remark')
            ->where(['scrap_id'=>$scrapId])
            ->asArray()
            ->one();
        if(!empty($scrapInfo)){
            $scrapInfo['scrap_time'] = !empty($scrapInfo['scrap_time'])?date("Y-m-d",$scrapInfo['scrap_time']):$scrapInfo['scrap_time'];
        }
        if(!empty($scrapInfo['remark'])){
            $scrapInfo['remark']['create_time'] = !empty($scrapInfo['remark']['create_time'])?date("Y-m-d",$scrapInfo['remark']['create_time']):$scrapInfo['remark']['create_time'];
        }else{
            $scrapInfo['remark'] = (object)[];
        }

        return $scrapInfo;
    }

    public static function getWarehouseScrapList($searchParam,$page,$pageNum){
        $scrapListQuery = self::getScrapListQuery($searchParam);
        $totalCount = $scrapListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $scrapListQuery = $scrapListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_scrap.create_time' => SORT_DESC
        ]);
        $scrapList = $scrapListQuery->asArray()->all();
        if(!empty($scrapList)){
            foreach ($scrapList as $key=>$value){
                $scrapList[$key]['scrap_time'] = !empty($value['scrap_time'])?date("Y-m-d",$value['scrap_time']):$value['scrap_time'];
            }
        }

        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'scrap_list'  => $scrapList,
        ];
    }

    public static function getScrapListQuery($searchParam){
        $fields = [
            't_scrap.scrap_id',
            't_scrap.scrap_num',
            't_scrap.scrap_time',
            't_warehouse.warehouse_name',
            't_scrap.operator_name',
        ];
        $scrapListQuery = WarehouseScrap::find()
            ->select($fields)
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_scrap.warehouse_id')
            ->where([
                't_warehouse.warehouse_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['scrap_goods_start_time'])){
            $scrapListQuery->andWhere(['>=', 't_scrap.scrap_time', $searchParam['scrap_goods_start_time']]);
        }

        if(!empty($searchParam['scrap_goods_end_time'])){
            $scrapListQuery->andWhere(['<=', 't_scrap.scrap_time', $searchParam['scrap_goods_end_time']]);
        }

        if(!empty($searchParam['scrap_id'])){
            $scrapListQuery->andWhere(['t_scrap.scrap_id'=>$searchParam['scrap_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $scrapListQuery->andWhere(['t_scrap.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        return $scrapListQuery;
    }

    public static function getWarehouseScrapDetailList($searchParam,$page,$pageNum){
        $scrapListDetailQuery = self::getScrapDetailListQuery($searchParam);
        $totalCount = $scrapListDetailQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $scrapListDetailQuery = $scrapListDetailQuery->offset($offset)->limit($pageNum)->orderBy([
            't_scrap.create_time' => SORT_DESC
        ]);
        $scrapDetailList = $scrapListDetailQuery->asArray()->all();
        if(!empty($scrapDetailList)){
            foreach ($scrapDetailList as $key=>$value){
                $scrapDetailList[$key]['scrap_time'] = !empty($value['scrap_time'])?date("Y-m-d",$value['scrap_time']):$value['scrap_time'];
                $scrapDetailList[$key]['scrap_num'] = $value['scrap_inventory_num'] + $value['scrap_defective_num'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'scrap_detail_list'  => $scrapDetailList,
        ];
    }

    public static function getScrapDetailListQuery($searchParam){
        $fields = [
            't_scrap_detail.scrap_id',
            't_scrap_detail.scrap_inventory_num',
            't_scrap_detail.scrap_defective_num',
            't_scrap_detail.operator_name',
            't_warehouse.warehouse_name',
            't_scrap.scrap_time',
            't_goods.goods_name'
        ];
        $scrapListQuery = WarehouseScrapDetail::find()
            ->select($fields)
            ->leftJoin('t_goods', 't_goods.goods_id = t_scrap_detail.goods_id')
            ->leftJoin('t_scrap', 't_scrap.scrap_id = t_scrap_detail.scrap_id')
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_scrap.warehouse_id')
            ->where([
                't_warehouse.warehouse_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['scrap_goods_start_time'])){
            $scrapListQuery->andWhere(['>=', 't_scrap.scrap_time', $searchParam['scrap_goods_start_time']]);
        }

        if(!empty($searchParam['scrap_goods_end_time'])){
            $scrapListQuery->andWhere(['<=', 't_scrap.scrap_time', $searchParam['scrap_goods_end_time']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $scrapListQuery->andWhere(['t_scrap.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['scrap_id'])){
            $scrapListQuery->andWhere(['t_scrap.scrap_id'=>$searchParam['scrap_id']]);
        }

        if(!empty($searchParam['goods_name'])){
            $scrapListQuery->andWhere([
                'like',
                't_goods.goods_name',
                $searchParam['goods_name'],
            ]);
        }

        return $scrapListQuery;
    }

    //仓库
    public static function addWarehouse($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $warehouseId = generateIntId('CK');
            self::insertWarehouse($warehouseId,$params['warehouse']);
            //生成仓库并且把所有商品都加到这个仓库里
            if($params['warehouse']['warehouse_type'] == Warehouse::REAL_LIBRARY){
                $conditions = ['goods_type'=>Warehouse::REAL_LIBRARY];
            }else{
                $conditions = ['goods_type'=>Warehouse::VIRTUAL_LIBRARY];
            }
            $goodsList = Goods::find()->where($conditions)->asArray()->all();
            if(!empty($goodsList)){
                self::insertWarehouseGood($warehouseId,$goodsList);
            }
            if(!empty($params['remark'])){
                Remark::saveRemark($warehouseId,Remark::WAREHOUSE,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function editWarehouse($params){
        $warehouseId = $params['warehouse_id'];
        $warehouseInfo = Warehouse::getWarehouseOne($warehouseId);
        if(!empty($warehouseInfo)){
            $warehouseInfo->warehouse_name = $params['warehouse_name'];
            $warehouseInfo->province_id = $params['province_id'];
            $warehouseInfo->province_name = $params['province_name'];
            $warehouseInfo->city_id = $params['city_id'];
            $warehouseInfo->city_name = $params['city_name'];
            $warehouseInfo->district_id = $params['district_id'];
            $warehouseInfo->district_name = $params['district_name'];
            $warehouseInfo->address = $params['address'];
            $warehouseInfo->longitude = $params['longitude'];
            $warehouseInfo->latitude = $params['latitude'];
            $warehouseInfo->warehouse_type = $params['warehouse_type'];
            $warehouseInfo->warehouse_leading_name = $params['warehouse_leading_name'];
            $warehouseInfo->save();
        }
        if(!empty($params['remark'])){
            $remark = Remark::getRemarkOne($warehouseId);
            if(!empty($remark)){
                $remark->remark = $params['remark'];
                $remark->save();
            }
        }
        return true;
    }

    public static function insertWarehouse($warehouseId,$params){
        $warehouseModel = new Warehouse();
        $warehouseModel->warehouse_name = $params['warehouse_name'];
        $warehouseModel->warehouse_id = $warehouseId;
        $warehouseModel->province_id = $params['province_id'];
        $warehouseModel->province_name = $params['province_name'];
        $warehouseModel->city_id = $params['city_id'];
        $warehouseModel->city_name = $params['city_name'];
        $warehouseModel->district_id = $params['district_id'];
        $warehouseModel->district_name = $params['district_name'];
        $warehouseModel->address = $params['address'];
        $warehouseModel->warehouse_type = $params['warehouse_type'];
        $warehouseModel->warehouse_leading_name = $params['warehouse_leading_name'];
        $warehouseModel->create_time = time();
        $warehouseModel->update_time = time();
        $warehouseModel->save();
    }

    public static function getWarehouseList($searchParam,$page,$pageNum){
        $warehouseListQuery = self::getWarehouseListQuery($searchParam);
        $totalCount = $warehouseListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $warehouseListQuery = $warehouseListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_goods.create_time' => SORT_DESC
        ]);
        $warehouseList = $warehouseListQuery->asArray()->all();
        $goodsId = [];
        if(!empty($warehouseList)){
            foreach ($warehouseList as $key=>$value){
                if (!empty($warehouseList[$key]['img'])){
                    foreach ($warehouseList[$key]['img'] as &$img){
                        $img['goods_img'] = IMAGE_DOMAIN.'/'.$img['goods_img'];
                    }
                }
                $goodsId[] = $value['goods_id'];
            }
            $goodsNum = self::getWarehouseGoodSum($goodsId);
            foreach ($warehouseList as $k=>$v){
                $warehouseList[$k]['inventory_total'] = $goodsNum[$v['goods_id']]['inventory_total'];
                $warehouseList[$k]['defective_inventory_total'] = $goodsNum[$v['goods_id']]['defective_inventory_total'];
                $warehouseList[$k]['total'] = $goodsNum[$v['goods_id']]['total'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_list'  => $warehouseList,
        ];
    }

    public static function getWarehouseListQuery($searchParam){
        $fields = [
            'goods_id',
            'goods_name',
            'type_name',
            'unit',
            'is_warn'
        ];
        $warehouseListQuery = Goods::find()->select($fields)->with('img');
        if(!empty($searchParam['type_id'])){
            $warehouseListQuery->andWhere(['type_id'=>$searchParam['type_id']]);
        }
        if(!empty($searchParam['is_warn']) || $searchParam['is_warn'] === 0){
            $warehouseListQuery->andWhere(['is_warn'=>$searchParam['is_warn']]);
        }
        if(!empty($searchParam['goods_name'])){
            $warehouseListQuery->andWhere([
                'like',
                'goods_name',
                $searchParam['goods_name'],
            ]);
        }
        return $warehouseListQuery;
    }


    public static function getWarehouseGoodSum($goodsId){
        $goodsNumlist = [];
        $goodsNum = WarehouseGoods::find()
            ->select(['sum(inventory) as inventory_total','goods_id','sum(defective_inventory) as defective_inventory_total'])
            ->where(['goods_id'=>$goodsId,'relation_status'=>AVAILABLE])
            ->groupBy(['goods_id'])
            ->asArray()
            ->all();
        $goodsIds = [];
        if(!empty($goodsNum)){
            foreach ($goodsNum as $v){
                $goodsIds[] = $v['goods_id'];
            }
            foreach ($goodsNum as $goods){
                $goodsNumlist[$goods['goods_id']]['inventory_total'] = $goods['inventory_total'];
                $goodsNumlist[$goods['goods_id']]['defective_inventory_total'] = $goods['defective_inventory_total'];
                $goodsNumlist[$goods['goods_id']]['total'] = $goods['inventory_total'] + $goods['defective_inventory_total'];
            }
        }
        foreach ($goodsId as $v){
            if (!in_array($v, $goodsIds) || empty($goodsNum)) {
                $goodsNumlist[$v]['inventory_total'] = 0;
                $goodsNumlist[$v]['defective_inventory_total'] = 0;
                $goodsNumlist[$v]['total'] = 0;
            }
        }
        return $goodsNumlist;
    }

    public static function getGoodsWarehouseNum($goodsId){
        $fields = [
            't_warehouse_goods.inventory',
            't_warehouse_goods.defective_inventory',
            't_warehouse.warehouse_name',
            't_warehouse.warehouse_id'
        ];
        $goodsWarehouseList = WarehouseGoods::find()
            ->select($fields)
            ->leftJoin('t_warehouse', 't_warehouse.warehouse_id = t_warehouse_goods.warehouse_id')
            ->where(['t_warehouse_goods.goods_id'=>$goodsId,'t_warehouse_goods.relation_status'=>AVAILABLE])
            ->asArray()
            ->all();

        return $goodsWarehouseList;
    }

    public static function getWarehouseInfo($warehouseId){
        $warehouseInfo = Warehouse::find()
            ->with('remark')
            ->where(['warehouse_id'=>$warehouseId])
            ->asArray()
            ->one();
        if(!empty($warehouseInfo['remark'])){
            $warehouseInfo['remark']['create_time'] = date("Y-m-d H:i:s",$warehouseInfo['remark']['create_time']);
        }else{
            $warehouseInfo['remark'] = (object)[];
        }
        return $warehouseInfo;
    }

    public static function getWarehouseOneList($searchParam,$page,$pageNum){
        $warehouseOneListQuery = self::getWarehouseOneListQuery($searchParam);
        $totalCount = $warehouseOneListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $warehouseOneListQuery = $warehouseOneListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_warehouse_goods.create_time' => SORT_DESC
        ]);
        $warehouseOneList = $warehouseOneListQuery->asArray()->all();
        if(!empty($warehouseOneList)){
            foreach ($warehouseOneList as &$row){
                if (!empty($row['img'])){
                    foreach ($row['img'] as &$img){
                        $img['goods_img'] = IMAGE_DOMAIN.'/'.$img['goods_img'];
                    }
                }
            }
        }

        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'warehouse_one_list'  => $warehouseOneList,
        ];
    }

    public static function getWarehouseOneListQuery($searchParam){
        $fields = [
            't_warehouse_goods.inventory',
            't_warehouse_goods.defective_inventory',
            't_goods.goods_id',
            't_goods.goods_name',
            't_goods.type_name',
            't_goods.unit',
            't_goods.is_warn'
        ];
        $warehouseOneListQuery = WarehouseGoods::find()
            ->select($fields)
            ->leftJoin('t_goods', 't_goods.goods_id = t_warehouse_goods.goods_id')
            ->with('img')
            ->where(['warehouse_id'=>$searchParam['warehouse_id']])
        ;

        if(!empty($searchParam['type_id'])){
            $warehouseOneListQuery->andWhere(['t_goods.type_id'=>$searchParam['type_id']]);
        }

        if(!empty($searchParam['is_warn']) || $searchParam['is_warn'] === 0){
            $warehouseOneListQuery->andWhere(['t_goods.is_warn'=>$searchParam['is_warn']]);
        }

        if(!empty($searchParam['goods_name'])){
            $warehouseOneListQuery->andWhere([
                'like',
                't_goods.goods_name',
                $searchParam['goods_name'],
            ]);
        }

        return $warehouseOneListQuery;
    }

    public static function insertWarehouseGood($warehouseId,$goodsList){
        foreach ($goodsList as $goods) {
            $info[] = array(
                'warehouse_id' => $warehouseId,
                'goods_id' => $goods['goods_id'],
                'inventory'=>0,
                'damage_inventory'=>0,
                'defective_inventory'=>0,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseGoods::getDb()
            ->createCommand()
            ->batchInsert(WarehouseGoods::tableName(), $columns, $info)
            ->execute();
    }

    public static function insertGoodWarehouse($goodsId,$warehouseList){
        foreach ($warehouseList as $warehouse) {
            $info[] = array(
                'warehouse_id' => $warehouse['warehouse_id'],
                'goods_id' => $goodsId,
                'inventory'=>0,
                'damage_inventory'=>0,
                'defective_inventory'=>0,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        WarehouseGoods::getDb()
            ->createCommand()
            ->batchInsert(WarehouseGoods::tableName(), $columns, $info)
            ->execute();
    }

    //对账单
    public static function addBalanceAccount($params){
        $balanceInfo = [];
        $check = self::checkTime($params['balance']['start_time'],$params['balance']['end_time']);
        if($check){
            throw new \app\exceptions\WarehouseException(21004);
        }
        $balanceId = generateIntId('DZ');
        $searchWarehouseInParams = [];
        $searchWarehouseInParams['warehouse_in_start_time'] = $params['balance']['start_time'];
        $searchWarehouseInParams['warehouse_in_end_time'] = $params['balance']['end_time'];
        $searchWarehouseInParams['warehouse_status'] = WarehouseIn::ALREADT_WAREHOUSE_IN;
        $warehouseInTotal = self::getWarehouseInList($searchWarehouseInParams,$page=null,$pageNum=null);
        $warehouseInlist = $warehouseInTotal['warehouse_in_list'];
        $searchReturnParams['return_goods_start_time'] = $params['balance']['start_time'];
        $searchReturnParams['return_goods_end_time'] = $params['balance']['end_time'];
        $searchReturnParams['return_status'] = ReturnGoods::ALREADT_RETURN_GOODS;
        $returnlistTotal = self::getReturnGoodsList($searchReturnParams,$page=null,$pageNum=null);
        $returnlist = $returnlistTotal['return_goods_list'];
        if(empty($warehouseInlist) && empty($returnlist)){
            throw new \app\exceptions\WarehouseException(21005);
        }
        $list  = self::chunkArr($warehouseInlist,$returnlist);
        $params['balance_detail'] = $list['list'];
        $warehouseResult = [];
        $arr = [];
        if(!empty($list['list'])){
            foreach ($list['list'] as $k => $v){
                if($v['type'] == Warehouse::warehouseReturn){
                    $list['list'][$k]['total_num'] = self::plus_minus_conversion($v['total_num']);
                    $list['list'][$k]['total_amount'] = self::plus_minus_conversion($v['total_amount']) / 100;
                }else{
                    $list['list'][$k]['total_amount'] = $v['total_amount'] / 100;
                }
                $list['list'][$k]['time'] = date("Y-m-d",$v['time']);
            }
            foreach ($list['list'] as $key => $info) {
                $warehouseResult[$info['supplier_id']]['supplier_name'] = $info['supplier_name'];
                $warehouseResult[$info['supplier_id']]['supplier_list'][] = $info;
            }
            foreach ($warehouseResult as $warehouse){
                $totalNum = array_reduce($warehouse['supplier_list'], function ($pre, $curr){
                    return $pre + $curr["total_num"];
                }, 0);
                $totalAmount = array_reduce($warehouse['supplier_list'], function ($pre, $curr){
                    return $pre + $curr["total_amount"];
                }, 0);
                usort($warehouse['supplier_list'], function ($a, $b){
                    if ( $a['time'] == $b["time"]){
                        return 0;
                    }
                    return $a['time'] > $b["time"]?1:-1;
                });
                $arr[] = [
                    'total_num'=>$totalNum,
                    'total_amount'=>$totalAmount,
                    'supplier_name'=>$warehouse['supplier_name'],
                    'supplier_list'=>$warehouse["supplier_list"]
                ];
            }
        }
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $balanceAccount = self::insertBalanceAccount($balanceId,$params['balance']);
            self::insertBalanceAccountDetail($balanceId,$params['balance_detail']);
            if(!empty($list['warehouse_in_ids'])){
                self::updateAllWarehouseIn($list['warehouse_in_ids'],$type=self::CLOSE_BALANCE);
            }
            if(!empty($list['return_ids'])){
                self::updateAllReturn($list['return_ids'],$type=self::CLOSE_BALANCE);
            }
            $transaction->commit();

            $balanceInfo['balance_id'] = $balanceAccount->balance_id;
            $balanceInfo['balance_start_time'] = date("Y-m-d",$balanceAccount->balance_start_time);
            $balanceInfo['balance_end_time'] = date("Y-m-d",$balanceAccount->balance_end_time);
            $balanceInfo['balance_list'] = $arr;

            return $balanceInfo;

        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function checkTime($startTime,$endTime){
        $check = BalanceAccount::find()
            ->where([
                '!=',
                'balance_status',
                BalanceAccount::ALREADY_CLOSE
            ])
            ->andWhere([
                'or',
                [
                    'and',
                    ['<=','balance_start_time', $startTime],
                    ['>=', 'balance_end_time', $startTime]
                ],
                [
                    'and',
                    ['<=', 'balance_start_time', $endTime],
                    ['>=', 'balance_end_time', $endTime]
                ],
                [
                    'and',
                    ['>=', 'balance_start_time', $startTime],
                    ['<=', 'balance_end_time', $endTime]
                ]
            ])->exists();
        return $check;
    }

    public static function insertBalanceAccount($balanceId,$params){
        $balanceModel = new BalanceAccount();
        $balanceModel->balance_id = $balanceId;
        $balanceModel->balance_start_time = $params['start_time'];
        $balanceModel->balance_end_time = $params['end_time'];
        $balanceModel->operator_id = \Yii::$app->user->getIdentity()->id;
        $balanceModel->operator_name = \Yii::$app->user->getIdentity()->name;
        $balanceModel->create_time = time();
        $balanceModel->update_time = time();
        $rs = $balanceModel->save();
        return $rs?$balanceModel:false;
    }


    public static function insertBalanceAccountDetail($balanceId,$params){
        foreach ($params as $balanceAccount) {
            $info[] = array(
                'balance_id' => $balanceId,
                'supplier_id' => $balanceAccount['supplier_id'],
                'number'=>$balanceAccount['id'],
                'number_type'=>$balanceAccount['type'],
                'number_time'=>$balanceAccount['time'],
                'warehouse_id'=>$balanceAccount['warehouse_id'],
                'total_num'=>$balanceAccount['total_num'],
                'total_account'=>$balanceAccount['total_amount'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        BalanceAccountDetail::getDb()
            ->createCommand()
            ->batchInsert(BalanceAccountDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function chunkArr($warehouseInlist,$returnlist){
        $warehouseInlistNew = [];
        $returnlistNew = [];
        $warehouseInIds = [];
        $returnInIds = [];
        if(!empty($warehouseInlist)){
            foreach ($warehouseInlist as $key=>$val){
                $warehouseInIds[] = $val['warehouse_in_id'];
                $warehouseInlistNew[$key]['id'] = $val['warehouse_in_id'];
                $warehouseInlistNew[$key]['supplier_id'] = $val['supplier_id'];
                $warehouseInlistNew[$key]['supplier_name'] = $val['supplier_name'];
                $warehouseInlistNew[$key]['type'] = Warehouse::warehouseIn;
                $warehouseInlistNew[$key]['time'] = strtotime($val['in_time']);
                $warehouseInlistNew[$key]['warehouse_name'] = $val['warehouse_name'];
                $warehouseInlistNew[$key]['warehouse_id'] = $val['warehouse_id'];
                $warehouseInlistNew[$key]['total_num'] = $val['actual_num'];
                $warehouseInlistNew[$key]['total_amount'] = $val['total_amount'] * 100;
            }
        }
        if(!empty($returnlist)){
            foreach ($returnlist as $k=>$v){
                $returnInIds[] = $v['return_id'];
                $returnlistNew[$k]['id'] = $v['return_id'];
                $returnlistNew[$k]['supplier_id'] = $v['supplier_id'];
                $returnlistNew[$k]['supplier_name'] = $v['supplier_name'];
                $returnlistNew[$k]['type'] = Warehouse::warehouseReturn;
                $returnlistNew[$k]['time'] = strtotime($v['return_time']);
                $returnlistNew[$k]['warehouse_id'] = $v['warehouse_id'];
                $returnlistNew[$k]['warehouse_name'] = $v['warehouse_name'];
                $returnlistNew[$k]['total_num'] = $v['return_total_num'];
                $returnlistNew[$k]['total_amount'] = $v['return_amount'];
            }
        }
        $resultList = array_merge($warehouseInlistNew,$returnlistNew);
        $result['list'] = $resultList;
        $result['warehouse_in_ids'] = $warehouseInIds;
        $result['return_ids'] = $returnInIds;
        return $result;
    }


    public static function updateAllWarehouseIn($warehouseInIds,$type){
        if($type == self::CLOSE_BALANCE){
            $changeValue = WarehouseIn::CLOSE_BALANCE;
        }else{
            $changeValue = WarehouseIn::NO_CLOSE_BALANCE;
        }
        $rs = WarehouseIn::updateAll([
            'is_balance' => $changeValue,
            'update_time' => time()
        ], [
            'warehouse_in_id' => $warehouseInIds,
        ]);
        return $rs;
    }

    public static function updateAllReturn($returnIds,$type){
        if($type == self::CLOSE_BALANCE){
            $changeValue = ReturnGoods::CLOSE_BALANCE;
        }else{
            $changeValue = ReturnGoods::NO_CLOSE_BALANCE;
        }
        $rs = ReturnGoods::updateAll([
            'is_balance' => $changeValue,
            'update_time' => time()
        ], [
            'return_id' => $returnIds,
        ]);
        return $rs;
    }

    public static function getBalanceAccountList($searchParam,$page,$pageNum){
        $balanceAccountListQuery = self::getBalanceAccountListQuery($searchParam);
        $totalCount = $balanceAccountListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $balanceAccountListQuery = $balanceAccountListQuery->offset($offset)->limit($pageNum)->orderBy([
            'create_time' => SORT_DESC
        ]);
        $balanceAccountList = $balanceAccountListQuery->asArray()->all();
        if(!empty($balanceAccountList)){
            foreach ($balanceAccountList as $key=>$value){
                $balanceAccountList[$key]['balance_start_time'] = !empty($value['balance_start_time'])?date("Y-m-d",$value['balance_start_time']):$value['balance_start_time'];
                $balanceAccountList[$key]['balance_end_time'] = !empty($value['balance_end_time'])?date("Y-m-d",$value['balance_end_time']):$value['balance_end_time'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'balance_account_list'  => $balanceAccountList,
        ];
    }

    public static function getBalanceAccountListQuery($searchParam){
        $fields = [
            'balance_id',
            'balance_start_time',
            'balance_end_time',
            'balance_status',
            'operator_name'
        ];
        $balanceAccountListQuery = BalanceAccount::find()
            ->select($fields);

        if(!empty($searchParam['balance_id'])){
            $balanceAccountListQuery->andWhere(['balance_id'=>$searchParam['balance_id']]);
        }

        if(!empty($searchParam['start_time'])){
            $balanceAccountListQuery->andWhere(['>=', 'start_time', $searchParam['start_time']]);
        }

        if(!empty($searchParam['end_time'])){
            $balanceAccountListQuery->andWhere(['<=', 'end_time', $searchParam['end_time']]);
        }

        if(!empty($searchParam['balance_status'])){
            $balanceAccountListQuery->andWhere(['balance_status'=>$searchParam['balance_status']]);
        }

        return $balanceAccountListQuery;
    }

    public static function getBalanceAccountInfo($balanceId){
        $arr = [];
        $balanceInfoArr = [];
        $balanceInfo = BalanceAccount::find()
            ->with(["balanceAccountDetail" => function(\yii\db\ActiveQuery $query){
                $query->with(["supplier",'warehouse']);
            }])
            ->with('remark')
            ->where(['balance_id'=>$balanceId])
            ->asArray()
            ->one();
        $warehouseResult = [];
        foreach ($balanceInfo['balanceAccountDetail'] as $key => $info) {
            $warehouseResult[$info['supplier_id']]['supplier_name'] = $info['supplier']['supplier_name'];
            $warehouseResult[$info['supplier_id']]['supplier_list'][] = [
                'id'=>$info['number'],
                'type'=>$info['number_type'],
                'warehouse_name'=>$info['warehouse']['warehouse_name'],
                'time'=>date('Y-m-d', $info['number_time']),
                'total_num'=>$info['number_type'] == Warehouse::warehouseReturn?self::plus_minus_conversion($info['total_num']):$info['total_num'],
                'total_amount'=>$info['number_type'] == Warehouse::warehouseReturn?self::plus_minus_conversion($info['total_account']) / 100 :$info['total_account'] / 100 ,
            ];
        }

        foreach ($warehouseResult as $warehouse){
            $totalNum = array_reduce($warehouse['supplier_list'], function ($pre, $curr){
                return $pre + $curr["total_num"];
            }, 0);
            $totalAmount = array_reduce($warehouse['supplier_list'], function ($pre, $curr){
                return $pre + $curr["total_amount"];
            }, 0);
            usort($warehouse['supplier_list'], function ($a, $b){
                if ( $a['time'] == $b["time"]){
                    return 0;
                }
                return $a['time'] > $b["time"]?1:-1;
            });
            $arr[] = [
                'total_num'=>$totalNum,
                'total_amount'=>$totalAmount,
                'supplier_name'=>$warehouse['supplier_name'],
                'supplier_list'=>$warehouse["supplier_list"]
            ];
        }
        if(!empty($balanceInfo['remark'])){
            foreach ($balanceInfo['remark'] as $key=>$remark){
                $balanceInfo['remark'][$key]['create_time'] = date("Y-m-d H:i:s",$balanceInfo['remark'][$key]['create_time']);
            }
        }else{
            $balanceInfo['remark'] = [];
        }
        $balanceInfoArr['balance_id'] = $balanceInfo['balance_id'];
        $balanceInfoArr['balance_status'] = $balanceInfo['balance_status'];
        $balanceInfoArr['balance_start_time'] = date('Y-m-d', $balanceInfo['balance_start_time']);
        $balanceInfoArr['balance_end_time'] = date('Y-m-d', $balanceInfo['balance_end_time']);
        $balanceInfoArr['operator_name'] = $balanceInfo['operator_name'];
        $balanceInfoArr['balance_list'] = $arr;
        $balanceInfoArr['remark'] = $balanceInfo['remark'];
        return $balanceInfoArr;
    }

    public static function closeBalanceAccount($balanceId){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $balanceInfo = BalanceAccount::getBalanceAccountInfo($balanceId);
            if(!empty($balanceInfo)){
                $warehouseInIds = [];
                $returnIds = [];
                $balanceInfo->balance_status = BalanceAccount::ALREADY_CLOSE;
                $balanceInfo->save();
                $detailList = BalanceAccountDetail::getBalanceAccountDetail($balanceId);
                foreach ($detailList as $value){
                    if($value['number_type'] == Warehouse::warehouseIn){
                        $warehouseInIds[] = $value['number'];
                    }else{
                        $returnIds[] = $value['number'];
                    }
                }
                if(!empty($warehouseInIds)){
                    self::updateAllWarehouseIn($warehouseInIds,$type=self::OPEN_BALANCE);
                }
                if(!empty($returnIds)){
                    self::updateAllReturn($returnIds,$type=self::OPEN_BALANCE);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function changeBalanceAccount($params){
        $balanceInfo = BalanceAccount::getBalanceAccountInfo($params['balance_id']);
        if(!empty($balanceInfo)){
            $balanceInfo->balance_status = $params['balance_status'];
            $balanceInfo->save();
        }
        if(!empty($params['remark'])){
            Remark::saveRemark($params['balance_id'],Remark::SUPPLIER_BALANCE_ACCOUNT,$params['remark']);
        }
        return true;
    }

    public static function getBalanceStatus($balanceStatus,$balanceType){
        switch ($balanceStatus){
            case BalanceAccount::INITIAL_STATUS:{
                $balanceStatus = BalanceAccount::INITIAL_STATUS_NAME;
                break;
            }
            case BalanceAccount::ALREADT_SUBMIT:{
                if($balanceType == BalanceAccount::FINANCE_OPEARTE){
                    $balanceStatus = BalanceAccount::WAITING_AGREE;
                }else{
                    $balanceStatus = BalanceAccount::ALREADT_SUBMIT_NAME;
                }
                break;
            }
            case BalanceAccount::FINANCE_UNSUBMIT:{
                if($balanceType == BalanceAccount::FINANCE_OPEARTE){
                    $balanceStatus = BalanceAccount::ALREADT_UNAGREE;
                }else{
                    $balanceStatus = BalanceAccount::FINANCE_UNSUBMIT_NAME;
                }
                break;
            }
            case BalanceAccount::ALREADY_SHUT_DOWN:{
                $balanceStatus = BalanceAccount::ALREADY_SHUT_DOWN_NAME;
                break;
            }
            case BalanceAccount::ALREADY_CLOSE:{
                $balanceStatus = BalanceAccount::ALREADY_CLOSE_NAME;
                break;
            }
        }
        return $balanceStatus;
    }

}
