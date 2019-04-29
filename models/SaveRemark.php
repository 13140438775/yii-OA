<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/4/26 15:10:08
 */

namespace app\models;



class SaveRemark extends Base{

    const ORDER_SAVE = 1;  //订单保存
    const CHARGE_SAVE = 2; //退单保存
    const FIN_PASS = 3; //财务审核通过

    public static function tableName()
    {
        return 't_save_remark';
    }

    public  function saveRemark($relationId,$remark,$action){
        $attributes = array(
            'relation_id' => $relationId,
            'remark' => $remark,
            'create_time' => time(),
            'action' => $action,
            'operator_id' =>\Yii::$app->user->getIdentity()->id,
            'operator_name' =>\Yii::$app->user->getIdentity()->name,
        );
        $this->setAttributes($attributes,false);
        $this->save();
    }

    public static function getRemark($relationId,$action){
        $result = self::find()->where(['relation_id'=>$relationId,'action' =>$action])->asArray()->all();
        $labels["create_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        parent::convert2string($result, $labels);
        return $result;
    }


}