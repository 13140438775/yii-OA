<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/15 18:06:05
 */

namespace app\controllers;

use Yii;
use app\services\CustomerService;

class CustomerStatisticsController extends BaseController
{
    const DAY_NUM = 7;

    /**
     * 统计每天客户数量
     *
     * @return array
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/4/3 11:23:22
     * @Author: chenxuxu@likingfit.com
     */
    public function actionCustomer()
    {
        $params = \Yii::$app->request->post();
        $day = isset($params['day']) && $params['day'] ? $params['day'] : self::DAY_NUM;
        $end = strtotime("today");
        $start = strtotime("-$day day");
        $data = CustomerService::isLeader($start, $end);
        return $data;
    }

    /**
     * 每个状态下客户的数量
     * @return mixed
     * @CreateTime 2018/4/3 11:23:50
     * @Author: chenxuxu@likingfit.com
     */
    public function actionNum()
    {
        $num = CustomerService::getTypeCustomer();
        return $num;
    }

}