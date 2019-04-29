<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/1/13
 * Time: 下午4:17
 */

namespace app\services;

use app\models\Collection;
use app\models\Customer;
use app\models\GymSeries;
use app\models\OpenContract;
use app\models\OpenProject;
use app\models\RightSideConfig;
use app\models\Staff;
use app\models\WorkItem;
use likingfit\Workflow\Workflow;
use yii\db\ActiveQuery;

class WorkItemService
{
    const WORK_ITEM_PAGE_NUM = 10;

    /**
     * 获取用户的工作事项（带分页）
     *
     * @param $data
     * @param $state
     * @return array
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/25 21:44:33
     * @Author: fangxing@likingfit.com
     */
    public static function getUserWorkItems($data, $state)
    {
        $table = WorkItem::tableName();
        $gymTable = OpenProject::tableName();
        $customerTable = Customer::tableName();
        /**
         * @var $staff Staff
         */
        $staff = \Yii::$app->user->getIdentity();
        $role = key(\Yii::$app->getAuthManager()->getRolesByUser($staff->id));
        $additions = [
            "select" => [[
                $table . ".series_id",
                "work_item_id" => $table . ".id",
                "series_type",
                "display_name",
                "page",
                "deal_date",
                "user_data",
                "project_id" => $gymTable . ".id",
                "join_gym_name" => OpenContract::tableName().".gym_name",
                "direct_gym_name" => OpenProject::tableName().".gym_name",
                "open_type",
                "gym_province_name" => $gymTable . ".province_name",
                "gym_city_name" => $gymTable . ".city_name",
                "customer_name" => "name",
                "phone",
                "customer_province_name" => $customerTable . ".province_name",
                "customer_city_name" => $customerTable . ".city_name",
                "collection_id" => Collection::tableName().".work_item_id"
            ]],
            "orderBy" => [$table.".create_time desc"]
        ];
        $join = [[
            "rightSide",
            "appendantSeries",
            "collection" => function(ActiveQuery $query)use($staff){
                $query->andOnCondition([Collection::tableName().".staff_id" => $staff->id, "relation_status" => AVAILABLE]);
            }], false];
        $conditions = [
            "and",
            [
                "or",
                ["like", OpenContract::tableName().".gym_name", $data["query_name"]],
                ["like", OpenProject::tableName().".gym_name", $data["query_name"]],
                ["like", "name", $data["query_name"]],
            ],
            [
                $table.".staff_id" => $staff->id,
                "state" => $state,
                "deal_date" => $data['deal_date'],
            ]
        ];

        //是否显示收藏列表
        if($data["is_collect"] == AVAILABLE){
            $conditions[] = [">", Collection::tableName().".id", 0];
        }
        $model = new WorkItem($additions);
        $results = $model->getList($conditions, $join);

        $seriesIds = [];
        foreach ($results as $row) {
            if (($row["open_type"] == null
                    || $row["open_type"] == OpenProject::CONSORTIUM)
                && $row["series_type"] == GymSeries::MAIN) {
                array_push($seriesIds, $row["series_id"]);
            }
        }

        //查找还没到达项目部的流程
        $seriesIds = array_diff($seriesIds, WorkItem::find()
            ->where([
                "series_id" => $seriesIds,
                "activity_id" => ['OpenDirect.Activity11', 'Main.Activity6']
            ])
            ->select("series_id")
            ->column());

        foreach ($results as &$row) {
            $row["gym_name"] = $row["join_gym_name"] ?: $row["direct_gym_name"];
            if (in_array($row["series_id"], $seriesIds)) {
                $row["is_customer"] = AVAILABLE;
                $row["province_name"] = $row["customer_province_name"];
                $row["city_name"] = $row["customer_city_name"];
                $row["open_type"] = (string)OpenProject::CONSORTIUM;
                $row["project_id"] = $row["project_id"] ?: UNAVAILABLE;
            } else {
                //合营到达指定项目专员还未选址
                $row["is_customer"] = $row["gym_name"] ? UNAVAILABLE : AVAILABLE;
                $row["province_name"] = $row["gym_province_name"];
                $row["city_name"] = $row["gym_city_name"];
            }

            $row["is_collect"] = UNAVAILABLE;
            if($row["collection_id"] == $row["work_item_id"]){
                $row["is_collect"] = AVAILABLE;
            }
            unset(
                $row["join_gym_name"],
                $row["direct_gym_name"],
                $row["series_type"],
                $row["gym_province_name"],
                $row["gym_city_name"],
                $row["customer_province_name"],
                $row["customer_city_name"],
                $row["collection_id"]);
        }

        return $results;
    }

    public static function save($workItemInfo)
    {
        if (!empty($workItemInfo['id'])) {
            $workItem = WorkItem::findOne(['work']);
        } else {
            $workItem = new WorkItem();
        }
        $workItem->setAttributes($workItemInfo, false);
        $workItem->save();
        return $workItem;
    }

    /**
     * @param $staffId
     * @param $startDate
     * @param $endDate
     * @param array $state
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getWorkItemByDate($staffId, $startDate, $endDate, $state = [])
    {
        $condition = [
            'and',
            ['staff_id' => $staffId],
            ['>=', 'deal_date', $startDate],
            ['<=', 'deal_date', $endDate]
        ];
        if (!empty($state)) {
            $condition[] = ['in', 'state', $state];
        }
        $column = [
            'deal_date AS date',
            'count(1) AS count'
        ];
        return WorkItem::find()->select($column)->where($condition)->groupBy('deal_date')->asArray()->all();
    }

    /**
     * @param       $staffId
     * @param array $state
     *
     * @return int|string]
     */
    public static function getWorkItemCount($staffId, $state = [])
    {
        $condition = [
            'staff_id' => $staffId,
        ];
        if (!empty($state)) {
            $condition['state'] = $state;
        }
        return WorkItem::find()->where($condition)->count();
    }

    /**
     * 获取项目已完成的节点
     * @param $seriesId
     *
     * @return static[]
     */
    public static function getWorkItem($seriesId)
    {
        return WorkItem::findAll(['series_id' => $seriesId, 'state' => WorkItem::COMPLETED]);
    }

    /**
     * 获取单个节点
     * @param $seriesId
     * @param $activityId
     *
     * @return WorkItem
     */
    public static function getNodeItem($seriesId, $activityId)
    {
        return WorkItem::findOne(['series_id' => $seriesId, 'activity_id' => $activityId]);
    }

    /**
     * 获取完成的单个节点
     * @param $seriesId
     * @param $activityId
     *
     * @return WorkItem
     */
    public static function getCompleteItem($seriesId, $activityId)
    {
        return WorkItem::findOne(['series_id' => $seriesId, 'activity_id' => $activityId, 'state' => WorkItem::COMPLETED]);
    }

    public static function getOne($conditions = [])
    {
        return WorkItem::find()->where($conditions)->one();
    }

    /**
     * @param $workItemId
     * @return null|WorkItem
     */
    public static function getWorkItemById($workItemId)
    {
        return WorkItem::findOne(['id' => $workItemId]);
    }

    /**
     * 获取当前工作事项的前一步
     *
     * @param $workItem
     * @return array|null|\yii\db\ActiveRecord
     * @throws \ReflectionException
     * @CreateTime 18/4/1 14:25:19
     * @Author: fangxing@likingfit.com
     */
    public static function getPrevWorkItemList($workItem)
    {
        $prevActivityIds = static::getPrevActivityIds($workItem); //优先loop节点
        $mainTable = WorkItem::tableName();
        $condition = [
            $mainTable . '.activity_id' => &$prevActivityIds,
            'series_id' => $workItem['series_id']
        ];
        $fields = [
            "complete_time",
            "staff_name" => "name",
            "display_name",
            "remark"
        ];
        if(empty($prevActivityIds) || !WorkItem::find()->where($condition)->exists()){
            $prevActivityIds = static::getPrevActivityIds($workItem, false);
            $fields = [
                "complete_time",
                "staff_name" => "name",
                "display_name"
            ];

            //特殊节点
            if(in_array($workItem->activity_id, [
                "OpenDirect.Activity8",
                "ContractTask.Activity7",
                "ContractTask.Activity7",
                "HouseTask.Activity6",
                "HouseTask.Activity11"])){
                $fields[] = "remark";
            }
        }
        return WorkItem::find()
            ->joinWith(["staff", "rightSide"], false)
            ->where($condition)
            ->select($fields)
            ->orderBy($mainTable . ".id desc")
            ->limit(1)
            ->asArray()
            ->one();
    }

    /**
     * 获取前一步activity_id
     *
     * @param $workItem
     * @param $loop
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/4/1 15:04:18
     * @Author: fangxing@likingfit.com
     */
    public static function getPrevActivityIds($workItem, $loop=true)
    {
        $process = Workflow::getProcess($workItem['process_id']);
        $prevActivities = $process->getPrevActivities($workItem['activity_id'], $loop);
        $prevActivityIds = array();
        foreach ($prevActivities AS $activity) {
            $prevActivityIds[] = $activity->getId();
        }
        return $prevActivityIds;
    }

    /**
     * 获取配置
     *
     * @param $conditions
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 18/4/17 11:28:47
     * @Author: fangxing@likingfit.com
     */
    public static function getWorkItemWithCfgAndStaff($conditions)
    {
        return WorkItem::find()
            ->joinWith(["staff", "rightSide"], false)
            ->where($conditions)
            ->select("*")
            ->asArray()
            ->one();
    }

    /**
     * 获取前一步的activityId列表
     *
     * @param $workItemId
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/4/1 14:20:23
     * @Author: fangxing@likingfit.com
     */
    public static function getPreActivityIds($workItemId)
    {
        $workItem = WorkItem::findOne(['id' => $workItemId]);
        $process = Workflow::getProcess($workItem['process_id']);
        $prevActivities = $process->getPrevActivities($workItem['activity_id']);
        $prevActivityIds = array();
        foreach ($prevActivities AS $activity) {
            $prevActivityIds[] = $activity->getId();
        }
        return $prevActivityIds;
    }

    /**
     * 获取一个工作项所有下一步工作事项
     *
     * @param $workItem
     * @return array|null|\yii\db\ActiveRecord
     * @throws \ReflectionException
     * @CreateTime 18/3/27 14:20:02
     * @Author: fangxing@likingfit.com
     */
    public static function getNextActivityIdsList($workItem)
    {
        $process = Workflow::getProcess($workItem['process_id']);
        $nextActivities = $process->getNextActivitiesNew($workItem['activity_id']);
        $nextActivityIds = [];
        foreach ($nextActivities AS $activity) {
            $nextActivityIds[] = $activity->getId();
        }
        return RightSideConfig::find()
            ->where(["activity_id" => $nextActivityIds])
            ->select("display_name")
            ->asArray()
            ->all();
    }

    /**
     * 获取流程下工作项
     * @param      $flowId
     * @param null $activityId
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getFlowWorkItem($flowId, $activityId = null)
    {
        $condition = [
            'flow_id' => $flowId,
        ];
        if ($activityId !== null) {
            $condition['activity_id'] = $activityId;
        }
        return WorkItem::find()->where($condition)->all();
    }

    /**
     * 更换工作项负责人
     * @param $flowIds
     * @param $staffId
     *
     * @return int
     */
    public static function changeFlowStaff($flowIds, $staffId)
    {
        $condition = [
            'flow_id' => $flowIds
        ];
        $attributes = [
            'staff_id' => $staffId
        ];
        return WorkItem::updateAll($attributes, $condition);
    }

    /**
     * @param $workItemInfo
     *
     * @return WorkItem|array|null|\yii\db\ActiveRecord
     */
    public static function saveWorkItem($workItemInfo)
    {
        if (isset($workItemInfo['id'])) {
            $workItem = WorkItem::find()->where(['id' => $workItemInfo['id']])->one();
        } else {
            $workItem = new WorkItem();
        }
        $workItem->setAttributes($workItemInfo, false);
        $workItem->save();
        return $workItem;
    }

    /**
     * 获取series_id
     *
     * @param $param
     * @return array
     * @CreateTime 18/3/19 16:35:10
     * @Author: fangxing@likingfit.com
     */
    public static function getWorkItemSeriesId($param)
    {
        $workItemList = WorkItem::find();
        $seriesIds = $workItemList->filterWhere($param)
            ->select('series_id')
            ->groupBy("series_id")
            ->column();
        return $seriesIds;
    }
}