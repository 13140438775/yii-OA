<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "t_open_rent_contract".
 *
 * @property int $id
 * @property int $flow_id
 * @property int $series_id
 * @property int $address_id 地址表id
 * @property int $pay_money 打款金额
 * @property int $pay_time 打款时间
 * @property int $confirm_status 合同审核状态:  0 驳回,  1, 通过
 * @property int $fee_confirm_status 费用确认状态:  0 驳回,  1, 通过
 * @property int $create_time 创建时间
 * @property int $update_time 更新时间
 */
class OpenRentContract extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_open_rent_contract';
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
            'address_id' => 'Address ID',
            'pay_money' => 'Pay Money',
            'pay_time' => 'Pay Time',
            'confirm_status' => 'Confirm Status',
            'fee_confirm_status' => 'Fee Confirm Status',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public function getCertificates(){
        return $this->hasMany(Certificate::class,['object_id'=>'id']);
    }
}
