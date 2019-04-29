<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/14 14:40:32
 */

namespace app\services;

use app\models\Address;
use app\models\AddressContract;
use app\models\AddressPresale;
use yii\db\Query;

class AddressService
{
    /**
     * 获取地址详细信息
     * @param $condition
     * @return Address|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/15 10:24:18
     * @Author     : pb@likingfit.com
     */
    public static function get($condition)
    {
        return Address::find()
            ->with(['presale', 'contract' => function ($query) {
                $query->select([
                    "contract_sn",
                    "receive_start_time" => "FROM_UNIXTIME(receive_start_time, \"%Y-%m-%d\")",
                    "receive_end_time"   => "FROM_UNIXTIME(receive_end_time, \"%Y-%m-%d\")",
                    "receive_name",
                    "receive_blank",
                    "receive_account",
                    "address_id"
                ]);
            }])
            ->where($condition)
            ->asArray()
            ->one();
    }

    /**
     * 保存预售处信息
     * @param $data
     * @return bool
     * @CreateTime 2018/3/20 15:33:54
     * @Author     : pb@likingfit.com
     */
    public static function savePresale($data)
    {
        $presale = AddressPresale::find()->where(['address_id' => $data['address_id']])->one();
        if (is_null($presale)) {
            $presale = new AddressPresale();
        }
        $presale->setAttributes($data, false);

        return $presale->save();
    }

    /**
     * 保存合同信息
     * @param $data
     * @return bool
     * @CreateTime 2018/4/23 10:28:27
     * @Author     : pb@likingfit.com
     */
    public static function saveContract($data)
    {
        $contract                   = AddressContract::find()->where(['address_id' => $data['address_id']])->one();
        $data['receive_start_time'] = strtotime($data['receive_start_time']);
        $data['receive_end_time']   = strtotime($data['receive_end_time']);
        $contract->setAttributes($data, false);

        return $contract->save();
    }

    /**
     * 获取指定坐标范围内的健身房
     * @param $longitude
     * @param $latitude
     * @param $radius
     * @param $addressId
     * @return array
     * @CreateTime 2018/4/14 11:22:07
     * @Author     : pb@likingfit.com
     */
    public static function getRoundRange($longitude, $latitude, $radius, $addressId = '')
    {
        $address = (new Query())
            ->from(Address::tableName())
            ->filterWhere(['NOT IN', 'id', $addressId])
            ->select([
                'province_name',
                'city_name',
                'district_name',
                'address',
                "(Power(Abs(longitude - $longitude), 2) + Power(Abs(latitude - $latitude),2)) as distance"
            ])
            ->orderBy('distance');

        $range = new Query();
        $range->from(['address' => $address])
            ->select([
                'province_name',
                'city_name',
                'district_name',
                'address',
            ])
            ->where(['<=', 'distance', $radius * $radius]);

        return $range->all();
    }
}