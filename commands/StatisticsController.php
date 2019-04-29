<?php
/**
 * Created by PhpStorm.
 * @Author: chenxuux@likingfit.com
 * @CreateTime 2018/3/15 16:38:54
 */

namespace app\commands;

use yii\console\Controller;
use app\services\CustomerService;
use yii\helpers\Console;


class StatisticsController extends Controller
{

    /**
     * 统计客户
     *
     * @param null $date
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/4/4 17:09:51
     * @Author: fangxing@likingfit.com
     * @Author: chenxuxu@likingfit.com
     */
    public function actionCustomer($date = null)
    {

        try{
            $date = $date ?: date("Y-m-d");
            $start = strtotime("-1 day", strtotime($date));
            $end = strtotime($date);
            $rows = CustomerService::statisticsCustomer($start, $end);
            $this->stdout($rows, Console::FG_GREEN);
        }catch (\Exception $e){
            \Yii::error($e->getMessage());
        }

    }
}