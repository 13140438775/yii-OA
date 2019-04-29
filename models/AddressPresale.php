<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_address_presale".
 *
 * @property int $id
 * @property int $province_id 省份ID
 * @property string $province_name 省份名字
 * @property int $city_id 城市ID
 * @property string $city_name 城市名字
 * @property int $district_id 地区ID
 * @property string $district_name 地区名称
 * @property string $address 详细地址
 * @property int $use_area 使用面积
 * @property int $address_id 地址id
 * @property int $create_time 创建时间
 * @property int $update_time 更新时间
 */
class AddressPresale extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_address_presale';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'province_id' => 'Province ID',
            'province_name' => 'Province Name',
            'city_id' => 'City ID',
            'city_name' => 'City Name',
            'district_id' => 'District ID',
            'district_name' => 'District Name',
            'address' => 'Address',
            'use_area' => 'Use Area',
            'address_id' => 'Address ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function getAddressPresaleInfoByAddressId($addressId)
    {
        return self::find()
            ->select("province_name,city_name,district_name,address,use_area")
            ->where(['address_id' => $addressId])
            ->asArray()
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
