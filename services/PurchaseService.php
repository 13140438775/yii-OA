<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:53:22
 */
namespace app\services;

use app\models\Order;
use app\models\OrderDetail;
use app\models\PurchaseSupplier;
use app\models\PurchaseWarehouse;
use app\models\WarehouseOut;
use Yii;
use app\models\Type;
use app\models\Purchase;
use app\models\PurchaseDetail;
use app\models\Goods;
use app\models\Remark;
use yii\helpers\ArrayHelper;

class PurchaseService{

    const PURCHASE_GOODS_PAGENUM = 15;//采购商品分页数量
    const PURCHASE_GOODS_LIST_PAGENUM = 15;//采购订单分页数量
    const FINISH_TIME = 0;//完成时间默认值
    const PURCHASE_TIME = 0;//采购时间默认值
    const GYM_ID = 0;//场馆默认值
    const GYM_NAME = '';//场馆名称默认值
    const ARRIVAL_NUM = 0;//默认到货数量


    public static function getType(){
        $typeList = Type::getTypes();
        return $typeList;
    }

    public static function getPurchaseGoodsList($searchParam,$page,$pageNum){
        $purchaseGoodsQuery = self::getPurchaseGoodsQuery($searchParam);
        $offset = ($page - 1) * $pageNum;
        $purchaseGoodsQuery = $purchaseGoodsQuery->offset($offset)->limit($pageNum)->orderBy([
            't_goods_supplier.create_time' => SORT_DESC
        ]);
        $totalCount = $purchaseGoodsQuery->count('1');
        $purchaseGoodsList = $purchaseGoodsQuery->asArray()->all();
        if(!empty($purchaseGoodsList)){
            foreach ($purchaseGoodsList as $key=>$value){
                $purchaseGoodsList[$key]['purchase_amount'] = !empty($value['purchase_amount'])?$value['purchase_amount']/100:$value['purchase_amount'];
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'purchase_goods_list'  => $purchaseGoodsList,
        ];
    }

    public static function getPurchaseGoodsQuery($searchParam){
        $fields = [
            't_goods.goods_id',
            't_goods.goods_name',
            't_goods.brand',
            't_goods.model',
            't_goods.unit',
            't_goods.purchase_amount',
            't_goods.inventory',
        ];
        $purchaseGoodsQuery = Goods::find()
            ->select($fields)
            ->leftJoin('t_goods_supplier', 't_goods.goods_id = t_goods_supplier.goods_id')
            ->where([
                't_goods_supplier.relation_status'=>AVAILABLE
            ]);

        if(!empty($searchParam['supplier_id'])){
            $purchaseGoodsQuery->andWhere(['t_goods_supplier.supplier_id'=>$searchParam['supplier_id']]);
        }

        if(!empty($searchParam['goods_id'])){
            $purchaseGoodsQuery->andWhere(['t_goods.goods_id'=>$searchParam['goods_id']]);
        }
        if(!empty($searchParam['goods_name'])){
            $purchaseGoodsQuery->andWhere(['LIKE', 't_goods.goods_name', $searchParam['goods_name']]);
        }

        if(!empty($searchParam['warehouse_type'])){
            $purchaseGoodsQuery->andWhere(['t_goods.goods_type'=>$searchParam['warehouse_type']]);
        }

        return $purchaseGoodsQuery;
    }

    public static function addPurchase($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $purchaseId = generateIntId('CG');
            self::insertPurchase($purchaseId,$params['purchase']);
            self::insertPurchaseDetail($purchaseId,$params['purchase_goods']);
            self::insertPurchaseSupplier($purchaseId,$params['purchase_supplier']);
            self::insertPurchaseWarehouse($purchaseId,$params['purchase_warehouse']);
            if(!empty($params['remark'])){
                Remark::saveRemark($purchaseId,Remark::PURCHASE,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function editPurchase($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $purchaseId = $params['purchase']['purchase_id'];
            $purchase = Purchase::getPurchaseInfo($purchaseId);
            if(!empty($purchase)){
                self::editPurchaseInfo($purchaseId,$params['purchase']);
            }
            $purchaseDetail = PurchaseDetail::getPurchaseDetail($purchaseId);
            if(!empty($purchaseDetail)){
                self::updatePurchaseDetail($purchaseId);
                self::insertPurchaseDetail($purchaseId,$params['purchase_goods']);
            }
            $purchaseWarehouse = PurchaseWarehouse::getPurchaseWarehouse($purchaseId);
            if(!empty($purchaseWarehouse)){
                self::editPurchaseWarehouse($purchaseId,$params['purchase']);
            }
            self::editPurchaseRemark($purchaseId,$params['remark']['remark_id'], $params['remark']['remark']);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw  $e;
        }
    }

    public static function insertPurchase($purchaseId,$params){
        $purchaseModel = new Purchase();
        $purchaseModel->purchase_id = $purchaseId;
        $purchaseModel->order_id = $params['order_id'];
        $purchaseModel->actual_amount = $params['actual_amount'] * 100;
        $purchaseModel->warehouse_id = $params['warehouse_id'];
        $purchaseModel->purchase_type = $params['purchase_type'];
        $purchaseModel->purchase_time = ArrayHelper::getValue($params,'purchase_time',self::PURCHASE_TIME);
        $purchaseModel->finish_time = ArrayHelper::getValue($params,'finish_time',self::FINISH_TIME);
        $purchaseModel->supplier_id = $params['supplier_id'];
        $purchaseModel->gym_id = ArrayHelper::getValue($params,'gym_id',self::GYM_ID);
        $purchaseModel->gym_name = ArrayHelper::getValue($params,'gym_name',self::GYM_NAME);
        if($params['purchase_type'] == Purchase::PURCHASE_SELF_ADD){
            $purchaseModel->operator_id = \Yii::$app->user->getIdentity()->id;
            $purchaseModel->operator_name = \Yii::$app->user->getIdentity()->name;
        }else{
            $purchaseModel->operator_id = $params['operator_id'];
            $purchaseModel->operator_name = $params['operator_name'];
        }
        $purchaseModel->create_time = time();
        $purchaseModel->update_time = time();
        $purchaseModel->save();
        return true;
    }

    public static function insertPurchaseDetail($purchaseId, $goodsDetail)
    {
        foreach ($goodsDetail as $goods) {
            $info[] = array(
                'purchase_num' => $goods['purchase_num'],
                'goods_id' => $goods['goods_id'],
                'actual_amount' => $goods['actual_amount'] * 100,
                'warehouse_id' => $goods['warehouse_id'],
                'arrival_num'=> ArrayHelper::getValue($goods,'arrival_num',self::ARRIVAL_NUM),
                'purchase_id' => $purchaseId,
                'operator_id' => \Yii::$app->user->getIdentity()->id,
                'operator_name' => \Yii::$app->user->getIdentity()->name,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        PurchaseDetail::getDb()
            ->createCommand()
            ->batchInsert(PurchaseDetail::tableName(), $columns, $info)
            ->execute();
    }

    public static function insertPurchaseSupplier($purchaseId, $purchaseSuppliers)
    {
        $purchaseSupplierModel = new PurchaseSupplier();
        $purchaseSupplierModel->purchase_id = $purchaseId;
        $purchaseSupplierModel->supplier_id = $purchaseSuppliers['supplier_id'];
        $purchaseSupplierModel->create_time = time();
        $purchaseSupplierModel->update_time = time();
        $purchaseSupplierModel->save();
    }

    public static function insertPurchaseWarehouse($purchaseId, $purchaseWarehouses)
    {
        $purchaseWarehouseModel = new PurchaseWarehouse();
        $purchaseWarehouseModel->purchase_id = $purchaseId;
        $purchaseWarehouseModel->warehouse_id = $purchaseWarehouses['warehouse_id'];
        $purchaseWarehouseModel->create_time = time();
        $purchaseWarehouseModel->update_time = time();
        $purchaseWarehouseModel->save();
    }

    public static function editPurchaseInfo($purchaseId,$params){
        Purchase::updateAll([
            'warehouse_id' => $params['warehouse_id'],
            'purchase_time'=>$params['purchase_time'],
            'finish_time'=>$params['finish_time'],
            'update_time' =>time(),
            'actual_amount'=>$params['actual_amount'] * 100
        ], [
            'purchase_id' => $purchaseId
        ]);
    }

    public static function editPurchaseDetail($purchaseId,$params){
        foreach ($params as $goods){
            PurchaseDetail::updateAll([
                'arrival_num'=>$goods['arrival_num'],
                'update_time' =>time()
            ], [
                'goods_id'=>$goods['goods_id'],
                'purchase_id' => $purchaseId,
                'relation_status' =>AVAILABLE,
            ]);
        }
    }

    public static function updatePurchaseDetail($purchaseId){
        PurchaseDetail::updateAll([
           'relation_status'=>UNAVAILABLE,
            'update_time' =>time()
        ], [
            'purchase_id' => $purchaseId
        ]);
    }

    public static function editPurchaseWarehouse($purchaseId,$params){
            PurchaseWarehouse::updateAll([
                'warehouse_id' => $params['warehouse_id'],
                'update_time' =>time()
            ], [
                'purchase_id' => $purchaseId
            ]);
    }

    public static function getPurchasesList($searchParam,$page,$pageNum){
        $purchaseListQuery = self::getPurchasesQuery($searchParam);
        $offset = ($page - 1) * $pageNum;
        $purchaseListQuery = $purchaseListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_purchase.create_time' => SORT_DESC
        ]);
        $totalCount = $purchaseListQuery->count('1');
        $purchaseList = $purchaseListQuery->asArray()->all();
        if (!empty($purchaseList)){
            foreach ($purchaseList as $key=>$value){
                $purchaseList[$key]['purchase_time'] = !empty($value['purchase_time'])?date("Y-m-d",$value['purchase_time']):'-';
                $purchaseList[$key]['finish_time'] = !empty($value['finish_time'])?date("Y-m-d",$value['finish_time']):'-';
                $purchaseList[$key]['gym_name'] = !empty($value['gym_name'])?$value['gym_name']:'-';
                $purchaseList[$key]['order_id'] = !empty($value['order_id'])?$value['order_id']:'-';
                $purchaseList[$key]['warehouse_in_id'] = !empty($value['warehouse_in_id'])?$value['warehouse_in_id']:'-';
                $purchaseList[$key]['actual_amount'] = !empty($value['actual_amount'])?$value['actual_amount']/100:$value['actual_amount'];
            }
        }

        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'purchase_list'  => $purchaseList,
        ];
    }

    public static function getPurchasesQuery($searchParam){
        $fields = [
            't_purchase.purchase_id',
            't_purchase.purchase_type',
            't_purchase.finish_time',
            't_supplier.supplier_name',
            't_purchase.order_id',
            't_purchase.actual_amount',
            't_purchase.purchase_status',
            't_open_project.gym_name',
            't_warehouse_in.warehouse_in_id',
            't_purchase.purchase_time',
        ];
        $purchaseListQuery = Purchase::find()
            ->select($fields)
            ->leftJoin('t_warehouse_in', 't_warehouse_in.purchase_id = t_purchase.purchase_id')
            ->leftJoin('t_supplier', 't_supplier.id = t_purchase.supplier_id')
            ->leftJoin('t_open_project', 't_open_project.id = t_purchase.gym_id')
        ;

        if(!empty($searchParam['purchaseOrSupplier'])){
            $purchaseListQuery->andWhere([
                'or',
                [
                    'like',
                    't_purchase.purchase_id',
                    $searchParam['purchaseOrSupplier'],
                ],
                [
                    'like',
                    't_supplier.supplier_name',
                    $searchParam['purchaseOrSupplier'],
                ],
            ]);
        }

        if(!empty($searchParam['purchase_start_time'])){
            $purchaseListQuery->andWhere(['>=', 't_purchase.purchase_time', $searchParam['purchase_start_time']]);
        }

        if(!empty($searchParam['purchase_end_time'])){
            $purchaseListQuery->andWhere(['<=', 't_purchase.purchase_time', $searchParam['purchase_end_time']]);
        }

        if(!empty($searchParam['finish_start_time'])){
            $purchaseListQuery->andWhere(['>=', 't_purchase.finish_time', $searchParam['finish_start_time']]);
        }

        if(!empty($searchParam['finish_end_time'])){
            $purchaseListQuery->andWhere(['<=', 't_purchase.finish_time', $searchParam['finish_end_time']]);
        }

        if(!empty($searchParam['purchase_status'])){
            $purchaseListQuery->andWhere(['t_purchase.purchase_status'=>$searchParam['purchase_status']]);
        }

        if(!empty($searchParam['order_id'])){
            $purchaseListQuery->andWhere(['t_purchase.order_id'=>$searchParam['order_id']]);
        }

        if(!empty($searchParam['warehouse_id'])){
            $purchaseListQuery->andWhere(['t_purchase.warehouse_id'=>$searchParam['warehouse_id']]);
        }

        if(!empty($searchParam['gym_name'])){
            $purchaseListQuery->andWhere([
                'like',
                't_open_project.gym_name',
                $searchParam['gym_name'],
            ]);
        }

        return $purchaseListQuery;
    }

    public static function confirmGoods($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $purchaseId = $params['purchase']['purchase_id'];
            $purchase = Purchase::getPurchaseInfo($purchaseId);
            if(!empty($purchase)){
                $purchase->purchase_time = $params['purchase']['purchase_time'];
                $purchase->finish_time = $params['purchase']['finish_time'];
                $purchase->purchase_status = Purchase::CONFIRM_PURCHASE;
                $purchase->purchase_use_status = Purchase::ADJUST_USE_PURCHASE;
                $purchase->save();
                $purchaseDetail = PurchaseDetail::getPurchaseDetail($purchaseId);
                if(!empty($purchaseDetail)){
                    self::editPurchaseDetail($purchaseId,$params['purchase_goods']);
                }
            }
            if(!empty($params['remark'])){
                Remark::saveRemark($purchaseId,Remark::PURCHASE,$params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function adjustGoods($purchaseId){
        $purchase = Purchase::getPurchaseInfo($purchaseId);
        if(!empty($purchase)){
            $purchase->purchase_use_status = Purchase::CONFIRM_USE_PURCHASE;
            $purchase->save();
        }
        return true;
    }

    public static function getPurchaseInfo($purchaseId){
        $purchase =  Purchase::find()
            ->with(["purchaseDetail" => function(\yii\db\ActiveQuery $query){
                $query->with("goods");
            }])
            ->with('supplier')
            ->with('warehouse')
            ->with('remark')
            ->with('gym')
            ->where(['purchase_id'=>$purchaseId])
            ->asArray()
            ->one();
        if(!empty($purchase)) {
            if(empty($purchase['gym'])){
                $purchase['gym']['gym_name'] = '-';
                $purchase['gym']['address'] = '-';
            }
            $purchase['warehouse']['address'] = !empty($purchase['warehouse']['address'])?$purchase['warehouse']['address']:'-';
            $purchase['purchase_time'] = !empty($purchase['purchase_time'])?date("Y-m-d",$purchase['purchase_time']):'';
            $purchase['finish_time'] = !empty($purchase['finish_time'])?date("Y-m-d",$purchase['finish_time']):'';
            $purchase['create_time'] = !empty($purchase['create_time'])?date("Y-m-d",$purchase['create_time']):'';
            $purchase['actual_amount'] = $purchase['actual_amount'] / 100;
            foreach ($purchase['purchaseDetail'] as $key=>$value){
                $purchase['purchaseDetail'][$key]['actual_amount'] = $value['actual_amount'] / 100;
                $purchase['purchaseDetail'][$key]['goods']['purchase_amount'] = $value['goods']['purchase_amount'] / 100;
            }
            if(!empty($purchase['remark'])){
                foreach ($purchase['remark'] as $k=>$remark){
                    $purchase['remark'][$k]['create_time'] = !empty($purchase['remark'][$k]['create_time'])?date("Y-m-d H:i:s",$purchase['remark'][$k]['create_time']):$purchase['remark'][$k]['create_time'];
                }
            }else{
                $purchase['remark'] = [];
            }
        }
        return $purchase;
    }

    public static function getGoodsInfo($goodsId){
        $result = [];
        $goodsList = Goods::find()
            ->select(['goods_id','goods_name','brand','model','unit'])
            ->where(['goods_id'=>$goodsId])
            ->asArray()
            ->all();

        foreach ($goodsList as $key=>$value){
            $result[$value['goods_id']] = $value;
        }
        return $result;
    }


    public static function closePurchase($purchaseId){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $purchase = Purchase::getPurchaseInfo($purchaseId);
            $purchase->purchase_status = Purchase::CLOSE_PURCHASE;
            $purchase->save();
            $purchaseDetail = PurchaseDetail::getPurchaseDetail($purchaseId);
            if(!empty($purchaseDetail)){
                PurchaseDetail::updateAll([
                    'relation_status'=>UNAVAILABLE,
                    'update_time' =>time()
                ], [
                    'purchase_id' => $purchaseId
                ]);
            }
            $purchaseWarehouse = PurchaseWarehouse::getPurchaseWarehouse($purchaseId);
            if(!empty($purchaseWarehouse)){
                PurchaseWarehouse::updateAll([
                    'relation_status'=>UNAVAILABLE,
                    'update_time' =>time()
                ], [
                    'purchase_id' => $purchaseId
                ]);
            }
            $purchaseSupplier = PurchaseSupplier::getPurchaseSupplier($purchaseId);
            if(!empty($purchaseSupplier)){
                PurchaseSupplier::updateAll([
                    'relation_status'=>UNAVAILABLE,
                    'update_time' =>time()
                ], [
                    'purchase_id' => $purchaseId
                ]);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function getPurchaseStatus($purchaseStatus){
        switch ($purchaseStatus){
            case Purchase::INITIAL_STATUS:{
                $purchaseStatus = Purchase::INITIAL_STATUS_NAME;
                break;
            }
            case Purchase::CONFIRM_PURCHASE:{
                $purchaseStatus = Purchase::CONFIRM_PURCHASE_NAME;
                break;
            }
            case Purchase::WAREHOUSE_LOADING:{
                $purchaseStatus = Purchase::WAREHOUSE_LOADING_NAME;
                break;
            }
            case Purchase::WAREHOUSE_COMPLETE:{
                $purchaseStatus = Purchase::WAREHOUSE_COMPLETE_NAME;
                break;
            }
            case Purchase::CLOSE_PURCHASE:{
                $purchaseStatus = Purchase::CLOSE_PURCHASE_NAME;
                break;
            }
        }
        return $purchaseStatus;
    }

    public static function updatePurchase($purchaseId){
        $purchase = Purchase::getPurchaseInfo($purchaseId);
        if(empty($purchase['order_id'])){
            $purchase->purchase_status = Purchase::WAREHOUSE_LOADING;
        }else{
            $purchase->purchase_status = Purchase::WAREHOUSE_COMPLETE;
        }
        $purchase->save();
        return true;
    }

    public static function editPurchaseRemark($purchaseId,$remark_id,$remark){
        $remarkInfo = Remark::find()->where(['id'=>$remark_id])->one();
        if(!empty($remarkInfo)){
            $remarkInfo->remark = $remark;
            $remarkInfo->update_time = time();
            $remarkInfo->save();
        }else{
            if(!empty($remark)){
                Remark::saveRemark($purchaseId,Remark::PURCHASE,$remark);
            }
        }
        return true;
    }

    public static function changeOrderStatus($orderId){

        $purchaseExists = Purchase::find()
            ->where(['order_id'=>$orderId])
            ->andWhere([
                'or',
                [
                   'purchase_status'=>Purchase::INITIAL_STATUS
                ],
                [
                   'purchase_status'=>Purchase::CONFIRM_PURCHASE
                ]
            ])->exists();

        $warehouseOutExists = WarehouseOut::find()
            ->where(['order_id'=>$orderId,'out_status'=>WarehouseOut::INITIAL_STATUS])
            ->exists();

        if(!$purchaseExists && !$warehouseOutExists){
            $order = Order::getOne($orderId);
            if($order->order_status == Order::PURCHASE_LOADING){
                $order->order_status = Order::SHIPPING_LOADING;
                $order->save();

                OrderDetail::updateAll([
                    'detail_status'=>Order::SHIPPING_LOADING,
                    'update_time' =>time()
                ], [
                    'order_id' => $orderId
                ]);
            }
        }
        return true;
    }


    //采购订单管理
    public static function getPurOrderList($params){
        $pur = CustomerOrderService::PURCHASE; //采购中
        $ship = CustomerOrderService::SHIP;    //发货中
        $complete = CustomerOrderService::COMPLETE; //已完成
        $addition = ['order_type'=>$params['order_type'],'order_status' => [$pur,$ship,$complete]];
        $list = CustomerOrderService::getOrderList($params,$addition);
        return $list;
    }

}