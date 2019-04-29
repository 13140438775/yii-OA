<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:33:33
 */
namespace app\models;
class Type extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 't_type';
    }

    /***
     * @return type array
     */
    public static function getTypes(){
        $condition = array(
            'type_status'=> AVAILABLE
        );
        return self::find()->select(['id','type_name'])->where($condition)->asArray()->all();
    }

    public function getSub(){
        return $this->hasMany(Sub::className(),['id'=>'sub_id'])->viaTable(TypeSub::tableName(),['type_id'=>'id'])
            ->select([Sub::tableName().'.id',Sub::tableName().'.sub_name']);
    }

    public function getSupplier(){
        return $this->hasMany(Supplier::className(),['id'=>'supplier_id'])->select(['id','supplier_name'])->where(['supplier_status'=>AVAILABLE])
            ->viaTable(SupplierType::tableName(),['type_id'=>'id'],function ($query){
                $query->andWhere(['relation_status'=>AVAILABLE]);
            });
    }

}