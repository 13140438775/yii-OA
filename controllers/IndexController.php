<?php

namespace app\controllers;


use app\services\GymService;
use app\services\IndexService;

class IndexController extends BaseController
{

    /**
     * 健身房图表统计
     *
     * @return array
     * @CreateTime 18/4/9 10:44:57
     * @Author: fangxing@likingfit.com
     */
    public function actionGymChart()
    {
        $open_type = \Yii::$app->request->post("open_type");
        return GymService::gymStatistics($open_type);
    }

    /**
     * 费用图
     *
     * @return array
     * @CreateTime 18/4/9 11:15:07
     * @Author: fangxing@likingfit.com
     */
    public function actionFeeChart()
    {
        $params = \Yii::$app->request->post();
        $day = isset($params['day']) && $params['day'] ? $params['day'] : 30;
        $end = strtotime("today");
        $start = strtotime("-$day day");
        return IndexService::feeChart($start, $end);
    }

    public function actionFeeStatistic()
    {
        return IndexService::feeStatistic();
    }

    /**
     * 获取待办事项
     *
     * @return $this|array|\yii\db\ActiveRecord[]
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/17 16:41:34
     * @Author: fangxing@likingfit.com
     */
    public function actionGetWork()
    {
        return IndexService::getUserWorkItem();
    }

    public function actionLogout()
    {
        \Yii::$app->user->logout();

    }

    public function actionCollect()
    {
        $workItemId = \Yii::$app->request->post("work_item_id");
        IndexService::collect($workItemId);
    }
}
