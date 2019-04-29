<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : screamwolf@likingfit.com
 * @CreateTime 2018/3/6 14:55:45
 */

namespace app\services;

use app\exceptions\CustomerException;
use app\exceptions\Gym;
use app\helpers\Helper;
use app\models\Address;
use app\models\Base;
use app\models\Customer;
use app\models\Department;
use app\models\GymSeries;
use app\models\Message;
use app\models\OpenLog;
use app\models\OpenProject;
use app\models\OpenContract;
use app\models\OpenFlow;
use app\models\Flow;
use app\models\OrderEntry;
use app\models\ProjectDirector;
use app\models\RightSideConfig;
use app\models\Roles;
use app\models\WorkItem;
use likingfit\Workflow\Base\Task;
use likingfit\Workflow\Workflow;
use yii\db\ActiveQuery;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;
use app\exceptions\Address as AddressException;

class GymService
{

    const DIRECT_OPEN_TYPE = 2;

    const JOIN_OPEN_TYPE = 1;

    public static function save($params)
    {
        if (!empty($params['id'])) {
            $model = OpenProject::findOne(['id' => $params['id']]);
        } else {
            $model = new OpenProject();
        }

        $model->setAttributes($params, false);
        $model->save();
        return $model;
    }

    /**
     * 健身房详情
     *
     * @param $projectId
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/9 14:42:32
     * @Author: fangxing@likingfit.com
     * @Author: chenuxu@likingfit.com
     */
    public static function detail($projectId)
    {
        $additions = [
            'select' => [[
                'id',
                'series_id',
                'province_name',
                'city_name',
                'district_name',
                'address',
                'gym_name',
                'open_type',
                'start_date',
                'end_date',
                'presale_cost',
                'gym_status',
                "open_cost",
                "order_cost",
                "create_time",
                "update_time",
                "remark",
                "address_id",
                "tel",
                "receiver_name"
            ]]
        ];
        $with = [
            'openContract' => function (ActiveQuery $query) {
                $query->select(['franchisee_name', 'franchisee_phone', "series_id"]);
            },
            'openFlow' => function (ActiveQuery $query) {
                $query->select(['create_time', "series_id", "expect_open_time", "open_time", "purchase"]);
            },
            "chargeList" => function (ActiveQuery $query) {
                $query->select(['total_amount', "series_id", "amount_type", "remark"]);
            }
        ];
        $openProject = new OpenProject($additions);
        $gymInfo = [$openProject->getOneRecord(['id' => $projectId], $with)];

        $_time = time();
        $series_id = $gymInfo[0]['openFlow']['series_id'];
        $work_item = WorkItem::find()->where(['series_id' => $series_id, 'activity_id' => [GymSeries::OPEN_DIRECT, GymSeries::OPEN_MAIN]])->one();
        if(isset($work_item['complete_time']) && $work_item['complete_time']){
            $_time = strtotime($work_item['complete_time']);
        } elseif ($gymInfo[0]['gym_status'] == GymSeries::CLOSED){
            $_time = $gymInfo[0]['update_time'];
        }
        //格式化数据
        OpenProject::convert2string($gymInfo, [
            "gym_status" => "gym.gym_status",
            "create_time" => function($val){
                return date("Y-m-d H:i:s", $val);
            },
            "openFlow.create_time" => function($val) use($_time) {
                return ceil(abs($_time - strtotime(date('Y-m-d', $val))) / 86400);
            }
        ]);
        $gymInfo = reset($gymInfo);
        Base::convert2string($gymInfo["chargeList"], ["amount_type" => "amount_type"]);
        $gymInfo["progress"] = ArrayHelper::getValue(\Yii::$app->params, ["gym", "open_type", $gymInfo["open_type"], "purchase"]);

        //填充地址&预售信息
        $addressInfo = AddressService::get(["id" => $gymInfo["address_id"]]);
        $gymInfo["addressInfo"] = [
            "use_area" => $addressInfo["use_area"],
            "contract_start" =>  $addressInfo["contract"]["receive_start_time"],
            "contract_end" =>  $addressInfo["contract"]["receive_end_time"],
            "rent" => $addressInfo["rent"]
        ];
        $gymInfo["is_presale"] = $addressInfo["is_presale"];
        if ($gymInfo["is_presale"] == AVAILABLE) {
            $gymInfo["presale"] = [
                "province_name" => $addressInfo["presale"]["province_name"],
                "city_name" => $addressInfo["presale"]["city_name"],
                "district_name" => $addressInfo["presale"]["district_name"],
                "address" => $addressInfo["presale"]["address"],
                "use_area" => $addressInfo["presale"]["use_area"]
            ];
        }

        //获取负责人
        $staffs = self::getRelationStaffs($gymInfo["series_id"]);

        //判断当前登录人是否是负责此健身房的项目专员
        $gymInfo["can_cancel"] = (self::isMyChargeGym($staffs) && $gymInfo["gym_status"] == OpenProject::WILL_OPEN) ? AVAILABLE : UNAVAILABLE;

        $departmentMapStaffs = [];
        foreach ($staffs as $staff){
            $top = Department::getTopDepartment($staff["department_id"]);
            $departmentMapStaffs[$top->id][] = $staff;
        }
        foreach (\Yii::$app->params["flowDepartment"] as $department) {
            $key = $department["key"];
            if (array_key_exists($department["id"], $departmentMapStaffs)) {
                $gymInfo[$key] = array_column($departmentMapStaffs[$department["id"]], "staff_name");
            }else {
                $gymInfo[$key] = $department["default"];
            }
        }
        return $gymInfo;
    }

    /**
     * 是否有资格关闭健身房
     *
     * @param $staffs
     * @return int
     * @CreateTime 18/4/26 14:19:09
     * @Author: fangxing@likingfit.com
     */
    public static function isMyChargeGym($staffs)
    {
        $userId = \Yii::$app->user->getId();
        $role = key(\Yii::$app->authManager->getRolesByUser($userId));
        if(((array_key_exists($userId, $staffs) || $role == Roles::PROJECT_MANAGER) && $staffs[$userId]["department_id"] == Department::PROJECT)
            || $role == Roles::PROJECT_MANAGER){
            return AVAILABLE;
        }
        return UNAVAILABLE;
    }

    /**
     * @param 获取时间天数和分钟
     * @return mixed
     * @CreateTime 2018/3/15 14:56:28
     * @Author: chenxuxu@likingfit.com
     */
    public static function getTimeNum($time)
    {
        $tmp = time() - $time;
        $data['day'] = floor($tmp / 86400);
        $data['hour'] = floor($tmp % 86400 / 3600);
        $data['minute'] = floor($tmp % 86400 / 60);
        return $data;
    }


    /**
     * 健身房列表
     *
     * @param $params
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/9 00:24:18
     * @Author: chenxuxu@likingfit.com
     * @Author: fangxing@likingfit.com
     */
    public static function search($params)
    {

        $tableName = OpenProject::tableName();
        $openFlowTable = OpenFlow::tableName();

        $gymPlace = isset($params['gym_place']) ? $params['gym_place'] : "";
        $gymStatus = isset($params['gym_status']) ? $params['gym_status'] : "";
        $openStatus = isset($params['open_status']) ? $params['open_status'] : "";

        $conditions = [
            "and",
            [
                "or",
                ['like', $tableName . '.gym_name', $gymPlace],
                ['like', $tableName . '.province_name', $gymPlace],
                ['like', $tableName . '.city_name', $gymPlace],
                ['like', $tableName . '.district_name', $gymPlace],
                ['like', $tableName . '.address', $gymPlace],
            ],
            [$openFlowTable . ".purchase" => $gymStatus],
            [$tableName . ".gym_status" => $openStatus]
        ];

        $additions = [
            'select' => [[
                $tableName . '.id',
                $tableName . '.series_id',
                $tableName . '.province_name',
                $tableName . '.city_name',
                $tableName . '.district_name',
                $tableName . '.gym_name',
                "gym_status",
                $tableName . '.open_type',
                $tableName . '.gym_status',
                $tableName . '.receiver_name AS franchisee_name',
                $tableName . '.tel AS franchisee_phone',
                $tableName . '.update_time',
                $openFlowTable . '.purchase',
                'continue_time' => "{$openFlowTable}.create_time"
            ]],
            "orderBy" => [$tableName.".create_time desc"]
        ];
        $join = [['openContract', 'openFlow'], false];
        $OpenProject = new OpenProject($additions);
        $results = $OpenProject->paginate($params['page'], $params['pageNum'], $join, $conditions);

        $time = time();
        foreach($results['rows'] as &$val) {
            $work_item = WorkItem::find()
                ->where([
                    'series_id' => $val['series_id'],
                    'activity_id' => [GymSeries::OPEN_DIRECT, GymSeries::OPEN_MAIN]])
                ->one();
            $val['complete_time'] = $time;
            if(isset($work_item['complete_time']) && $work_item['complete_time']){
                $val['complete_time'] = strtotime($work_item['complete_time']);
            } elseif ($val['gym_status'] == GymSeries::CLOSED){
                $val['complete_time'] = $val['update_time'];
            }
            $val['continue_time_label'] = ceil(($val['complete_time'] - strtotime(date('Y-m-d', $val['continue_time']))) / 86400);
        }

        OpenProject::convert2string($results["rows"], [
            "purchase" => "gym.open_type.purchase.label"
        ]);
        $gym_status = \Yii::$app->params['gym']['gym_status'];
        foreach($results["rows"] as &$val) {
            $val['gym_status'] = $gym_status[$val['gym_status']];
        }
        return $results;

    }

    /**
     * 健身房选择列表
     *
     * @param $gym_name
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/11 17:19:03
     * @Author: fangxing@likingfit.com
     */
    public static function getGymListForAutoComplete($gym_name)
    {
        $gyms = self::search([
            "gym_name" => $gym_name,
            "page" => 1,
            "pageNum" => 9999
        ]);
        return array_map(function ($val){
            return [
                "project_id" => $val["id"],
                "gym_name" => $val["gym_name"]
            ];
        }, $gyms["rows"]);
    }

    /**
     * 获取当前工作流程
     * @return $this|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/19 11:30:06
     * @Author: chenxuxu@likingfit.com
     */
    public static function getNowWorkName($seriesId)
    {
        $table = WorkItem::tableName();
        $additions = [
            "select" => [[
                "create_time" => $table . ".create_time",
                "display_name",
            ]],
            "orderBy" => [$table . ".complete_time desc"]
        ];
        $join = ["rightSide", false];
        $conditions = [
            "series_id" => $seriesId
        ];
        $model = new WorkItem($additions);
        return $model->getList($conditions, $join);
    }

    /**
     * 取消开店
     *
     * @param $params
     * @throws \Throwable
     * @CreateTime 18/4/9 22:34:42
     * @Author: fangxing@likingfit.com
     */
    public static function closeGym($params)
    {
        \Yii::$app->db->transaction(function ($db) use ($params) {

            $openProjectId = $params['id'];
            $reason = $params['reason'];
            $openProject = OpenProject::findOne($openProjectId);

            $staffs = self::getRelationStaffs($openProject->series_id);
            if(!self::isMyChargeGym($staffs)){
                throw new Gym(Gym::NO_ACCESS);
            }

            if($openProject->gym_status != OpenProject::WILL_OPEN){
                throw new Gym(Gym::CLOSE_ERR);
            }

            $openProject->gym_status = 3;
            $openProject->remark = $reason;
            $openProject->save();

            //停止流程
            FlowService::closeProcessByFlowId($openProject->series_id);

            //取消当前任务项
            WorkItem::updateAll(["state" => WorkItem::CANCELED], [
                "state" => WorkItem::CLAIMED,
                "series_id" => $openProject->series_id
            ]);

            //将订单置为无效
            OrderEntry::updateAll(["is_valid" => UNAVAILABLE], [
                "and",
                ["project_id" => $openProject->id, "is_valid" => AVAILABLE],
                ["<=", "order_status", OrderEntry::CONFIRM_DELIVERY]
            ]);

            //释放地址
            Address::updateAll(["occupancy" => AVAILABLE], ["id" => $openProject->address_id]);

            //记录日志
            FlowService::recordOpenLog(UNAVAILABLE, [
                "series_id" => $openProject->series_id,
                "remark" => $openProject->remark,
                "work_name_format" => $openProject->gym_name . "已取消开店",
            ]);

            //通知该流程下所有负责人
            $messages = [];
            foreach ($staffs as $staff) {
                $message = [
                    'staff_id' => $staff['staff_id'],
                    'title' => "取消开店",
                    'content' => $openProject->gym_name . '已取消开店, 请知悉',
                    'message_type' => Message::GYM
                ];
                MessageService::push($message);
                array_push($messages, $message);
            }
            $model = new Message;
            $model->batchInsert($messages);

            //短信通知
            Helper::sendSms(array_column($staffs, "phone"),
                SMS_CANCEL_GYM,
                [$openProject->gym_name]);
        });
    }

    public static function getRelationStaffs($mainSeriesId)
    {
        $directors = ProjectDirector::getStaffInfoBySeriesId($mainSeriesId);
        $staffs = WorkItem::find()
            ->joinWith("staff", false)
            ->where(["series_id" => $mainSeriesId, "staff_status" => AVAILABLE])
            ->andWhere(["<>", "staff_id", UNAVAILABLE])
            ->groupBy("staff_id")
            ->select(["staff_id", "staff_name" => "name", "department_id", "phone", "email"])
            ->indexBy("staff_id")
            ->asArray()
            ->all();
        return array_merge2($directors, $staffs);
    }

    /**
     * 获取开店日志
     *
     * @param array $condition
     * @return array
     * @CreateTime 18/4/4 14:07:48
     * @Author: fangxing@likingfit.com
     */
    public static function getOpenLog($condition)
    {
        $series_ids = GymSeries::find()
            ->where($condition)
            ->select("series_id");
        $res = OpenLog::find()
            ->where(["series_id" => $series_ids])
            ->orderBy("create_time desc")
            ->asArray()
            ->all();
        $i18n = \Yii::$app->getI18n();
        return array_group($res, "date", function ($row) use ($i18n) {
            $row["work_name"] = $i18n->format($row["work_name_format"], Json::decode($row["user_data"]), \Yii::$app->language);
            $row["date"] = date("Y-m-d", $row["create_time"]);
            $row["time"] = date("H:i", $row["create_time"]);
            unset($row["work_name_format"], $row["user_data"]);
            return $row;
        });
    }

    /**
     * 开店流程图
     *
     * @param $param
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/4 15:07:32
     * @Author: fangxing@likingfit.com
     */
    public static function getGraph($param)
    {
        $flow = Flow::findOne($param['flow_id']);
        $process = Workflow::getProcess($flow->process_id);
        $persistService = Workflow::getPersistenceService();
        $graph = $process->graph();
        list($nodes, $edges) = $graph->toArray();

        //获取节点配置
        $activityCfg = RightSideConfig::find()
            ->where(["activity_id" => array_column($nodes, "node_id")])
            ->indexBy("activity_id")
            ->asArray()
            ->all();

        //填入具体业务信息
        $param = [
            'process_id' => $flow->process_id,
            'state' => [WorkItem::CLAIMED, WorkItem::COMPLETED]
        ];
        $workItem = new WorkItem([
            "orderBy" => ["create_time desc"],
            "indexBy" => ["activity_id"]
        ]);
        $workItemList = $workItem->getList($param);
        $userId = \Yii::$app->user->id;

        //$filterNodes = [];
        //$inValidNodeIds = [];
        foreach ($nodes as &$node) {
            if ($node['type'] == Task::SUBFLOW) {
                $subProcess = $persistService->getActivitySubProcess($flow->process_id, $node['node_id']);
                /*if($node["status"] == Graph::COMPLETE && !$subProcess){
                    array_push($inValidNodeIds, $node["id"]);
                    continue;
                }*/
                if($subProcess){
                    $subFlow = Flow::findOne(["process_id" => $subProcess['ID']]);
                    $node['param']['flow_id'] = $subFlow->id;
                }
            }
            if ($node['type'] == Task::FORM) {
                $nodeCfg = isset($activityCfg[$node['node_id']]) ? $activityCfg[$node['node_id']] : [];
                if(array_key_exists($node['node_id'], $workItemList)){
                    $work = $workItemList[$node['node_id']];
                    $node['param']['work_item_id'] = $work['id'];

                    //如果是本人的work_item,告知侧边栏页面
                    if ($work['staff_id'] == $userId) {
                        $node['param']['page'] = $nodeCfg['page'];
                    }
                }else{
                    //排除未选择节点
                    /*if($node['status'] == Graph::COMPLETE) {
                        array_push($inValidNodeIds, $node["id"]);
                        continue;
                    }*/
                }
            }
            //array_push($filterNodes, $node);
        }

//        $map = [];
//        $map2 = [];
//        foreach ($edges as $i => $v){
//            if(in_array($v["from"], $inValidNodeIds)){
//                unset($edges[$i]);
//            }elseif(in_array($v["to"], $inValidNodeIds)){
//                $map2[] = $v["from"];
//                $edges[$i]["to"] = 0;
//            }else{
//                $map[] = $v;
//            }
//        }
//
//        foreach ($map2 as $from){
//            $flag = false;
//            foreach ($map as $v1){
//                if($from == $v1["from"]){
//                    $flag = true;
//                    break;
//                }
//            }
//            if(!$flag){
//                $map[] = ["from" => $from, "to" => 0];
//            }
//        }
        return [
            'node' => $nodes,
            'edge' => $edges
        ];
    }

    public static function getNodeInfo($workItemId)
    {
        return WorkItem::find()
            ->joinWith("staff", false)
            ->where([WorkItem::tableName().".id" => $workItemId])
            ->asArray()
            ->select(["step_name", "staff" => "name", "complete_time", "remark"])
            ->one();
    }

    /**
     * 新增直营健身
     *
     * @param $params
     * @throws Gym
     * @throws \Throwable
     * @throws \yii\db\Exception
     * @CreateTime 18/4/12 19:36:13
     * @Author: fangxing@likingfit.com
     */
    public static function openGym($params)
    {
        //查找选址池表
        $address = Address::findOne($params['address_id']);
        if($address === null){
            throw new AddressException(AddressException::INVALID);
        }
        $userId = \Yii::$app->user->getId();
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            //开启流程
            list($flow,$process) = FlowService::startProcess("OpenDirect",$userId);
            $process->start();
            $project = [
                'flow_id'       => $flow->id,
                'series_id'     => $flow->series_id,
                'gym_area'      => intval($address->build_area),
                'address'       => $address->address,
                'province_id'   => $address->province_id,
                'province_name' => $address->province_name,
                'city_id'       => $address->city_id,
                'city_name'     => $address->city_name,
                'district_id'   => $address->district_id,
                'district_name' => $address->district_name,
                'address_id'    => $params['address_id'],
                'gym_name'      => $params["gym_name"],
                'tel'           => $params["tel"],
                'receiver_name' => $params["receiver_name"],
                'open_type'     => OpenProject::DIRECT,
                'create_time'   => time(),
            ];

            $rentContract = [
                'flow_id'    => $flow->id,
                'series_id'  => $flow->series_id,
                'address_id' => $params['address_id'],
            ];
            $gym = self::save($project);
            OpenRentContractService::save($rentContract);

            //记录开店流程
            $purchase = key(\Yii::$app->params["gym"]["open_type"][OpenProject::DIRECT]["purchase"]);
            self::recordOpenFlow($flow->id, ["site_time" => time(), "purchase" => $purchase]);

            //关联补货流程
            $model = new GymSeries;
            $model->setAttributes([
                "series_id" => $flow->series_id,
                "project_id" => $gym->id,
                "customer_id" => UNAVAILABLE,
                "series_type" => GymSeries::MAIN
            ], false);
            $model->save();

            //自动完成第一步
            $workItem = FlowService::getWorkItemBySeriesId($flow->series_id);
            $workItem->remark = $params["description"];
            FlowService::completeWorkItem2($workItem);

            //更改地址状态
            $address->occupancy = Address::$occupancyUsed;
            $address->save();

            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            throw new Gym(Gym::GYM_SAVE_FAILED);
        }
    }

    public static function recordOpenFlow($seriesId, $data=[])
    {
        $openFlow = OpenFlow::findOne(["series_id" => $seriesId]);
        if(is_null($openFlow)){
            $openFlow = new OpenFlow;
            $openFlow->flow_id = $seriesId;
            $openFlow->series_id = $seriesId;
        }
        $openFlow->setAttributes($data, false);
        $openFlow->save();
    }

    /**
     * 检验客户是否存在
     * @param $params
     * @return bool
     * @CreateTime 2018/3/6 16:55:05
     * @Author     : screamwolf@likingfit.com
     */
    public static function checkCustomer($params)
    {
        $customer = Customer::checkCustomerByPhone($params);
        if (empty($customer)) {
            return 0;
        } else {
            return 1;
        }
    }


    /**
     * 新增合营健身房
     *
     * @param $params
     * @throws \Throwable
     * @CreateTime 18/4/20 12:20:03
     * @Author: fangxing@likingfit.com
     */
    public static function heGym($params)
    {
        $customer = Customer::findOne($params['customer_id']);
        //验证客户是否合格
        if($customer->audition != Customer::INTERVIEW_PASS){
            throw new CustomerException(CustomerException::NO_AUDITION);
        }
        if ($customer->docking_status == Customer::NO_AVAILABLE){
            throw new CustomerException(CustomerException::INVALID);
        }
        \Yii::$app->db->transaction(function ($db)use($params, $customer){
            list($flow,$process) = FlowService::startProcess("Main", \Yii::$app->user->getId(),[
                'IsMainRejoin' => AVAILABLE
            ]);
            $process->start();

            //记录开店流程
            self::recordOpenFlow($flow->id, ["customer_id" => $params['customer_id']]);

            list($start_date, $end_date) = $params['date'];
            $contract = [
                'flow_id'          => $flow->id,
                'series_id'        => $flow->series_id,
                'franchisee_name' => $params['franchisee_name'],
                'franchisee_phone' => $params['franchisee_phone'],
                'start_date'       => $start_date,
                'end_date'         => $end_date,
                'total_fee'        => $params['total_fee'],
                'gym_name'         => $params['gym_name'],
                'create_time'      => time(),
            ];
            OpenContractService::save($contract);

            $workItem = WorkItem::findOne(['series_id' => $flow->series_id, 'state' => 2]);

            $gymSeries = new GymSeries();
            $gymSeries->series_id = $flow->series_id;
            $gymSeries->customer_id = $params['customer_id'];
            $gymSeries->save();

            $workItem->remark = isset($params['remark']) ? $params['remark']: '';
            FlowService::completeWorkItem2($workItem);
            return true;
        });
    }

    /**
     * 保存场馆
     * @param $params
     * @return bool
     * @CreateTime 2018/3/6 16:55:05
     * @Author     : screamwolf@likingfit.com
     */
    /*public static function save ($params)
    {
        $model = new OpenProject();
        $model->setAttributes($params, false);
        return $model->save();
    }*/

    /**
     * 获取健身房最近的工作事项
     *
     * @param $projectId
     * @return mixed
     * @throws \yii\base\InvalidConfigException
     */
    public static function getLatestWorkItem($projectId)
    {
        $join = [
            "workItem" => function (ActiveQuery $query) {
                $query->orderBy(["create_time" => SORT_DESC])->limit(1);
            }
        ];
        $conditions = ["project_id" => $projectId];
        $gymModel = new OpenProject;
        $gym = $gymModel->getOneRecord($conditions, $join);
        return $gym["workItem"];
    }

    /**
     * 获取健身房信息
     *
     * @param $seriesId
     * @return null|OpenProject
     */
    public static function getGymBySeriesId($seriesId)
    {
        $gym = GymSeries::find()
            ->where(["series_id" => $seriesId])
            ->with("openProject")
            ->one();
        return $gym["openProject"];
    }

    public static function gymStatistics($open_type)
    {
        $statistics = OpenProject::find()
            ->where(["gym_status" => [OpenProject::WILL_OPEN, OpenProject::OPENING]])
            ->groupBy("gym_status")
            ->select(["num" => "COUNT(*)", "gym_status"])
            ->orderBy("gym_status asc")
            ->asArray()
            ->all();
        Base::convert2string($statistics, [
            "gym_status" => "gym.gym_status"
        ]);
        $chart = self::gymChart($open_type);
        return compact("statistics", "chart");
    }

    public static function gymChart($open_type)
    {
        $gyms = OpenFlow::find()
            ->joinWith("gym", false)
            ->where(["open_type" => $open_type])
            ->groupBy(["purchase", "open_type"])
            ->select(["value" => "COUNT(*)", "purchase", "open_type"])
            ->asArray()
            ->all();
        $gyms = ArrayHelper::index($gyms, null, "open_type");
        if(in_array(OpenProject::CONSORTIUM, $open_type)){
            $progress = \Yii::$app->params["gym"]["open_type"][OpenProject::CONSORTIUM]["purchase"];
        }else{
            $progress = \Yii::$app->params["gym"]["open_type"][OpenProject::DIRECT]["purchase"];
        }
        $series = [];
        $data = [];
        foreach ($progress as $key => $part){
            $value = 0;
            $name = $part["label"];
            array_push($data, $name);
            foreach ($gyms as $type => $gym){
                foreach ($gym as $v){
                    if($v["purchase"] == $key) {
                        $value += $v["value"];
                    }
                }
            }
            array_push($series, compact("name", "value"));
        }
        return compact("data", "series");
    }

    public static function getGym($param){
        $data = ['AND'];
        foreach ($param as $k => $val){
            if (!empty($val)){
                if ($k == 'gym_name'){
                    $data[] = ['LIKE','gym_name',$val];
                    continue;
                }
                $data[] = [$k=>$val];
            }
        }
        $data = ['!=','gym_status',OpenProject::CLOSE];
        return OpenProject::getGym($data);
    }


}