<?php
/**
 * Created by PhpStorm.
 * User: wfeng
 * Date: 2017/1/22
 * Time: 14:17
 */
namespace app\controllers;

use app\services\RegionService;
use app\models\Region;
use yii\helpers\VarDumper;

class RegionController extends BaseController {
    /**
     * 地址
     * @CreateTime 2018/3/4 12:59:04
     * @Author: heyafei@likingfit.com
     */
    public function actionProvinceList()
    {
        return RegionService::provinceList();
    }

    /**
     * 地址
     * @CreateTime 2018/3/4 12:59:04
     * @Author: heyafei@likingfit.com
     */
    public function actionCityList()
    {
        $data = \yii::$app->Request->post();
        return RegionService::cityList($data['province_id']);
    }

    /**
     * 地址
     * @CreateTime 2018/3/4 12:59:04
     * @Author: heyafei@likingfit.com
     */
    public function actionDistrictList()
    {
        $data = \yii::$app->Request->post();
        return RegionService::districtList($data['city_id']);
    }
}