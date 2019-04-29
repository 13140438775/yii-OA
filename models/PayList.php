<?php

namespace app\models;

use Yii;
use yii\db\ActiveQuery;

/**
 * This is the model class for table "t_pay_list".
 *
 * @property int $id
 * @property int $series_id 流程id
 * @property int $cost_type 1加盟费 2开店订单费用 3装修订单费用 4补差费用
 * @property int $pay_status 支付状态 1:未支付 2:未完全支付 3:完全支付
 * @property int $has_confirm 是否已审核过: 0 未审核, 1, 已审核
 * @property int $project_id
 * @property int $total_amount
 * @property int $coupon_amount
 * @property int $actual_amount
 * @property string $remark
 * @property string $create_time 创建时间
 */
class PayList extends Base
{
    const JOIN_FEE = 1;
    const OPEN_ORDER_FEE = 2;
    const DECORATION_ORDER_FEE = 3;

    const NOT_ARRIVED = 1;
    const PARTIAL_ARRIVAL = 2;
    const ALL_ARRIVED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_pay_list';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'serires_id' => 'Serires ID',
            'cost_type' => 'Cost Type',
            'pay_status' => 'Pay Status',
            'has_confirm' => 'Has Confirm',
            'total_amount' => 'Total Amount',
            'remark' => 'Remark',
            'create_time' => 'Create Time',
        ];
    }

    public function getPayTask()
    {
        return $this->hasMany(PayTask::class, ['pay_list_id' => 'id'])
            ->where(["is_valid" => AVAILABLE])
            ->orderBy("update_time desc")
            ->with(["certificate" => function (ActiveQuery $query) {
                $query->andWhere('is_valid = 1');
            }]);
    }

    public function getProject()
    {
        return $this->hasOne(OpenProject::class, ["id" => "project_id"]);
    }
}
