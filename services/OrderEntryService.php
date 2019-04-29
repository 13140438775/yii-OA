<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/2 16:27:04
 */

namespace app\services;


use app\exceptions\Gym;
use app\exceptions\OrderException;
use app\helpers\Helper;
use app\models\Base;
use app\models\Department;
use app\models\Flow;
use app\models\Goods;
use app\models\GymSeries;
use app\models\Message;
use app\models\OpenProject;
use app\models\OrderAppendant;
use app\models\OrderEntry;
use app\models\OrderGood;
use app\exceptions\Goods as GoodException;
use app\models\PayList;
use app\models\ProjectDirector;
use app\models\Staff;
use app\models\WorkItem;
use likingfit\Workflow\Base\Activity;
use likingfit\Workflow\Base\Process;
use likingfit\Workflow\Workflow;
use yii\db\ActiveQuery;
use yii\db\Connection;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class OrderEntryService
{
    /**
     * @param $queryData
     * @param $pagination
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/9 11:19:46
     * @Author: fangxing@likingfit.com
     */
    public static function getList($queryData, $pagination)
    {
        $tableName = OrderEntry::tableName();
        $projectTable = OpenProject::tableName();
        $conditions = [
            'and',
            [
                $tableName . '.order_id' => $queryData['order_id'],
                $tableName . '.order_type' => $queryData['order_type'],
                $tableName . '.order_status' => $queryData['order_status'],
            ],
            ['like', 'gym_name', $queryData['gym_name']],
            ['<>', $tableName . '.order_status', UNAVAILABLE]
        ];
        $additions = [
            'select' => [[
                $tableName . '.order_id',
                $tableName . '.total_amount',
                $tableName . '.order_status',
                $tableName . '.order_type',
                $tableName . '.create_time',
                'gym_name',
                'open_type',
                $projectTable . '.province_name',
                $projectTable . '.city_name',
                $tableName . ".is_valid",
                "cg_name" => Staff::tableName(). ".name"
            ]],
            'orderBy' => [$tableName . ".id desc"]
        ];
        $join = [["gym", "staff"], false];
        $OrderEntry = new OrderEntry($additions);
        $results = $OrderEntry->paginate($pagination['page'], $pagination['pageNum'], $join, $conditions);
        OrderEntry::convert2string($results["rows"], [
            "open_type" => "gym.open_type.name",
            "total_amount" => function ($val) {
                return round($val / 100, 2);
            }
        ]);
        return $results;
    }

    /**
     * 获取订单信息
     *
     * @param $order_id
     * @return $this|array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/18 12:11:48
     * @Author: fangxing@likingfit.com
     */
    public static function getOrderInfo($order_id)
    {
        $additions = [
            "select" => [[
                "order_id",
                "total_amount",
                "coupon_amount",
                "actual_amount",
                "order_status",
                "order_type",
                "project_id",
                "series_id",
                "purchase_staff",
                "receiver_name",
                "receiver_phone"
            ]]
        ];
        $order = new OrderEntry($additions);
        $join = [
            "gym" => function (ActiveQuery $query) {
                $query->select([
                        "series_id",
                        "gym_name",
                        "open_type",
                        "province_name",
                        "city_name",
                        "district_name",
                        "address"
                    ]);
            },
            "orderAppendant" => function (ActiveQuery $query) {
                $query->where(["appendant_type" => OrderAppendant::DECORATION_AMOUNT])
                    ->select(["appendant", "order_id"]);
            },
            "staff" => function (ActiveQuery $query) {
                $query->select("name");
            },
        ];
        $results = [$order->getOneRecord(["order_id" => $order_id], $join)];
        Base::convert2string($results, [
            "order_type" => "order_entry.order_type.name"
        ]);
        $orderInfo = reset($results);

        //获取各阶段负责人
        $orderFlowDepartment = \Yii::$app->params["orderFlowDepartment"];
        $performers = ProjectDirector::find()
            ->where(["series_id" => $orderInfo["series_id"]])
            ->select(["staff_name", "series_id", "department_id", "staff_id"])
            ->indexBy("staff_id")
            ->asArray()
            ->all();
        $orderInfo["can_cancel"] = (GymService::isMyChargeGym($performers) && $orderInfo["order_status"] < OrderEntry::ALREADY_DELIVERY) ? AVAILABLE : UNAVAILABLE;
        $performers = ArrayHelper::index($performers, "department_id");
        foreach ($orderFlowDepartment as $department) {
            $key = $department["key"];
            $orderInfo[$key] = $performers[$department["id"]]["staff_name"];
        }

        //进度条
        $orderInfo["progress"] = \Yii::$app->params["order_entry"]["order_status"];
        if($orderInfo['gym']['open_type'] == OpenProject::DIRECT) {
            unset($orderInfo["progress"][3]);
            unset($orderInfo["progress"][4]);
        }
        return $orderInfo;
    }

    /**
     * 获取订单商品列表
     *
     * @param $data
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/10 11:39:30
     * @Author: fangxing@likingfit.com
     */
    public static function getOrderGoods($data)
    {
        $model = new OrderGood;
        $data["conditions"]["is_valid"] = AVAILABLE;
        return $model->paginate($data["page"], $data["pageNum"], [], $data["conditions"]);
    }

    /**
     * 检测健身房能否补单
     *
     * @param $data
     * @return OpenProject|OrderEntryService|array|null
     * @throws Gym
     * @throws OrderException
     * @CreateTime 18/3/22 16:05:31
     * @Author: fangxing@likingfit.com
     */
    public static function checkQualifications($data)
    {
        list($gym, $customer) = static::checkGymInfo(["project_id" => $data["project_id"]]);
        if ($gym['can_replenishment'] == UNAVAILABLE) {
            throw new Gym(Gym::COULD_NOT_REPLENISHMENT);
        }
        if (in_array($data["order_type"], [OrderEntry::PRESALE_ORDER, OrderEntry::DEMOLITION])
            && OrderEntry::findOne([
                "order_type" => $data["order_type"],
                "project_id" => $gym['id'],
                "is_valid" => AVAILABLE
            ])) {
            throw new OrderException(OrderException::ALREADY_PRESALE);
        }
        return [$gym, $customer];
    }

    /**
     * 补单
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/21 16:16:32
     * @Author: fangxing@likingfit.com
     */
    public static function startReplenishmentFlow($data)
    {
        return \Yii::$app->db->transaction(function ($db) use ($data) {
            /**
             * 开启补货流程
             * @var $flow Flow
             * @var $process Process
             * @var $db Connection
             */
            list($gym, $customer) = static::checkQualifications($data);
            $creator = \Yii::$app->user->getId();
            $var = ArrayHelper::getValue(\Yii::$app->params, "order_entry.order_type.{$data["order_type"]}.var");
            list($flow, $process) = FlowService::startProcess("Relenishment", $creator, [
                $var => AVAILABLE,
                "open_type" => $gym["open_type"]
            ]);

            //继承主流程专员信息
            $directors = ProjectDirector::findAll(["series_id" => $gym["series_id"]]);
            foreach ($directors as $director) {
                unset($director->id);
                $director->series_id = $flow->series_id;
                $director->flow_id = $flow->id;
                $director->create_time = time();
                $director->setIsNewRecord(true);
                $director->save();
            }

            //关联补货流程
            $model = new GymSeries;
            $model->setAttributes([
                "series_id" => $flow->series_id,
                "project_id" => $data["project_id"],
                "customer_id" => $customer ? $customer["id"] : 0,
                "series_type" => GymSeries::APPEND
            ], false);

            // 判断是否合营
            if ($gym["open_type"] == OpenProject::CONSORTIUM) {
                $gym["receiver_name"] = $customer["name"];
                $gym["tel"] = $customer["phone"];
            }

            $data["flow"] = [
                "flow_id" => $flow->id,
                "series_id" => $flow->series_id
            ];
            $data["is_replenishment"] = AVAILABLE;
            $orderType = $data["order_type"];
            $data["order_type"] = [$orderType];

            //入库订单数据
            $orders = [];
            OrderEntryService::organizeOrders($orders, $data, $gym);
            (new OrderEntry())->batchInsert($orders);
            $model->save(false);

            //开启流程
            $process->start();

            //记录日志
            $workItem = FlowService::getWorkItemBySeriesId($flow->series_id);
            $workItem->remark = $data["remark"];
            FlowService::completeWorkItem2($workItem);

            return true;
        });
    }

    /**
     * 检查健身房信息
     *
     * @param $conditions
     * @return $this|array|null|OpenProject
     * @throws Gym
     * @CreateTime 18/3/12 13:59:27
     * @Author: fangxing@likingfit.com
     */
    public static function checkGymInfo($conditions)
    {
        $gym = GymSeries::find()
            ->where($conditions)
            ->with(["openProject" => function (ActiveQuery $query) {
                $query->andWhere(["gym_status" => OpenProject::WILL_OPEN]);
            }, "customer"])
            ->asArray()
            ->one();
        if ($gym["openProject"] == null) {
            throw new Gym(Gym::NOT_FOUND);
        }
        return [$gym["openProject"], $gym["customer"]];
    }

    /**
     * 录入开店订单
     *
     * @param $data
     * @throws GoodException
     * @throws Gym
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 18/3/12 15:42:27
     * @Author: fangxing@likingfit.com
     */
    public static function saveOrderByProjectId($data)
    {
        $conditions = ["project_id" => $data["project_id"]];
        list($gymInfo, $customer) = static::checkGymInfo($conditions);

        $gymInfo["receiver_name"] = $gymInfo["gym_name"];
        $gymInfo["receiver_phone"] = "";
        // 判断是否合营
        if ($gymInfo["open_type"] == OpenProject::CONSORTIUM) {

            $gymInfo["receiver_name"] = $customer["name"];
            $gymInfo["receiver_phone"] = $customer["phone"];

        }

        $workItem = WorkItemService::getWorkItemById($data["work_item_id"]);
        $data["flow"]["series_id"] = $workItem["series_id"];
        $data["flow"]["flow_id"] = $workItem["flow_id"];

        $order = OrderEntry::findOne([
            "series_id" => $workItem->series_id,
            'order_type' => $data["order_type"]
        ]);

        OrderFlowService::checkOrder($order);

        if ($order->order_status > OrderEntry::SUBMIT_ORDER) {
            throw new OrderException(OrderException::AGREE_ORDER);
        }

        OrderEntryService::saveOrder($data, $gymInfo, $order);
    }

    /**
     * 保存订单
     *
     * @param $data
     * @param $gymInfo
     * @param $order OrderEntry
     * @throws GoodException
     * @throws \Exception
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 18/3/12 14:50:56
     * @Author: fangxing@likingfit.com
     */
    public static function saveOrder($data, $gymInfo, $order)
    {
        //组合订单商品数据
        $goods_info = static::organizeOrderDetail($data, $order);
        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            if ($order->order_status == OrderEntry::SUBMIT_ORDER) {
                //修正订单数据
                static::affectOrder($order);
                OrderGood::updateAll(["is_valid" => UNAVAILABLE], ["order_id" => $order->order_id]);
            } else {
                $order->order_status = OrderEntry::SUBMIT_ORDER;
            }
            $orderGoodModel = new OrderGood;
            $orderGoodModel->batchInsert($goods_info);
            $order->order_status = OrderEntry::SUBMIT_ORDER;
            $order->save(false);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * @param $order OrderEntry
     * @param $data
     * @param $gymInfo
     * @throws \Exception
     * @CreateTime 18/3/12 14:40:31
     * @Author: fangxing@likingfit.com
     */
    public static function setOrderCommonAttributes($data, $gymInfo, $order)
    {
        if ($order->isNewRecord) {
            //整理订单头
            $commonData = [
                "order_id" => generateIntId(),
                "flow_id" => $data["flow"]["flow_id"],
                "series_id" => $data["flow"]["series_id"],
                "order_type" => $data['order_type'],
                "is_replenishment" => $data['is_replenishment'],
                "project_id" => $gymInfo['id'],
                'order_status' => OrderEntry::SUBMIT_ORDER,
                'province_id' => $gymInfo['province_id'],
                'province_name' => $gymInfo['province_name'],
                'city_id' => $gymInfo['city_id'],
                'city_name' => $gymInfo['city_name'],
                'district_id' => $gymInfo['district_id'],
                'district_name' => $gymInfo['district_name'],
                'address' => $gymInfo['address'],
                'receiver_name' => $gymInfo['receiver_name'],
                'receiver_phone' => $gymInfo['receiver_phone']
            ];
            $order->setAttributes($commonData, false);
        }
    }

    /**
     * 组织订单数据
     *
     * @param array $data
     * @param array $param
     * @param $gymInfo
     * @throws \Exception
     * @CreateTime 18/4/12 12:36:15
     * @Author: fangxing@likingfit.com
     */
    public static function organizeOrders(array &$data, $param, $gymInfo)
    {
        $time = time();
        $staffId = \Yii::$app->user->getId();
        $order_entry = \Yii::$app->params["order_entry"];
        foreach ($param["order_type"] as $type) {
            $sub_type = OrderEntry::OPEN;
            if (in_array($type, [OrderEntry::DECORATION_ORDER, OrderEntry::DEMOLITION])) {
                $sub_type = OrderEntry::DECORATION;
            }
            array_push($data, [
                "order_id" => generateIntOrderId(),
                "flow_id" => $param["flow"]["flow_id"],
                "series_id" => $param["flow"]["series_id"],
                "order_type" => $type,
                "sub_order_type" => $sub_type,
                "is_replenishment" => $param['is_replenishment'],
                "project_id" => $gymInfo['id'],
                'order_status' => UNAVAILABLE,
                'province_id' => $gymInfo['province_id'],
                'province_name' => $gymInfo['province_name'],
                'city_id' => $gymInfo['city_id'],
                'city_name' => $gymInfo['city_name'],
                'district_id' => $gymInfo['district_id'],
                'district_name' => $gymInfo['district_name'],
                'address' => $gymInfo['address'],
                'receiver_name' => $gymInfo['receiver_name'],
                'receiver_phone' => $gymInfo['tel'],
                'create_time' => $time,
                'update_time' => $time,
                'opeator' => $staffId,
                "purchase_staff" => $order_entry["order_type"][$type]["cg"]
            ]);
        }
    }

    /**
     * 生成订单详情
     *
     * @param $data
     * @param $order
     * @return array
     * @throws GoodException
     * @throws OrderException
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/12 20:08:12
     * @Author: fangxing@likingfit.com
     */
    public static function organizeOrderDetail($data, $order)
    {
        //获取商品信息
        $goods_id = array_column($data['goods'], "goods_id");
        $goodModel = new Goods([
            "select" => [
                ["price", 'model', 'weight', 'param', 'goods_id', 'goods_name', 'type_id']
            ]
        ]);
        $goodModel->getList(['goods_id' => $goods_id], [], true);
        $goodsInfo = $goodModel->getQuery()->indexBy("goods_id")->all();
        if (empty($goodsInfo)) {
            throw new GoodException(GoodException::GOOD_NOT_FOUND);
        }

        //整理订单详情
        $total_amount = 0;
        $orderGoods = [];
        $total_num = 0;
        foreach ($data['goods'] as $good) {
            if ($good["good_num"] <= 0) {
                throw new OrderException(OrderException::CONTAINER_ZERO);
            }
            $good_id = $good["goods_id"];
            if (!isset($goodsInfo[$good_id])) {
                throw new GoodException(GoodException::GOOD_NOT_FOUND);
            }
            $goodInfo = $goodsInfo[$good_id];
            array_push($orderGoods, [
                'order_id' => $order->order_id,
                'goods_id' => $good_id,
                'good_num' => $good["good_num"],
                'goods_name' => $goodInfo["goods_name"],
                'price' => $goodInfo["price"],
                "model" => $goodInfo["model"],
                "weight" => $goodInfo["weight"],
                "param" => $goodInfo["param"],
                "type_id" => $goodInfo["type_id"]
            ]);
            $total_amount += $good["good_num"] * $goodInfo["price"];
            $total_num += $good["good_num"];
        }
        if (empty($orderGoods)) {
            throw new OrderException(OrderException::NO_ORDER_GOOD);
        }
        $order->total_amount = $total_amount;
        $order->actual_amount = $total_amount - $order->coupon_amount;
        $order->total_num = $total_num;
        return $orderGoods;
    }

    /**
     * 修改订单总金额和实际金额
     * 因为装修物料变化会和装修费用一起引起订单金额变化
     *
     * @param $order
     * @CreateTime 18/3/12 13:53:17
     * @Author: fangxing@likingfit.com
     */
    public static function affectOrder($order)
    {
        if ($order->order_type == OrderEntry::DECORATION_ORDER) {
            $appendant = OrderAppendant::find()
                ->select("appendant")
                ->where([
                    "order_id" => $order->order_id,
                    "appendant_type" => OrderAppendant::DECORATION_AMOUNT
                ])
                ->createCommand()
                ->getRawSql();
            $order->total_amount = new Expression("IFNULL(($appendant), 0) + {$order->total_amount}");
            $order->actual_amount = new Expression("total_amount - {$order->coupon_amount}");
        }
    }

    /**
     * 根据子类型获取订单列表
     *
     * @param $data
     * @return $this|array|\yii\db\ActiveRecord[]
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/14 17:22:54
     * @Author: fangxing@likingfit.com
     */
    public static function getOrderBySubType($data)
    {
        $additions = [
            "select" => [[
                'order_type',
                OrderEntry::tableName() . ".order_id",
                "total_num",
                "total_amount",
                "coupon_amount",
                "actual_amount",
                "is_replenishment",
                "appendant",
                "sub_order_type"
            ]],
            "orderBy" => ["order_type asc"]
        ];
        $conditions = [
            "and",
            [
                "pay_list_id" => $data["pay_list_id"],
                "order_status" => [OrderEntry::CONFIRM_ORDER, OrderEntry::SUBMIT_PAYMENT],
            ]
        ];
        $join = ["orderAppendant", false];
        $OrderEntry = new OrderEntry($additions);
        $results = $OrderEntry->getList($conditions, $join);
        OrderEntry::convert2string($results);

        return array_group($results, "order_type");
    }

    /**
     * 订单详情列表
     *
     * @param $data
     * @return $this|array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/13 14:18:35
     * @Author: fangxing@likingfit.com
     */
    public static function getOrderInfoByOrderType($data)
    {
        $data["flow"] = [
            "work_item_id" => $data["work_item_id"]
        ];
        unset($data["work_item_id"]);
        return OrderFlowService::getOrderInfoByOrderType($data);
    }

    /**
     * 营业准备为侧边栏的点击查看订单详情
     *
     * @param $project_id
     * @return array
     * @CreateTime 18/4/18 12:13:27
     * @Author: fangxing@likingfit.com
     */
    public static function orderList($project_id)
    {
        $select = [
            'order_type',
            'order_id',
            'is_replenishment',
            'total_num',
            'total_amount',
            'coupon_amount',
            'actual_amount'
        ];
        $res = ['total_amount' => 0, 'coupon_amount' => 0, 'actual_amount' => 0];
        $query = OrderEntry::find()
            ->select($select)
            ->where(['project_id' => $project_id, 'confirm_order' => AVAILABLE])
            ->orderBy('create_time')
            ->asArray()
            ->all();
        $order_type = \Yii::$app->params['order_entry']['order_type'];
        foreach ($query as $k => &$val) {
            $val['total_amount'] = $val['total_amount'] / 100;
            $val['coupon_amount'] = $val['coupon_amount'] / 100;
            $val['actual_amount'] = $val['actual_amount'] / 100;

            $res['total_amount'] += $val['total_amount'];
            $res['coupon_amount'] += $val['coupon_amount'];
            $res['actual_amount'] += $val['actual_amount'];
            $val['is_replenishment'] = $val['is_replenishment'] ? '（补单）' : '';
            $val['order_type'] = isset($order_type[$val['order_type']]) ? $order_type[$val['order_type']]['name'] : '';
            $val['order_type'] .= $val['is_replenishment'];
        }
        return ['order_list' => $query, 'order_amount' => $res];
    }

    /**
     * 关闭订单
     *
     * @param $order_id
     * @throws \Throwable
     * @CreateTime 18/4/11 15:54:28
     * @Author: fangxing@likingfit.com
     */
    public static function close($order_id)
    {
        \Yii::$app->db->transaction(function ($db) use ($order_id) {
            $order = OrderEntry::find()
                ->with("gym")
                ->where(["order_id" => $order_id])
                ->asArray()
                ->one();
            if ($order["order_status"] > OrderEntry::CONFIRM_DELIVERY) {
                throw new OrderException(OrderException::CLOSE_EXCEPTION);
            }

            $staffs = GymService::getRelationStaffs($order["gym"]["series_id"]);

            if(!GymService::isMyChargeGym($staffs)){
                throw new OrderException(OrderException::REJECT_CLOSE);
            }

            OrderEntry::updateAll(["is_valid" => UNAVAILABLE], ["order_id" => $order_id]);

            $cost_type = PayList::OPEN_ORDER_FEE;
            if ($order["sub_order_type"] == OrderEntry::DECORATION) {
                $cost_type = PayList::DECORATION_ORDER_FEE;
            }

            $flow = FlowService::getFlow($order["series_id"]);
            $persistenceService = Workflow::getPersistenceService();
            $process = Workflow::getProcess($flow->process_id);
            $net = $process->getNet();
            $workItemsQuery = WorkItem::find()
                ->joinWith("rightSide", false)
                ->where([
                    "series_id" => $order["series_id"],
                    "state" => WorkItem::CLAIMED
                ])
                ->andWhere(["like", "user_data", "{\"order_type\":\"{$order["order_type"]}\"}"]);

            if($order["gym"]["open_type"] == OpenProject::CONSORTIUM){
                $workItemsQuery->andWhere(["like", "user_data", "{\"cost_type\":\"{$cost_type}\"}"]);
            }

            $workItems = $workItemsQuery->all();
            if(!empty($workItems)){
                $activityIds = array_column($workItems, "activity_id");
                $tokens = $persistenceService::getProcessToken($flow->process_id, $activityIds);
                foreach ($tokens as $token){
                    $nodeId = $token->getNodeId();
                    $element  = $net->getElement($nodeId);
                    if($element instanceof Activity){
                        if(in_array($nodeId, [
                                "Main.Activity13",
                                "Main.Activity13",
                                "Main.Activity22",
                                "Main.Activity23",
                                "Relenishment.Activity35",
                                "Relenishment.Activity47",
                                "Relenishment.Activity38",
                                "Relenishment.Activity48"])
                            && $order["is_replenishment"] != AVAILABLE
                        ){
                            $process->setVariable(\Yii::$app->params["order_entry"]["order_type"][$order["order_type"]]["var"], UNAVAILABLE);
                        }else{
                            $token->setAlive(0);
                            $persistenceService->saveToken($token);
                            $workItem = array_shift($workItems);
                            $process->completeWorkItem($workItem->work_item_id);
                            $workItem->state = WorkItem::CANCELED;
                            $workItem->save();
                        }
                    }
                }
            }
            $order_type_label = ArrayHelper::getValue(\Yii::$app->params, ["order_entry", "order_type", $order["order_type"], "name"]);
            //消息通知
            $messages = [];
            $phones = [];
            foreach ($staffs as $staff) {
                if(!in_array($staff["department_id"], [Department::PROJECT, Department::FINANCIAL, Department::PURCHASE])) {
                    continue;
                }
                $message = [
                    'staff_id' => $staff['staff_id'],
                    'title' => "取消订单",
                    'content' => $order["gym"]["gym_name"] . "的" . $order_type_label . "订单已取消",
                    'message_type' => Message::ORDER
                ];
                MessageService::push($message);
                array_push($messages, $message);
                array_push($phones, $staff["phone"]);
            }
            $model = new Message;
            $model->batchInsert($messages);

            //短信通知
            Helper::sendSms($phones, SMS_CANCEL_ORDER, [$order["gym"]["gym_name"], $order_type_label]);
        });
    }
}