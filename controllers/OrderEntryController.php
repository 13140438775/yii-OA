<?php

namespace app\controllers;

use app\services\OrderEntryService;

class OrderEntryController extends BaseController
{
    /**
     * 订单列表页初始化数据
     *
     * @return array
     * @CreateTime 18/3/2 15:42:09
     * @Author: fangxing@likingfit.com
     */
    public function actionIndex()
    {
        return [
            "order_type" => array_map(function ($row){
                return [
                    "text" => $row["name"],
                    "value" => $row["value"]
                ];
            }, \Yii::$app->params["order_entry"]['order_type']),
            "order_status" => \Yii::$app->params["order_entry"]['order_status']
        ];
    }

    /**
     * 订单查询
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSearch()
    {
        $pagination = \Yii::$app->request->post("pagination");
        $queryData = \Yii::$app->request->post("conditions");
        return OrderEntryService::getList($queryData, $pagination);
    }

    /**
     * 获取订单明细
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/10 12:02:12
     * @Author: fangxing@likingfit.com
     */
    public function actionGetOrderGoods()
    {
        $queryData = \Yii::$app->request->post();
        return OrderEntryService::getOrderGoods($queryData);
    }

    /**
     * 录入补货订单
     *
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/21 16:17:07
     * @Author: fangxing@likingfit.com
     */
    public function actionReplenishmentSave()
    {
        $data = \Yii::$app->request->post();
        OrderEntryService::startReplenishmentFlow($data);
    }

    /**
     * 侧边栏录入订单
     *
     * @throws \Exception
     * @throws \Throwable
     * @throws \app\exceptions\Gym
     * @throws \yii\base\InvalidConfigException
     */
    public function actionSave()
    {
        $data = \Yii::$app->request->post();
        OrderEntryService::saveOrderByProjectId($data);
    }

    /**
     * 订单商品列表
     *
     * @return $this|array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     */
    public function actionGetDetailList()
    {
        $data = \Yii::$app->request->post();
        return OrderEntryService::getOrderInfoByOrderType($data);
    }

    /**
     * 订单子分类列表
     *
     * @return $this|array|\yii\db\ActiveRecord[]
     * @throws \yii\base\InvalidConfigException
     */
    public function actionListSub()
    {
        $data = \Yii::$app->request->post();
        return OrderEntryService::getOrderBySubType($data);
    }

    /**
     * 订单详情
     *
     * @CreateTime 18/3/3 13:18:03
     * @Author: fangxing@likingfit.com
     */
    public function actionInfo()
    {
        $order_id = \Yii::$app->request->post("order_id");
        return OrderEntryService::getOrderInfo($order_id);
    }

    /**
     * @return mixed
     * @CreateTime 2018/3/14 18:28:56
     * @Author: heyafei@likingfit.com
     */
    public function actionOrderList()
    {
        $project_id = \Yii::$app->request->post("project_id");
        return OrderEntryService::orderList($project_id);
    }

    /**
     * 关闭订单
     *
     * @throws \Throwable
     * @CreateTime 18/4/11 15:55:04
     * @Author: fangxing@likingfit.com
     */
    public function actionClose()
    {
        $order_id = \Yii::$app->request->post("order_id");
        OrderEntryService::close($order_id);
    }

}
