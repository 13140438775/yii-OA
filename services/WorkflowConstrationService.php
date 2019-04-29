<?php

namespace app\services;

use app\models\OpenFlow;
use app\models\OpenProject;
use app\models\OrderEntry;
use app\models\Certificate;
use app\models\Order;
use app\exceptions\OrderException;
use yii\helpers\VarDumper;

class WorkflowConstrationService
{
    /**
     * 侧边栏 - 确认施工队入场
     * @param $request
     * @return bool
     * @CreateTime 2018/4/18 14:17:25
     * @Author: heyafei@likingfit.com
     */
    public static function constrationTeamEnterSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $open_flow = OpenFlow::findOne(['series_id' => $series_id]);
        $open_flow->enter_time = $request['enter_time'];
        return $open_flow->save();
    }

    /**
     * 侧边栏 - 确认骏工报告通过
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:42:03
     * @Author: heyafei@likingfit.com
     */
    public static function projectReportPassSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $open_flow = OpenFlow::findOne(['series_id' => $series_id]);
        $open_flow->completion_time = $request['enter_time'];
        return $open_flow->save();
    }

    /**
     * 侧边栏 - 录入订单期望到货日期
     * @param $request
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/8 18:47:35
     * @Author: heyafei@likingfit.com
     */
    public static function inputOrderArriveInit($request)
    {
        $series_id = $request['flow']['series_id'];
        $order_type = $request['order_type'];
        $query = OrderEntry::find()
            ->select(['order_type', 'order_id', 'receiver_name', 'province_name', 'city_name', 'district_name', 'address', 'receiver_phone', 'actual_amount'])
            ->where(['series_id' => $series_id, 'order_type' => $order_type])
            ->asArray()
            ->one();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        if($query) $query['order_type'] = isset($order_type[$query['order_type']]) ? $order_type[$query['order_type']]['name']: '';
        $query['actual_amount'] = $query['actual_amount'] / 100;
        return $query;
    }

    /**
     * 侧边栏 - 录入订单期望到货日期
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:42:03
     * @Author: heyafei@likingfit.com
     */
    public static function inputOrderArriveSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $order_type = $request['order_type'];
        $open_entry = OrderEntry::find()->where(['series_id' => $series_id, 'order_type' => $order_type])->one();
        $open_entry->expect_arrive_date = strtotime($request['expect_time']);
        $open_entry->order_status = OrderEntry::CONFIRM_DELIVERY;
        return $open_entry->save();
    }

    /**
     * 侧边栏 - 录入订单发货信息
     * @param $request
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/9 09:58:15
     * @Author: heyafei@likingfit.com
     */
    public static function inputOrderDeliverInit($request)
    {
        // 出库订单
        $series_id = $request['flow']['series_id'];
        $order_type = $request['order_type'];
        $select = [
            'order_type' => 't_order_entry.order_type',
            'order_id' => 't_order_entry.order_id',
            'receiver_name' => 't_order_entry.receiver_name',
            'province_name' => 't_order_entry.province_name',
            'city_name' => 't_order_entry.city_name',
            'district_name' => 't_order_entry.district_name',
            'address' => 't_order_entry.address',
            'receiver_phone' => 't_order_entry.receiver_phone',
            'expect_time' => 't_order_entry.expect_arrive_date',
            'purchase_order_id' => 't_order_entry.purchase_order_id'
        ];
        $query = OrderEntry::find()
            ->select($select)
            ->where(['t_order_entry.series_id' => $series_id, 't_order_entry.order_type' => $order_type])
            ->asArray()
            ->one();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        $query['order_type'] = isset($order_type[$query['order_type']]) ? $order_type[$query['order_type']]['name']: '';
        $query['expect_time'] = date('Y-m-d', $query['expect_time']);

        // 出库统计（待写）调黄煜豪的接口
        $store_tongji = WarehouseService::getWarehouseOut($query['purchase_order_id']);
        $store_tongji['order_out'] = $store_tongji['order_out'] ? $store_tongji['order_out']: 0;
        $store_tongji['is_submit'] = $store_tongji['out_status'] == 2 ? true: false;
        if($store_tongji['out_order_list']) {
            $end_time = end($store_tongji['out_order_list']);
            $store_tongji['delivery_date'] = $end_time['out_time'];
            $store_tongji['pre_arrive_date'] = $end_time['arrival_time'];
        }
        foreach ($store_tongji['out_order_list'] as &$_val) {
            $_val['out_time'] = date('Y-m-d', $_val['out_time']);
            $_val['arrival_time'] = date('Y-m-d', $_val['arrival_time']);
        }
        return ['store_order' => $query, 'store_tongji' => $store_tongji];

    }

    /**
     * 侧边栏 - 录入订单发货信息
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:42:03
     * @Author: heyafei@likingfit.com
     */
    public static function inputOrderDeliverSave($request)
    {
        $delivery_date = $request['delivery_date'] ? $request['delivery_date']: '';
        $pre_arrive_date = $request['pre_arrive_date'] ? $request['pre_arrive_date']: '';
        $order_id = $request['order_id'];
        $open_entry = OrderEntry::findOne(['order_id' => $order_id]);
        $open_entry->order_status = OrderEntry::ALREADY_DELIVERY;
        if($delivery_date) $open_entry->delivery_date = $delivery_date;
        if($pre_arrive_date) $open_entry->pre_arrive_date = $pre_arrive_date;
        return $open_entry->save();
    }

    /**
     * 侧边栏 - 确认订单到货
     * @param $request
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/9 14:32:54
     * @Author: heyafei@likingfit.com
     */
    public static function orderArriveSureInit($request)
    {
        /*订单信息*/
        $series_id = $request['flow']['series_id'];
        $order_type = $request['order_type'];
        $select = [
            'order_type' => 't_order_entry.order_type',
            'order_id' => 't_order_entry.order_id',
            'receiver_name' => 't_order_entry.receiver_name',
            'receiver_phone' => 't_order_entry.receiver_phone',
            'province_name' => 't_order_entry.province_name',
            'city_name' => 't_order_entry.city_name',
            'district_name' => 't_order_entry.district_name',
            'address' => 't_order_entry.address',
            'delivery_date' => 't_order_entry.delivery_date',
            'pre_arrive_date' => 't_order_entry.pre_arrive_date',
            'expect_time' => 't_order_entry.expect_arrive_date',
            'purchase_order_id' => 't_order_entry.purchase_order_id'
        ];
        $query = OrderEntry::find()
            ->select($select)
            ->where(['t_order_entry.series_id' => $series_id, 't_order_entry.order_type' => $order_type])
            ->asArray()
            ->one();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        $query['order_type'] = isset($order_type[$query['order_type']]) ? $order_type[$query['order_type']]['name']: '';
        $query['delivery_date'] = date('Y-m-d', $query['delivery_date']);
        $query['pre_arrive_date'] = date('Y-m-d', $query['pre_arrive_date']);
        $query['expect_time'] = date('Y-m-d', $query['expect_time']);

        // 调用煜豪的接口查看-订单共16个商品，已出库：14个商品，待出库：2个商品
        $store_tongji['store_tongji'] = WarehouseService::getWarehouseOut($query['purchase_order_id']);
        $query['order_totle'] = isset($store_tongji['store_tongji']['order_totle']) ? $store_tongji['store_tongji']['order_totle'] : 0;
        $query['order_out'] = isset($store_tongji['store_tongji']['order_out']) ? $store_tongji['store_tongji']['order_out'] : 0;
        $query['order_wait'] = isset($store_tongji['store_tongji']['order_wait']) ? $store_tongji['store_tongji']['order_wait'] : 0;
        return $query;

    }

    /**
     * 侧边栏 - 确认订单到货
     * @param $request
     * @return int
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 2018/4/18 14:18:08
     * @Author: heyafei@likingfit.com
     */
    public static function orderArriveSureSave($request)
    {
        $order_id = $request['order_id'];
        $certificate = $request['certificate'];
        $open_entry = OrderEntry::findOne(['order_id' => $order_id]);
        if (strtotime($request['arrive_date']) < $open_entry->delivery_date) {
            throw new OrderException(OrderException::TIME_ERROR);
        }
        $open_entry->arrive_date = strtotime($request['arrive_date']);
        $open_entry->order_status = OrderEntry::CONFIRM_RECEIVER;
        $open_entry->save();
        // 完成订单的状态
        Order::updateStatus($open_entry->purchase_order_id, ['order_status' => Order::FINNISH_ORDER]);

        $data = [];
        foreach ($certificate as $val) {
            $data[] = [
                'series_id' => $request['flow']['series_id'],
                'object_id' => $order_id,
                'certificate' => $val['url'],
                'file_name' => $val['name'],
                'type' => 3
            ];
        }
        $certificate = new Certificate();
        return $certificate->batchInsert($data);
    }

    /**
     * 侧边栏 - 确认智能设备调试完成
     * @param $request
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/9 15:13:07
     * @Author: heyafei@likingfit.com
     */
    public static function browDeviceFinishInit($request)
    {
        $series_id = $request['flow']['series_id'];
        $order_type = OrderEntry::SMART_ORDER;
        $query = OrderEntry::find()
            ->select(['order_type', 'order_id', 'arrive_date'])
            ->where(['series_id' => $series_id, 'order_type' => $order_type])
            ->asArray()
            ->one();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        $query['order_type'] = isset($order_type[$query['order_type']]) ? $order_type[$query['order_type']]['name']: '';
        $query['arrive_date'] = date('Y-m-d', $query['arrive_date']);
        return $query;
    }

    /**
     * 侧边栏 - 确认智能设备调试完成
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:42:03
     * @Author: heyafei@likingfit.com
     */
    public static function browDeviceFinishSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $open_flow = OpenFlow::findOne(['series_id' => $series_id]);
        $open_flow->device_debug_time = $request['device_debug_time'];
        return $open_flow->save();
    }

    /**
     * 侧边栏 - 提交预售时间及成本
     * @param $request
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/9 15:13:07
     * @Author: heyafei@likingfit.com
     */
    public static function presellTimeSureInit($request)
    {
        $series_id = $request['flow']['series_id'];
        $order_type = OrderEntry::PRESALE_ORDER;
        $query = OrderEntry::find()
            ->select(['order_type', 'order_id', 'arrive_date', 'project_id'])
            ->where(['series_id' => $series_id, 'order_type' => $order_type])
            ->asArray()
            ->one();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        $query['order_type'] = isset($order_type[$query['order_type']]) ? $order_type[$query['order_type']]['name']: '';
        $query['arrive_date'] = date('Y-m-d', $query['arrive_date']);
        return $query;
    }

    /**
     * 侧边栏 - 提交预售时间及成本
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:42:03
     * @Author: heyafei@likingfit.com
     */
    public static function presellTimeSureSave($request)
    {
        $project_id = $request['project_id'];
        $open_project = OpenProject::findOne(['id' => $project_id]);
        list($start_date, $end_date) = $request['date_time'];
        $open_project->start_date = strtotime($start_date);
        $open_project->end_date = strtotime($end_date);
        $open_project->presale_cost = $request['presale_cost'];
        return $open_project->save();
    }
}