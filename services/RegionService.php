<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/3/1
 * Time: 下午1:23
 */
namespace app\services;
use app\models\Region;

class RegionService
{
    /**
     * @CreateTime 2018/3/4 16:44:06
     * @Author: heyafei@likingfit.com
     */
    public static function provinceList(){
        return Region::find()->select(['province_id', 'province_name'])->filterWhere(['region_type' => 1])->asArray()->all();
    }

    /**
     * @param $province_id
     * @return array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/4 16:56:22
     * @Author: heyafei@likingfit.com
     */
    public static function cityList($province_id){
        return Region::find()->select(['city_id', 'city_name'])->filterWhere(['region_type' => 2, 'province_id' => $province_id])->asArray()->all();
    }

    /**
     * @param $city_id
     * @return array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/4 16:59:29
     * @Author: heyafei@likingfit.com
     */
    public static function districtList($city_id){
        return Region::find()->select(['district_id', 'district_name'])->filterWhere(['region_type' => 3, 'city_id' => $city_id])->asArray()->all();
    }


    /**
     * 省的信息
     */
    public static function provinceInfo($province_id)
    {
        return Region::find()->where(['province_id' => $province_id])->asArray()->one();
    }

    /**
     * 城市信息
     */
    public static function cityInfo($city_id)
    {
        return Region::find()->where(['city_id' => $city_id])->asArray()->one();
    }

    /**
     * 区信息
     */
    public static function districtInfo($district_id)
    {
        return Region::find()->where(['district_id' => $district_id])->asArray()->one();
    }

    /**
     * 组所负责的城市
     */
    public static function departmentCity($city_name)
    {
        return Region::find()->select(['city_id', 'city_name'])->where(['like', 'city_name', $city_name])->distinct(TRUE)->asArray()->all();
    }
}