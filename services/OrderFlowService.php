<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/3 13:59:17
 */

namespace app\services;


use app\exceptions\OrderException;
use app\exceptions\PayException;
use app\models\OpenContract;
use app\models\OpenProject;
use app\models\OrderAppendant;
use app\models\OrderEntry;
use app\models\OrderGood;
use app\models\PayCertificate;
use app\models\PayList;
use app\models\PayTask;
use likingfit\Workflow\Workflow;
use yii\db\ActiveQuery;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\Json;

class OrderFlowService
{
    /**
     * 确认平面图时间
     *
     * @param $data
     * @return int
     * @CreateTime 18/4/1 13:26:46
     * @Author: fangxing@likingfit.com
     */
    public static function confirmPlanTime($data)
    {
        GymService::recordOpenFlow($data["flow"]["series_id"], [
            "confirm_floor_plan" => $data["confirm_floor_plan"]
        ]);
        return true;
    }


    /**
     * 选择订单保存
     *
     * @param $data
     * @return bool
     * @throws Exception
     * @throws \Throwable
     * @CreateTime 18/4/11 11:16:24
     * @Author: fangxing@likingfit.com
     */
    public static function pickOrderSave($data)
    {
        $order_types = \Yii::$app->params['order_entry']['order_type'];
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $conditions = ["project_id" => $data["project_id"]];
            list($gymInfo, $customer) = OrderEntryService::checkGymInfo($conditions);

            // 判断是否合营
            if ($gymInfo["open_type"] == OpenProject::CONSORTIUM) {
                $gymInfo["receiver_name"] = $customer["name"];
                $gymInfo["tel"] = $customer["phone"];
            }

            //入库订单数据
            $orders = [];
            OrderEntryService::organizeOrders($orders, $data, $gymInfo);
            (new OrderEntry())->batchInsert($orders);

            //设置流程变量
            $process = Workflow::getProcess($data['flow']['process_id']);
            foreach ($data['order_type'] as $type) {
                $process->setVariable($order_types[$type]['var'], AVAILABLE);
            }
            if (!empty(array_diff($data['order_type'], [OrderEntry::DECORATION_ORDER, OrderEntry::DEMOLITION]))) {
                $process->setVariable("IsOpenOrder", AVAILABLE);
            }
            OpenProject::updateAll(["can_replenishment" => AVAILABLE], ["id" => $gymInfo["id"]]);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 选择订单初始化
     *
     * @return array
     * @CreateTime 18/3/21 16:35:51
     * @Author: fangxing@likingfit.com
     */
    public static function pickOrderInit()
    {
        $order_type = \Yii::$app->params["order_entry"]["order_type"];
        $arr = [];
        foreach ($order_type as $row) {
            $arr[] = [
                "text" => $row["name"],
                "value" => $row["value"],
                "is_required" => @$row["is_required"] ?: 0,
                "default" => $row["default"]
            ];
        }
        return $arr;
    }

    /**
     * 开店订单初始化
     *
     * @param $data
     * @return $this|array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/9 11:29:20
     * @Author: fangxing@likingfit.com
     */
    public static function orderInit($data)
    {
        $additions = [
            'select' => [[
                "franchisee_name",
                "franchisee_phone",
                "series_id"
            ]]
        ];
        $model = new OpenContract($additions);
        $with = [
            'gym' => function (ActiveQuery $query) {
                $query->select([
                    "province_name",
                    "city_name",
                    "district_name",
                    "address",
                ]);
            }
        ];
        $conditions = ["series_id" => $data["flow"]["series_id"]];
        return $model->getOneRecord($conditions, $with);
    }

    public static function commonOrderInit($data)
    {
        $conditions = [
            "order_type" => $data["order_type"],
            "series_id" => $data["flow"]["series_id"]
        ];
        $order = OrderEntry::find()
            ->where($conditions)
            ->select([
                "total_amount",
                "coupon_amount",
                "total_num"
            ])
            ->one();
        $coupon_amount = 0;
        $total_amount = 0;
        $total_num = 0;
        if ($order) {
            $coupon_amount = $order["coupon_amount"];
            $total_amount = $order["total_amount"];
            $total_num = $order["total_num"];
        }
        return [
            "init_data" => [
                "coupon_amount" => $coupon_amount
            ],
            "total_amount" => $total_amount,
            "total_num" => $total_num
        ];
    }

    public static function specialOrderInit($data)
    {
        $conditions = [
            "order_type" => $data["order_type"],
            "series_id" => $data["flow"]["series_id"]
        ];
        $order = OrderEntry::find()
            ->joinWith("orderAppendant", false)
            ->where($conditions)
            ->asArray()
            ->select([
                "total_amount",
                "coupon_amount",
                "total_num",
                "appendant"
            ])
            ->one();
        $coupon_amount = 0;
        $total_amount = 0;
        $total_num = 0;
        $appendant = 0;
        if ($order) {
            $coupon_amount = $order["coupon_amount"];
            $total_amount = $order["total_amount"];
            $total_num = $order["total_num"];
            $appendant = $order["appendant"];
        }
        $logic = [
            "total_amount" => $total_amount,
            "total_num" => $total_num,
            "init_data" => [
                "coupon_amount" => $coupon_amount,
            ]
        ];
        if ($data["order_type"] == OrderEntry::DEMOLITION) {
            $gym = GymService::getGymBySeriesId($data["flow"]["series_id"]);
            $logic["gym_area"] = $gym->gym_area;
            $logic["init_data"]["total_amount"] = $total_amount;
            $logic["init_data"]["area"] = $appendant ?: 0;
            unset($logic["total_amount"]);
        } else {
            $logic["init_data"]["decoration_amount"] = ($appendant ?: 0);
        }
        return $logic;
    }

    /**
     * 录入开店订单
     *
     * @param $data
     * @return bool
     * @throws OrderException
     * @CreateTime 18/3/30 11:44:33
     * @Author: fangxing@likingfit.com
     */
    public static function saveCommonOrder($data)
    {
        $conditions = [
            "order_type" => $data["order_type"],
            "series_id" => $data["flow"]["series_id"]
        ];

        $model = OrderEntry::find()
            ->with("orderGood")
            ->where($conditions)
            ->one();

        if ($model->is_valid == UNAVAILABLE) {
            throw new OrderException(OrderException::ORDER_CLOSE);
        }
        if ($model->confirm_order == OrderEntry::AGREE) {
            throw new OrderException(OrderException::AGREE_ORDER);
        }
        if (empty($model->orderGood)) {
            throw new OrderException(OrderException::NO_ORDER_GOOD);
        }
        $attributes = [
            "actual_amount" => new Expression("[[total_amount]]-:amount", [":amount" => $data["coupon_amount"] * 100]),
            "coupon_amount" => $data["coupon_amount"] * 100,
            "sub_order_type" => OrderEntry::OPEN,
            "order_status" => OrderEntry::SUBMIT_ORDER
        ];
        $model->setAttributes($attributes, false);
        $model->save();
        return true;
    }

    /**
     * 录入装修施工物料订单
     *
     * @param $data
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     * @CreateTime 18/3/12 14:07:00
     * @Author: fangxing@likingfit.com
     */
    public static function saveDecorationOrder($data)
    {
        $conditions = [
            "order_type" => OrderEntry::DECORATION_ORDER,
            "series_id" => $data["flow"]["series_id"]
        ];
        $order = OrderEntry::find()
            ->with("orderGood")
            ->where($conditions)
            ->one();
        if ($order->is_valid == UNAVAILABLE) {
            throw new OrderException(OrderException::ORDER_CLOSE);
        }
        if ($order->confirm_order == OrderEntry::AGREE) {
            throw new OrderException(OrderException::AGREE_ORDER);
        }
        if (empty($order->orderGood)) {
            throw new OrderException(OrderException::NO_ORDER_GOOD);
        }

        $good_amount = OrderGood::find()
            ->select("SUM(price*good_num)")
            ->where([
                "order_id" => $order->order_id,
                "is_valid" => AVAILABLE
            ])
            ->createCommand()
            ->getRawSql();
        $attributes = [
            "total_amount" => new Expression("($good_amount) + " . $data["decoration_amount"] * 100),
            "coupon_amount" => $data["coupon_amount"] * 100,
            "actual_amount" => new Expression("total_amount - " . $data["coupon_amount"] * 100),
            "sub_order_type" => OrderEntry::DECORATION,
            "order_status" => OrderEntry::SUBMIT_ORDER
        ];
        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            OrderEntry::updateAll($attributes, ["order_id" => $order->order_id]);
            $data['order_id'] = $order->order_id;
            $appendData = [
                'appendant' => $data["decoration_amount"] * 100,
                'appendant_type' => OrderAppendant::DECORATION_AMOUNT
            ];
            static::appendantSave($data, $appendData);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 拆除订单
     *
     * @param $data
     * @return bool
     * @throws \Exception
     * @throws \yii\db\Exception
     */
    public static function saveDemolitionOrder($data)
    {
        $order = OrderEntry::findOne([
            "series_id" => $data["flow"]["series_id"],
            'order_type' => OrderEntry::DEMOLITION,
            //"order_status" => OrderEntry::SUBMIT_ORDER
        ]);

        if ($order->is_valid == UNAVAILABLE) {
            throw new OrderException(OrderException::ORDER_CLOSE);
        }
        if ($order->confirm_order == OrderEntry::AGREE) {
            throw new OrderException(OrderException::AGREE_ORDER);
        }
        $order->total_amount = $data["total_amount"] * 100;
        $order->coupon_amount = $data["coupon_amount"] * 100;
        $order->actual_amount = $order->total_amount - $order->coupon_amount;
        $order->sub_order_type = OrderEntry::DECORATION;
        $order->order_status = OrderEntry::CONFIRM_ORDER;
        $order->confirm_order = OrderEntry::AGREE;

        $transaction = \Yii::$app->getDb()->beginTransaction();
        try {
            $order->save(false);
            $data['order_id'] = $order->order_id;

            //新增附属数据
            $appendData = [
                'appendant' => $data["area"],
                'appendant_type' => OrderAppendant::DEMOLITION_AREA
            ];
            static::appendantSave($data, $appendData);

            $gym = OpenProject::findOne($order->project_id);
            if ($gym->open_type == OpenProject::DIRECT) {
                $transaction->commit();
                return true;
            }
            self::makePayList($order, $gym->series_id);
            $transaction->commit();
            return true;
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw $e;
        }
    }

    /**
     * 订单附属数据变化
     *
     * @param $data
     * @param $appendData
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/12 13:57:09
     * @Author: fangxing@likingfit.com
     */
    public static function appendantSave($data, $appendData)
    {
        $appendant = OrderAppendant::findOne(["order_id" => $data["order_id"]]);
        if ($appendant === null) {
            $appendant = new OrderAppendant;
            $appendant->order_id = $data["order_id"];
        }
        $appendant->setAttributes($appendData, false);
        $appendant->save(false);
    }

    /**
     * 检测订单是否提交
     *
     * @param $orderEntry
     * @throws OrderException
     * @CreateTime 18/3/14 14:25:43
     * @Author: fangxing@likingfit.com
     */
    public static function checkOrder($orderEntry)
    {
        if ($orderEntry === null) {
            throw new OrderException(OrderException::NO_ORDER);
        } elseif ($orderEntry->is_valid == UNAVAILABLE) {
            throw new OrderException(OrderException::ORDER_CLOSE);
        }
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
        $workItem = WorkItemService::getWorkItemById($data["flow"]["work_item_id"]);
        $conditions = [
            "order_type" => $data["order_type"],
            "series_id" => $workItem->series_id,
            "order_status" => OrderEntry::SUBMIT_ORDER,
            "is_valid" => AVAILABLE
        ];
        $orderEntryModel = new OrderEntry([
            "select" => [[
                "total_num",
                OrderEntry::tableName() . ".order_id",
                "total_amount",
                "coupon_amount",
                "actual_amount",
                "project_id"
            ]]
        ]);
        $join = ["orderGood"];
        $orderEntry = $orderEntryModel->getOneRecord($conditions, $join);
        return $orderEntry;
    }

    /**
     * 确认订单
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/13 16:25:59
     * @Author: fangxing@likingfit.com
     */
    public static function confirmOrderSave($data)
    {
        return \Yii::$app->db->transaction(function ($db) use ($data) {

            $orderEntryModel = OrderEntry::findOne($data["order_id"]);

            //检测订单
            static::checkOrder($orderEntryModel);

            if ($orderEntryModel->confirm_order == OrderEntry::AGREE) {
                throw new OrderException(OrderException::AGREE_ORDER);
            }

            $typeInfo = \Yii::$app->params["order_entry"]["order_type"];
            $orderEntryModel->confirm_order = $data["confirm_order"];
            $res = $data["confirm_order"] == OrderEntry::AGREE;

            $order_type = $orderEntryModel->order_type;

            //记录日志
            FlowService::recordOpenLog($data["flow"]["work_item_id"], [
                "user_data" => Json::encode([
                    "res" => $res ? "通过" : "驳回",
                    "order_label" => $typeInfo[$order_type]["name"]
                ]),
                "work_name_format" => "{res}{order_label}订单"
            ]);

            //设置循环变量
            $loopVar = $typeInfo[$order_type]["confirm-var"];
            FlowService::setVariable($data["flow"]["work_item_id"], [$loopVar => (int)$res]);

            if (!$res) {
                $orderEntryModel->save();
                return true;
            }

            $gym = OpenProject::findOne($orderEntryModel->project_id);
            if ($gym->open_type == OpenProject::DIRECT) {
                $orderEntryModel->order_status = OrderEntry::CONFIRM_DELIVERY;
                $orderEntryModel->save();
                static::makePurchaseOrder($gym->id, $orderEntryModel->order_type);
                return true;
            }
            $orderEntryModel->order_status = OrderEntry::CONFIRM_ORDER;
            $orderEntryModel->save();
            self::makePayList($orderEntryModel, $gym->series_id);
            return true;
        });
    }

    /**
     * 检测合营是否生成付款单
     *
     * @param $orderModel
     * @param $mainSeriesId
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/13 10:16:39
     * @Author: fangxing@likingfit.com
     */
    public static function makePayList($orderModel, $mainSeriesId)
    {

        $conditions = [
            "project_id" => $orderModel->project_id,
            "sub_order_type" => $orderModel->sub_order_type,
            "is_valid" => AVAILABLE
        ];

        $orderEntry = OrderEntry::find()->where($conditions)->all();
        $total_amount = 0;
        $coupon_amount = 0;
        $actual_amount = 0;
        $confirm_all = true;
        /**
         * @var $order OrderEntry
         */
        /* 检测合营全部订单是否确认完毕 */
        $ids = [];
        foreach ($orderEntry as $order) {
            $confirm_all = $confirm_all && ($order->confirm_order >= OrderEntry::AGREE); //如果 == 会影响到补单
            $total_amount += $order->total_amount;
            $coupon_amount += $order->coupon_amount;
            $actual_amount += $order->actual_amount;
            $ids[] = $order->id;
        }

        $cost_type = PayList::OPEN_ORDER_FEE;
        if ($orderModel->sub_order_type == OrderEntry::DECORATION) {
            $cost_type = PayList::DECORATION_ORDER_FEE;
        }
        if ($confirm_all) {
            $payList = new PayList;
            $payList->series_id = $mainSeriesId;
            $payList->total_amount = $total_amount;
            $payList->coupon_amount = $coupon_amount;
            $payList->actual_amount = $actual_amount;
            $payList->cost_type = $cost_type;
            $payList->create_time = time();
            $payList->project_id = $orderModel->project_id;
            $payList->save(false);
            OrderEntry::updateAll(["pay_list_id" => $payList->id], ["id" => $ids]);
        }
    }

    /**
     * 录入订单款项初始化
     *
     * @param $data
     * @return array
     * @CreateTime 18/3/14 13:49:11
     * @Author: fangxing@likingfit.com
     */
    public static function getOrderSummary($data)
    {
        $payInfo = PayListService::getPayListInfo([
            "project_id" => $data["project_id"],
            "cost_type" => $data["cost_type"],
            "is_valid" => AVAILABLE,
            "pay_status" => [PayList::NOT_ARRIVED, PayList::PARTIAL_ARRIVAL],
            "series_id" => $data["flow"]["series_id"]
        ]);

        $conditions = [
            "order_status" => [OrderEntry::CONFIRM_ORDER, OrderEntry::SUBMIT_PAYMENT],
            "pay_list_id" => $payInfo->id,
            "is_valid" => AVAILABLE
        ];
        $count = OrderEntry::find()->where($conditions)->count();

        $payTask = array_group($payInfo->payTask, "payfee_status", function ($row) {
            return [
                "pay_account" => $row["pay_account"],
                "pay_person" => $row["pay_person"],
                "pay_amount" => $row["pay_amount"],
                "pay_time" => date("Y-m-d H:i", $row["pay_time"]),
                "payfee_status" => $row["payfee_status"],
                "receive_account" => $row["receive_account"],
                "pay_task_id" => $row["id"],
                "certificate" => array_map(function ($row) {
                    return [
                        'name' => $row['file_name'],
                        'url' => $row['certificate']
                    ];
                }, $row["certificate"])
            ];
        });
        return [
            "pay_list_id" => $payInfo->id,
            "total_amount" => $payInfo->total_amount,
            "coupon_amount" => $payInfo->coupon_amount,
            "actual_amount" => $payInfo->actual_amount,
            "order_num" => $count,
            "pay_task" => @$payTask[1] ?: [],
            "init_data" => ["payInfo" => @$payTask[0] ?: []]
        ];
    }

    /**
     * 录入订单款项
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/15 11:52:21
     * @Author: fangxing@likingfit.com
     */
    public static function entryPaySave($data)
    {
        return \Yii::$app->db->transaction(function ($db) use ($data) {
            $payCertificates = [];
            $payTaskIds = [];
            $payTaskQuery = PayTask::find()->where([
                "payfee_status" => UNAVAILABLE,
                'pay_list_id' => $data["pay_list_id"]
            ])->select("id");

            //删除之前未通过的pay_task和凭证
            PayTask::updateAll(["is_valid" => UNAVAILABLE], [
                "payfee_status" => UNAVAILABLE,
                'pay_list_id' => $data["pay_list_id"]
            ]);
            PayCertificate::updateAll(["is_valid" => UNAVAILABLE], ["pay_task_id" => $payTaskQuery]);

            foreach ($data["payInfo"] as $v) {
                $certificates = $v["certificate"];
                unset($v["certificate"]);
                $v["pay_list_id"] = $data["pay_list_id"];
                $v["pay_time"] = strtotime($v["pay_time"]);
                $v["pay_amount"] = $v["pay_amount"] * 100;
                if (isset($v["pay_task_id"])) {
                    unset($v["pay_task_id"]);
                }
                $payTask = new PayTask;
                $payTask->setAttributes($v, false);
                $payTask->save();
                $payTaskIds[] = $payTask->id;
                foreach ($certificates as $certificate) {
                    $payCertificates[] = [
                        "pay_task_id" => $payTask->id,
                        "certificate" => $certificate["url"],
                        "file_name" => $certificate["name"],
                    ];
                }
            }

            (new PayCertificate)->batchInsert($payCertificates);
            OrderEntry::updateAll(["order_status" => OrderEntry::SUBMIT_PAYMENT], [
                "pay_list_id" => $data["pay_list_id"],
                "is_valid" => AVAILABLE,
                "order_status" => [OrderEntry::CONFIRM_ORDER, OrderEntry::SUBMIT_PAYMENT]
            ]);
            return true;
        });
    }

    /**
     * 确认订单款项初始化
     *
     * @param $data
     * @return array
     */
    public static function confirmPayInit($data)
    {
        $payInfo = PayListService::getPayListInfo([
            "project_id" => $data["project_id"],
            "cost_type" => $data["cost_type"],
            "is_valid" => AVAILABLE,
            "series_id" => $data["flow"]["series_id"],
            "pay_status" => [PayList::NOT_ARRIVED, PayList::PARTIAL_ARRIVAL]
        ]);
        $alReadyPay = 0;
        $payTask = array_map(function ($row) use (&$alReadyPay) {
            if ($row["payfee_status"] == AVAILABLE) {
                $alReadyPay += $row["pay_amount"];
            }
            return [
                "pay_task_id" => $row["id"],
                "pay_account" => $row["pay_account"],
                "pay_person" => $row["pay_person"],
                "pay_amount" => $row["pay_amount"],
                "pay_time" => date("Y-m-d H:i", $row["pay_time"]),
                "payfee_status" => $row["payfee_status"],
                "receive_account" => $row["receive_account"],
                "certificate" => array_map(function ($row) {
                    return [
                        "name" => $row["file_name"],
                        "url" => IMAGE_DOMAIN . "/" . $row["certificate"]
                    ];
                }, $row["certificate"])
            ];
        }, $payInfo->payTask);
        return [
            "pay_list_id" => $payInfo->id,
            "total_amount" => $payInfo->total_amount,
            "coupon_amount" => $payInfo->coupon_amount,
            "already_pay" => $alReadyPay,
            "pay_task" => $payTask
        ];
    }

    /**
     * 确认订单款项
     *
     * @param $data
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     */
    public static function confirmPaySave($data)
    {
        return \Yii::$app->db->transaction(function ($db) use ($data) {
            if (!empty($data["agree"])) {
                PayTask::updateAll(["payfee_status" => AVAILABLE], ["id" => $data["agree"], "payfee_status" => UNAVAILABLE]);
            }
            $payList = PayList::findOne([
                "and",
                ["id" => $data["pay_list_id"]],
                ["<>", "pay_status", PayList::ALL_ARRIVED]
            ]);
            $variables = [];
            $payList->pay_status = $data["pay_status"];

            $res = $data["pay_status"] == PayList::ALL_ARRIVED;

            //记录日志
            $payStatus = \Yii::$app->params["pay_status"];
            $costType = \Yii::$app->params["cost_type"];

            FlowService::recordOpenLog($data["flow"]["work_item_id"], [
                "user_data" => Json::encode([
                    "res" => $res ? "通过" : "驳回",
                    "pay_status" => $payStatus[$data["pay_status"]],
                    "cost_label" => $costType[$payList->cost_type]
                ]),
                "work_name_format" => "{res}{cost_label}款项（{pay_status}）"
            ]);

            if ($payList->save() && $res) {
                OrderEntry::updateAll(["order_status" => OrderEntry::CONFIRM_PAYMENT], [
                    "pay_list_id" => $payList->id,
                    "order_status" => OrderEntry::SUBMIT_PAYMENT,
                    "is_valid" => AVAILABLE
                ]);
                static::makePurchaseOrder($payList->project_id);

                $key = "IsMainDeviceOrderAmount";
                if ($data["cost_type"] == PayList::DECORATION_ORDER_FEE) {
                    $key = "IsMainFinishOrderAmount";
                }
                $variables[$key] = AVAILABLE;
            }
            FlowService::setVariable($data["flow"]["work_item_id"], $variables);
            return true;
        });
    }

    /**
     * 将订单写入一站式采购
     *
     * @param $project_id
     * @param null $orderType
     * @throws OrderException
     * @CreateTime 2018/6/19 11:52:22
     * @Author: fangxing@likingfit.com
     */
    public static function makePurchaseOrder($project_id, $orderType=null)
    {
        $mainTable = OrderEntry::tableName();
        $select = [
            "total_amount",
            "actual_amount",
            "contact_name" => "receiver_name",
            "contact_phone" => "receiver_phone",
            $mainTable . ".province_id",
            $mainTable . ".province_name",
            $mainTable . ".city_id",
            $mainTable . ".city_name",
            $mainTable . ".district_id",
            $mainTable . ".district_name",
            "contact_address" => $mainTable . ".address",
            "gym_id" => "project_id",
            $mainTable . ".order_id",
            "order_type"
        ];
        $conditions = [
            "project_id" => $project_id,
            $mainTable.".is_valid" => AVAILABLE,
            "purchase_order_id" => "",
            "order_type" => $orderType
        ];

        $join = ["orderGood" => function (ActiveQuery $query) {
            $query->select([
                "order_id",
                "goods_id",
                "goods_name",
                "goods_num" => "good_num",
                "type_id",
                "goods_amount" => new Expression("price * good_num / 100")
            ]);
        }];

        $orders = OrderEntry::find()
            ->filterWhere($conditions)
            ->joinWith($join)
            ->select($select)
            ->asArray()
            ->all();
        $orderTypes = \Yii::$app->params["order_entry"]["order_type"];
        foreach ($orders as $order) {
            $goods = $order["orderGood"];
            $orderId = $order["order_id"];
            unset($order["orderGood"], $order["order_id"]);
            $order["goods"] = $goods;
            $order["total_amount"] = $order["total_amount"]/100;
            $order["actual_amount"] = $order["actual_amount"]/100;
            $order["order_name"] = $orderTypes[$order["order_type"]]["name"];
            $order["order_type"] = CustomerOrderService::OA_ORDER;
            $purchaseOrderId = CustomerOrderService::saveOrder($order);
            OrderEntry::updateAll(["purchase_order_id" => $purchaseOrderId], ["order_id" => $orderId]);
        }
    }
}