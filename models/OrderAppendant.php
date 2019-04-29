<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_order_appendant".
 *
 * @property int $id
 * @property int $flow_id
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $order_id 订单id
 * @property int $appendant 附属数据
 * @property int $appendant_type 1装修费 2拆除面积
 */
class
OrderAppendant extends Base
{
    const DECORATION_AMOUNT = 1;

    const DEMOLITION_AREA = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_order_appendant';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        /*return [
            [['flow_id', 'series_id', 'order_id', 'appendant'], 'integer'],
            [['series_id'], 'required'],
            [['appendant_type'], 'string', 'max' => 1],
        ];*/
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
            'appendant' => 'Appendant',
            'appendant_type' => 'Appendant Type',
        ];
    }
}
