<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:53:22
 */
namespace app\services;

use Yii;
use app\models\Supplier;
use app\models\SupplierType;
use app\models\SupplierArea;
use app\models\SupplierAccount;
use app\models\Remark;
use yii\helpers\ArrayHelper;

class SupplierService{

    const SUPPLIERS_LIST_PAGENUM = 15;//供应商列表分页数量

    public static function getSupplier(){
        $supplier = Supplier::getSupplier();
        return $supplier;
    }

    public static function getSuppliersList($searchParam,$page,$pageNum){
        $supplierListQuery = self::getSuppliersListQurey($searchParam);
        $totalCount = $supplierListQuery->count('1');
        $offset = ($page - 1) * $pageNum;
        $supplierListQuery = $supplierListQuery->offset($offset)->limit($pageNum)->orderBy([
            't_supplier.create_time' => SORT_DESC
        ]);

        $supplierList = $supplierListQuery->asArray()->all();
        $supplierIds = [];
        if(!empty($supplierList)){
            foreach ($supplierList as $supplier){
                $supplierIds[] = $supplier['id'];
            }
            $supplierArea = SupplierArea::getSupplierAreaBySupplierIds($supplierIds);
            foreach ($supplierList as $k=>$v){
                foreach ($supplierArea as $area){
                    if($v['id'] == $area['supplier_id']){
                        $supplierList[$k]['region'][] = $area;
                    }
                }
            }
        }
        $supplierListArr = [];
        if(!empty($supplierList)){
            foreach ($supplierList as $key=>$value){
                $supplierListArr[$key]['id'] = $value['id'];
                $supplierListArr[$key]['email'] = $value['email'];
                $supplierListArr[$key]['supplier_name'] = $value['supplier_name'];
                $supplierListArr[$key]['contact_name'] = $value['contact_name'];
                $supplierListArr[$key]['phone'] = $value['phone'];
                $supplierListArr[$key]['area_name'] = self::getAreaName($value['region']);
            }
        }
        return [
            'page_size'=>$pageNum,
            'count' => $totalCount,
            'supplier_list'  => $supplierListArr,
        ];
    }

    public static function getSuppliersListQurey($searchParam){

        $supplierslistQuery = Supplier::find()
            ->select([
                't_supplier.id',
                't_supplier.email',
                't_supplier.supplier_name',
                't_supplier.contact_name',
                't_supplier.phone'
            ])
            ->innerJoin('t_supplier_area','t_supplier_area.supplier_id = t_supplier.id')
            ->where(['t_supplier_area.relation_status'=>AVAILABLE])
        ;

        if (!empty($searchParam['area_id'])){
            $supplierslistQuery->andWhere(['t_supplier_area.area_id'=>$searchParam['area_id']]);
        }

        if (!empty($searchParam['contact_name'])){
            $supplierslistQuery ->andWhere(['LIKE', 'contact_name', $searchParam['contact_name']]);
        }
        if (!empty($searchParam['supplier_name'])){
            $supplierslistQuery ->andWhere(['LIKE', 'supplier_name', $searchParam['supplier_name']]);
        }
        if (!empty($searchParam['phone'])){
            $supplierslistQuery ->andWhere(['LIKE', 'phone', $searchParam['phone']]);
        }
        $supplierslistQuery = $supplierslistQuery->groupBy('t_supplier.id');
        return $supplierslistQuery;
    }

    public static function addSupplier($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            //首先验证供应商名称是否有重复
            $supplierCheck = Supplier::getSupplierByName($params['suppliers']['supplier_name']);
            if(!empty($supplierCheck)){
                throw new \app\exceptions\SupplierException(21003);
            }
            $rs = self::insertSupplierInfo($params['suppliers']);
            if($rs){
                self::insertSupplierType($rs->id,$params['goods_type']);
                self::insertSupplierArea($rs->id,$params['suppliers_area']);
                self::insertSupplierAccount($rs->id,$params['suppliers_account']);
                if(!empty($params['remark'])){
                    self::insertSupplierRemark($rs->id,$params['remark']);
                }
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function editSupplier($params){
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            //首先验证供应商名称是否有重复
            $supplierCheck = Supplier::checkSupplier($params['supplier_id'],$params['suppliers']['supplier_name']);
            if(!empty($supplierCheck)){
                throw new \app\exceptions\SupplierException(21003);
            }
            $supplier = Supplier::getSupplierInfo($params['supplier_id']);
            if(!empty($supplier)){
                self::editSupplierInfo($params['supplier_id'],$params['suppliers']);
            }
            if(!empty($params['goods_type'])){
                self::editSupplierType($params['supplier_id']);
                self::insertSupplierType($params['supplier_id'],$params['goods_type']);
            }
            if(!empty($params['suppliers_area'])){
                self::editSupplierArea($params['supplier_id']);
                self::insertSupplierArea($params['supplier_id'],$params['suppliers_area']);
            }
            if(!empty($params['suppliers_account'])){
                self::editSupplierAccount($params['supplier_id']);
                self::insertSupplierAccount($params['supplier_id'],$params['suppliers_account']);
            }
            if(!empty($params['remark'])) {
                self::editSupplierRemark($params['supplier_id'], $params['remark']);
            }
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    public static function insertSupplierInfo($params){
        $supplierModel = new Supplier();
        $supplierModel->supplier_name = $params['supplier_name'];
        $supplierModel->province_id = $params['province_id'];
        $supplierModel->city_id = $params['city_id'];
        $supplierModel->district_id = $params['district_id'];
        $supplierModel->address = $params['address'];
        $supplierModel->contact_name = $params['contact_name'];
        $supplierModel->phone = $params['phone'];
        $supplierModel->email = $params['email'];
        $supplierModel->create_time = time();
        $supplierModel->update_time = time();
        $rs = $supplierModel->save();
        return $rs?$supplierModel:false;
    }

    public static function insertSupplierRemark($supplierId,$remark){
        $remarkModel = new Remark();
        $remarkModel->object_id = $supplierId;
        $remarkModel->object_type = Remark::SUPPLIER;
        $remarkModel->remark = $remark;
        $remarkModel->create_time = time();
        $remarkModel->update_time = time();
        $remarkModel->save();
    }

    public static function insertSupplierType($supplierId, $types)
    {
        foreach ($types as $typeId) {
            $info[] = array(
                'supplier_id' => $supplierId,
                'type_id' => $typeId,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        SupplierType::getDb()
            ->createCommand()
            ->batchInsert(SupplierType::tableName(), $columns, $info)
            ->execute();
    }

    public static function insertSupplierArea($supplierId, $areas)
    {
        foreach ($areas as $areaId) {
            $info[] = array(
                'supplier_id' => $supplierId,
                'area_id' => $areaId,
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        SupplierArea::getDb()
            ->createCommand()
            ->batchInsert(SupplierArea::tableName(), $columns, $info)
            ->execute();
    }

    public static function insertSupplierAccount($supplierId, $types)
    {
        foreach ($types as $type) {
            $info[] = array(
                'supplier_id' => $supplierId,
                'account_name' => $type['name'],
                'bank_name' => $type['bankName'],
                'bank_number' => $type['bankAccount'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        SupplierAccount::getDb()
            ->createCommand()
            ->batchInsert(SupplierAccount::tableName(), $columns, $info)
            ->execute();
    }

    public static function editSupplierInfo($supplierId,$params){
        Supplier::updateAll([
            'contact_name' => $params['contact_name'],
            'province_id' => $params['province_id'],
            'city_id' => $params['city_id'],
            'district_id' => $params['district_id'],
            'address' => $params['address'],
            'supplier_name' => $params['supplier_name'],
            'phone' => $params['phone'],
            'email' => $params['email'],
            'update_time' =>time()
        ], [
            'id' => $supplierId
        ]);
    }

    public static function editSupplierType($supplierId){
        SupplierType::updateAll([
            'update_time'=>time(),
            'relation_status' => UNAVAILABLE
        ], [
            'supplier_id' => $supplierId
        ]);
    }

    public static function editSupplierArea($supplierId){
        SupplierArea::updateAll([
            'update_time'=>time(),
            'relation_status' => UNAVAILABLE
        ], [
            'supplier_id' => $supplierId
        ]);
    }

    public static function editSupplierAccount($supplierId){
        SupplierAccount::updateAll([
            'update_time'=>time(),
            'account_status' => UNAVAILABLE
        ], [
            'supplier_id' => $supplierId
        ]);
    }

    public static function editSupplierRemark($supplierId,$remark){
        Remark::updateAll([
            'update_time'=>time(),
            'remark' => $remark
        ], [
            'object_id' => $supplierId,
            'object_type'=>Remark::SUPPLIER
        ]);
    }

    public static function getSupplierInfo($supplierId){

        $supplierInfo = Supplier::find()
            ->with('supplierType')
            ->with('region')
            ->with('supplierType')
            ->with('supplierAccount')
            ->with('remark')
            ->where(['id'=>$supplierId])
            ->asArray()
            ->one();
        $districtInfo = RegionService::districtInfo($supplierInfo['district_id']);
        $supplierInfo['region_info'] = $districtInfo;
        $supplierInfo['remark'] = !empty($supplierInfo['remark'])?$supplierInfo['remark']:'';
        return $supplierInfo;
    }

    public static function getAreaName($arr){
        $areaResult = Yii::$app->params['area_list'];
        $areaNameArr = [];
        $areaNameStr = '';
        if(!empty($arr)){
            foreach ($arr as $k=>$v){
                foreach ($areaResult as $key=>$value){
                    if($v['area_id'] == $value['id']){
                        $areaNameArr[] = $value['area_name'];
                        break;
                    }
                }
            }
            $areaNameStr = implode('-',$areaNameArr);
        }
        return $areaNameStr;
    }

}