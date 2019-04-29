<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_order_good".
 *
 * @property int $id
 * @property int $order_id 订单ID
 * @property int $goods_id 物资ID
 * @property int $goods_name 物资ID
 * @property int $good_num 物资数量
 * @property string $create_time 新建时间
 */
class OrderGood extends Base
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_order_good';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['order_id', 'good_id', 'good_num'], 'integer'],
            [['create_time'], 'string', 'max' => 19],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'order_id' => 'Order ID',
            'good_id' => 'Good ID',
            'good_num' => 'Good Num',
            'create_time' => 'Create Time',
        ];
    }
}
