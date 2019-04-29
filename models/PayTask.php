<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_pay_task".
 *
 * @property int $id
 * @property int $pay_list_id 付款列表ID
 * @property string $pay_account 付款账号
 * @property string $pay_person 付款人
 * @property string $pay_amount 付款金额
 * @property string $pay_time 付款时间
 * @property string $pay_bank 付款银行
 * @property int $receive_id 收款账号配置ID
 * @property string $receive_account 收款账号
 * @property string $receive_person 收款人
 * @property string $receive_bank 收款银行
 * @property string $invoice_title 发票抬头
 * @property string $receive_title 收据抬头
 * @property string $description 付款说明
 * @property int $create_time 创建时间
 * @property int $update_time
 */
class PayTask extends Base
{
    //  确认到账
    public static $payFeeStatusType = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_pay_task';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_list_id' => 'Pay List ID',
            'pay_account' => 'Pay Account',
            'pay_person' => 'Pay Person',
            'pay_amount' => 'Pay Amount',
            'pay_time' => 'Pay Time',
            'pay_bank' => 'Pay Bank',
            'receive_id' => 'Receive ID',
            'receive_account' => 'Receive Account',
            'receive_person' => 'Receive Person',
            'receive_bank' => 'Receive Bank',
            'invoice_title' => 'Invoice Title',
            'receive_title' => 'Receive Title',
            'description' => 'Description',
            'create_time' => 'Create Time',
        ];
    }

    public function beforeSave($insert)
    {
        $time = time();
        if($insert){
            $this->create_time = $time;
        }
        $this->update_time = $time;
        return parent::beforeSave($insert);
    }

    public function getCertificate(){
        return $this->hasMany(PayCertificate::class,['pay_task_id'=>'id']);
    }
}
