<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_address_contract".
 *
 * @property int $id
 * @property string $contract_sn 合同编码
 * @property string $receive_time 签约时间
 * @property string $receive_name 收款人
 * @property string $receive_blank 收款银行
 * @property string $receive_account 收款账号
 * @property int $address_id 地址id
 * @property int $create_time 创建时间
 * @property int $update_time 更新时间
 */
class AddressContract extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_address_contract';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'contract_sn' => 'Contract Sn',
            'receive_time' => 'Receive Time',
            'receive_name' => 'Receive Name',
            'receive_blank' => 'Receive Blank',
            'receive_account' => 'Receive Account',
            'address_id' => 'Address ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public function getRentContractInfoByAddressId($addressId)
    {
        return self::find()
            ->select("rent_contract_sn")
            ->where(['address_id' => $addressId])
            ->one();
    }

    public static function getRentBySeriesId($seriesId)
    {
        return self::find()
            ->select("rent")
            ->where(['series_id' => $seriesId])
            ->one();
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = time();
        }
        $this->update_time = time();
        return true;
    }
}
