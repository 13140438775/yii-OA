<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_decoration".
 *
 * @property int $id
 * @property int $flow_id
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $order_id 订单id
 * @property int $decorate_amount 装修费用
 * @property int $pay_amount 实际支付金额
 * @property string $gym_area 场馆面积
 * @property string $actual_area 实际面积
 * @property int $decorate_type 1 装修 2拆除
 * @property int $create_time
 */
class Decoration extends Base
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_decoration';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flow_id' => 'Flow ID',
            'series_id' => 'Series ID',
            'order_id' => 'Order ID',
            'decorate_amount' => 'Decorate Amount',
            'pay_amount' => 'Pay Amount',
            'gym_area' => 'Gym Area',
            'actual_area' => 'Actual Area',
            'decorate_type' => 'Decorate Type',
            'create_time' => 'Create Time',
        ];
    }
}
