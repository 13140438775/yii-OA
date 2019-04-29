<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/19 16:32:15
 */

namespace app\models;

class OrderAction extends Base
{
    const ORDER_TYPE = [
        1 => '财务',    //财务审核订单
        2 => '财务',    //财务审核退单
        3 => '退货'     //采购审核退单
    ];
    const STATUS_ACTION =[
        1 => '审核',
        2 => '驳回',
        3 => '通过'
    ];
    const ORDER_ACTION = [
        1 => '订单提交',
        2 => '财务驳回',
        3 => '财务通过'
    ];

    public static function tableName()
    {
        return 't_order_action';
    }

    public static function unavailable($relationId, $orderType)
    {
        self::updateAll(['action_status' => UNAVAILABLE, 'update_time' => time()], ['relation_id' => $relationId, 'order_type' => $orderType]);
    }

    public function getOrder()
    {
        return $this->hasOne(Order::className(), ['order_id' => 'order_id']);
    }

    public static function convert2string(&$results, $labels = [])
    {
        $labels["update_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        $labels["create_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        parent::convert2string($results, $labels);
    }


}