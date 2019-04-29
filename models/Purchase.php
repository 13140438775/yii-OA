<?php

namespace app\models;

use Yii;

class Purchase extends Base
{

    const INITIAL_STATUS = 1;
    const CONFIRM_PURCHASE = 2;
    const WAREHOUSE_LOADING = 3;
    const WAREHOUSE_COMPLETE = 4;
    const CLOSE_PURCHASE = 5;

    const INITIAL_STATUS_NAME = '初始状态';
    const CONFIRM_PURCHASE_NAME = '确认到货';
    const WAREHOUSE_LOADING_NAME = '入库中';
    const WAREHOUSE_COMPLETE_NAME = '已入库';
    const CLOSE_PURCHASE_NAME = '已关闭';

    const CONFIRM_USE_PURCHASE = 1;
    const ADJUST_USE_PURCHASE = 2;

    const PURCHASE_SELF_ADD= 1;
    const PURCHASE_OTHER_ADD= 2;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_purchase';
    }

    public static function getDb() {
        return Yii::$app->getDb();
    }

    public static function getPurchase(){
        return self::find()->orderBy('create_time')->one();
    }

    public static function getPurchaseInfo($purchaseId){
        return self::find()
            ->where([
                'purchase_id'=>$purchaseId
            ])
            ->orderBy('create_time')
            ->one();
    }

    public function getPurchaseDetail(){
        return $this->hasMany(PurchaseDetail::className(),['purchase_id' => 'purchase_id'])
            ->select(['purchase_id','purchase_num','arrival_num','goods_id','actual_amount','warehouse_id'])
            ->where(['relation_status'=>AVAILABLE]);
    }

    public function getSupplier(){
        return $this->hasOne(Supplier::className(), ['id' => 'supplier_id'])
            ->select(['id as supplier_id','supplier_name','phone','contact_name']);
    }

    public function getWarehouse(){
        return $this->hasOne(Warehouse::className(), ['warehouse_id' => 'warehouse_id'])
            ->select(['warehouse_id','warehouse_name','address']);
    }

    public function getRemark(){
        return $this->hasMany(Remark::className(), ['object_id' => 'purchase_id'])
            ->select(['object_id','id as remark_id','remark','create_time'])
            ->orderBy('create_time DESC');
    }

    public function getGym(){
        return $this->hasOne(OpenProject::className(), ['id' => 'gym_id'])
            ->select(['id','gym_name','address','create_time']);
    }
}