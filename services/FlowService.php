<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/1/14
 * Time: 下午5:09
 */

namespace app\services;

use app\models\Flow;
use app\models\OpenLog;
use app\models\OpenProject;
use app\models\ProcinstVar;
use app\models\ProjectDirector;
use app\models\RightSideConfig;
use app\models\Roles;
use app\models\Staff;
use app\models\WorkItem;
use likingfit\Events\WorkItemEvent;
use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Workflow;
use yii\base\Event;
use yii\helpers\ArrayHelper;

class FlowService
{
    const APPOINT_DESC = '各部门经理指定专员';
    const APPOINT_STAFF_DESC = '各部门负责人';

    /**
     * @param $id
     * @return null|Flow
     * @CreateTime 18/4/13 14:21:12
     * @Author: fangxing@likingfit.com
     */
    public static function getFlow($id)
    {
        return Flow::findOne(['id' => $id]);
    }

    /**
     * @param $workItemId
     *
     * @return null|WorkItem
     */
    public static function getWorkItem($workItemId)
    {
        return WorkItem::findOne(['id' => $workItemId]);
    }

    /**
     * @param $series_id
     * @return null|WorkItem
     */
    public static function getWorkItemBySeriesId($series_id)
    {
        return WorkItem::findOne(['series_id' => $series_id]);
    }

    public static function completeFlowByProcessId($processId)
    {
        $flow = Flow::findOne(['process_id' => $processId]);
        $flow->flow_status = Flow::COMPLETE;
        $flow->complete_time = date('Y-m-d H:i:s');
        $flow->save();
        return;
    }

    /**
     * 创建流程
     * @param $defId
     * @param $creatorId
     * @param $concreteAttribute属性设置
     *
     * @return array
     */
    public static function startProcess($defId, $creatorId, $concreteAttribute = [])
    {
        $process = Workflow::createProcess($defId, $creatorId);
        if (!empty($concreteAttribute)) {
            foreach ($concreteAttribute as $key => $value) {
                $process->setVariable($key, $value);
            }
        }

        $flowInfo = [
            'process_id' => $process->getId(),
            'name' => $process->getDisplayName(),
            'def_name' => $process->getDefineId(),
            'flow_type' => 0,
            'creator_id' => $creatorId
        ];
        $flow = new Flow();
        $flow->setAttributes($flowInfo, false);
        $flow->save();
        $flow->series_id = $flow->id;
        $flow->update();
        return [
            $flow,
            $process
        ];
    }

    /**
     * 创建子流程
     * @param $defId
     * @param $creatorId
     * @param $parentProcessId
     * @param $parentTaskId
     *
     * @return array
     */
    public static function startSubProcess($defId, $creatorId, $parentProcessId, $parentTaskId)
    {
        $subProcess = Workflow::createSubflowProcess($defId, $creatorId, $parentProcessId, $parentTaskId);
        $parentFlow = self::getProcessFlow($parentProcessId);
        $flowInfo = [
            'series_id' => $parentFlow['series_id'],
            'process_id' => $subProcess->getId(),
            'name' => $subProcess->getDisplayName(),
            'def_name' => $subProcess->getDefineId(),
            'flow_type' => 0,
            'flow_level' => $parentFlow['flow_level'] + 1,
            'parent_flow_id' => $parentFlow['id'],
            'creator_id' => $creatorId
        ];
        $flow = new Flow();
        $flow->setAttributes($flowInfo, false);
        $flow->save();
        return [
            $flow,
            $subProcess
        ];
    }

    /**
     * 完成工作事项
     *
     * @param $workItemId
     * @param string $remark
     * @param string $param
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/19 10:41:56
     * @Author: fangxing@likingfit.com
     */
    public static function completeWorkItem($workItemId, $remark = '', $param = '')
    {
        $workItem = WorkItem::findOne(['id' => $workItemId]);

        //触发事件
        $event = new WorkItemEvent;
        $event->workItem = $workItem;
        Event::trigger(TaskInstance::class, TaskInstance::EVENT_BEFORE_COMPLETE, $event);


        $workItem->state = WorkItem::COMPLETED;
        $workItem->remark = $remark;
        $workItem->complete_time = date('Y-m-d H:i:s');

        /*if (!empty($param)) {
            $workItem->param = $param;
        }*/

        \Yii::$app->db->transaction(function ($db) use ($workItem) {
            $workItem->save();
            $process = Workflow::getProcess($workItem->process_id);
            $process->completeWorkItem($workItem->work_item_id);
        });

        //触发事件
        Event::trigger(TaskInstance::class, TaskInstance::EVENT_AFTER_COMPLETE, $event);
        return;

    }

    /**
     * 新的完成工作事项
     *
     * @param $workItem WorkItem
     * @throws \Throwable
     * @CreateTime 18/4/19 18:20:10
     * @Author: fangxing@likingfit.com
     */
    public static function completeWorkItem2($workItem)
    {
        //触发事件
        $event = new WorkItemEvent;
        $event->workItem = $workItem;
        Event::trigger(TaskInstance::class, TaskInstance::EVENT_BEFORE_COMPLETE, $event);


        $workItem->state = WorkItem::COMPLETED;
        $workItem->complete_time = date('Y-m-d H:i:s');

        \Yii::$app->db->transaction(function ($db) use ($workItem) {
            $workItem->save();
            $process = Workflow::getProcess($workItem->process_id);
            $process->completeWorkItem($workItem->work_item_id);
        });

        //触发事件
        Event::trigger(TaskInstance::class, TaskInstance::EVENT_AFTER_COMPLETE, $event);
        return;

    }

    public static function completeRelationWorkItem($processId)
    {
        $flow = Flow::findOne(['process_id' => $processId]);
        $parentFlowId = $flow['parent_flow_id'];
        $flowList = Flow::findAll(['parent_flow_id' => $parentFlowId]);
        $proessIds = [];
        foreach ($flowList as $item) {
            $proessIds[] = $item['process_id'];
        }
        $leftNum = WorkItem::find()->where(['process_id' => $proessIds, 'state' => WorkItem::CLAIMED])->count();   //剩余未完成的数量
        if ($leftNum == 0) {
            $workItem = WorkItem::findOne(['flow_id' => $parentFlowId, 'activity_id' => 'PreSalePlace.MultiDeliveryActivity']);
            self::completeWorkItem($workItem->id);
        }
    }

    /**
     * @param $processId
     *
     * @return array|null|Flow
     */
    public static function getProcessFlow($processId)
    {
        $flow = Flow::find()->where(['process_id' => $processId])->one();
        return $flow;
    }

    /**
     * 获取最顶层Flow
     * @param $flowId
     *
     * @return Flow
     */
    public static function getTopFlow($flowId)
    {
        $flow = Flow::findOne(['id' => $flowId]);
        while ($flow['parent_flow_id']) {
            $flow = Flow::findOne(['id' => $flow['parent_flow_id']]);
        }
        return $flow;
    }

    /**
     * 获取某系列流程下所有工作项
     * @param $series
     * @param $state
     *
     * @return WorkItem[]
     */
    public static function getSeriesWorkItem($series, $state = [])
    {
        $condition = [
            'series_id' => $series,
            'state' => $state
        ];
        return WorkItem::find()
            ->with('staff')
            ->filterWhere($condition)
            ->all();
    }

    /**
     * 获取上一步的集合
     * @param array $pages
     *
     * @return array
     */
    public static function getWorkItemByPage($seriesId, $pages = [])
    {
        $items = WorkItem::find()->with('staff')->where([
            'series_id' => $seriesId,
            'page' => $pages,
            //         		'state' => 3
        ])->limit(count($pages))->orderBy(['id' => SORT_DESC])->all();
        $result = [];
        foreach ($items as $item) {
            $staff = $item->staff;
            $result[] = [
                'item_name' => $item['name'],
                'staff_name' => $staff['name'],
                'item_remark' => $item['remark'],
                'activity_id' => $item['activity_id'],
                'page' => $item['page']
            ];
        }
        return $result;
    }

    /**
     *按照activity_id获取上一步的集合
     */
    public static function getWorkItemByActivityId($seriesId, $activityIds = [], $limit = true)
    {
        $result = [];
        if (in_array('appoint', $activityIds)) {
            $result[] = [
                'item_name' => self::APPOINT_DESC,
                'staff_name' => self::APPOINT_STAFF_DESC,
                'item_remark' => '',
            ];
            return $result;
        }
        $items = WorkItem::find()->with('staff')->where([
            'series_id' => $seriesId,
            'activity_id' => $activityIds,
            'state' => 3
        ]);
        if ($limit) {
            $items->limit(count($activityIds));
        }
        $items = $items->orderBy(['id' => SORT_DESC])->all();

        foreach ($items as $item) {
            if (empty($item['staff_id'])) {
                continue;
            }
            $staff = $item->staff;
            $result[] = [
                'item_name' => $item['name'],
                'staff_name' => $staff['name'],
                'item_remark' => $item['remark'],
                'activity_id' => $item['activity_id'],
                'flow_id' => $item['flow_id'],
                'page' => $item['page'],
                'param' => $item['param']
            ];
        }
        return $result;
    }

    /**
     * 获取所有子流程
     * @param $flowId
     *
     * @return array|\yii\db\ActiveRecord[]
     */
    public static function getSubFlow($flowId)
    {
        $condition = [
            'parent_flow_id' => $flowId
        ];
        return Flow::find()->where($condition)->all();
    }

    /**
     * 获取流程的配置文件
     * @param int $flowId
     *
     * @return mixed
     */
    public static function getFlowConfig($flowId)
    {
        $config = array();
        $flow = Flow::findOne(['id' => $flowId]);
        $process = Workflow::getProcess($flow['process_id']);
        $define = Workflow::getProcessDef($process->getDefineId(), $process->getVersion());
        if ($define->CONFIG_PATH) {
            $config = require \Yii::getAlias('@app') . '/params/' . $define->CONFIG_PATH;
        }
        return $config;
    }

    public static function getProcessFlows($processIds)
    {
        $condition = [
            'process_id' => $processIds
        ];
        return Flow::find()->where($condition)->all();
    }

    public static function completeSpecialWorkItem($seriesId, $activityId, \Closure $when)
    {

        if (call_user_func($when)) {
            $workItem = WorkItemService::getNodeItem($seriesId, $activityId);
            FlowService::completeWorkItem($workItem->id);
        }
    }

    /**
     * 通过活动ID获取节点信息
     * @param  int $seriesId
     * @param string $activityId
     *
     * @return array
     */
    public static function getActivityInfoByCfg($seriesId, $activityId)
    {
        $res = RightSideConfig::getCfgByActivity($activityId);
        return $res;
    }

    /**
     * 完成事项，并设置分支
     *
     * @param $work_item_id
     * @param $params
     * @throws \ReflectionException
     * @CreateTime 18/3/29 11:52:58
     * @Author: fangxing@likingfit.com
     */
    public static function setVariable($work_item_id, $params)
    {
        $workItem = FlowService::getWorkItem($work_item_id);
        $process = Workflow::getProcess($workItem->process_id);

        foreach ($params as $k => $v) {
            $process->setVariable($k, $v);
        }
    }

    public static function getCustomerBySeriesId($seriesId)
    {
        $data = ProjectDirector::find()
            ->with("customer")
            ->where(['series_id' => $seriesId])
            ->one();

        return is_null($data)?null:$data->customer;
    }

    /**
     * 获取当前工作事项附近的工作事项
     *
     * @param $workItem
     * @return array
     * @throws \ReflectionException
     * @CreateTime 18/3/27 15:39:11
     * @Author: fangxing@likingfit.com
     */
    public static function getNearByWorkItem($workItem)
    {
        $step = [];
        $params = \Yii::$app->params["nearlyActivity"];
        if (($name = ArrayHelper::getValue($params, "{$workItem->activity_id}.prev")) !== null) {
            $step[] = [
                "display_name" => $name
            ];
        } elseif (($prev = WorkItemService::getPrevWorkItemList($workItem)) && !empty($prev)) {
            $step[] = $prev;
        }
        $curr = WorkItemService::getWorkItemWithCfgAndStaff([WorkItem::tableName() . ".id" => $workItem->id]);
        $step[] = [
            "complete_time" => $curr["complete_time"],
            "staff_name" => $curr["name"],
            "display_name" => $curr["display_name"],
            "remark" => $curr["remark"],
        ];
        if (($name = ArrayHelper::getValue($params, "{$workItem->activity_id}.next")) !== null) {
            $step[] = [
                "display_name" => $name
            ];
        } elseif (($next = WorkItemService::getNextActivityIdsList($workItem)) && !empty($next)) {
            $step[] = $next[0];
        }
        return $step;
    }

    /**
     * 获取侧边栏头部健身房信息
     *
     * @param $flow
     * @return array
     * @deprecated
     * @CreateTime 18/3/22 16:49:31
     * @Author: fangxing@likingfit.com
     */
    public static function getHeaderInfo($flow)
    {
        $conditions = ["series_id" => $flow["series_id"]];
        $gym = OpenProject::findOne($conditions);
        $headerInfo = [];

        //补货的侧边栏获取头部信息
        if ($gym === null) {
            $project_id = WorkflowPersistenceService::getVariableByName($flow["process_id"], "project_id");
            $conditions = [OpenProject::tableName() . ".id" => $project_id];
            $gym = OpenProject::findOne($conditions);
            $conditions = ["series_id" => $gym->series_id];
        }

        $customer = ProjectDirector::find()
            ->joinWith("customer", false)
            ->where($conditions)
            ->select(["name", "phone"])
            ->asArray()
            ->one();

        if($customer){
            $headerInfo["name"] = $customer["name"];
            $headerInfo["phone"] = $customer["phone"];
        }

        if ($gym) {
            $headerInfo['gym_name'] = $gym->gym_name;
            $headerInfo['open_type'] = $gym->open_type;
            $headerInfo['project_id'] = $gym->id;
        }

        return $headerInfo;
    }

    public static function getVariableBySeriesId($seriesId, $name)
    {
        return ProcinstVar::find()
            ->joinWith("flow", false)
            ->where(["series_id" => $seriesId, Flow::tableName() . ".NAME" => $name])
            ->select("VALUE")
            ->asArray()
            ->column();
    }

    public static function getVariablesByFlowId($flowId, $conditions)
    {
        return ProcinstVar::find()
            ->joinWith("flow", false)
            ->where(["flow_id" => $flowId])
            ->andWhere($conditions)
            ->select(["name" => "NAME", "value" => "VALUE"])
            ->asArray()
            ->all();
    }

    /**
     * 记录日志
     *
     * @param $workItemId
     * @param $data
     * @return OpenLog
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/4/4 12:51:34
     * @Author: fangxing@likingfit.com
     */
    public static function recordOpenLog($workItemId, $data=[])
    {
        $openLog = new OpenLog;
        /**
         * @var $user Staff
         */
        $user = \Yii::$app->getUser()->getIdentity();
        $role_name = key(\Yii::$app->getAuthManager()->getRolesByUser($user->id));
        $role = Roles::findOne(["role_name" => $role_name]);
        $openLog->work_item_id = $workItemId;
        $openLog->user_name = $user->name;
        $openLog->role_name = $role->display_name;
        $openLog->setAttributes($data, false);
        $openLog->save(false);
        return $openLog;
    }

    /**
     * 关闭流程（穿透子流程）
     *
     * @param $flowId
     * @throws \ReflectionException
     * @throws \likingfit\Workflow\Exception\ProcessException
     * @CreateTime 18/4/9 23:01:14
     * @Author: fangxing@likingfit.com
     */
    public static function closeProcessByFlowId($flowId)
    {
        $flow = self::getFlow($flowId);
        $process = Workflow::getProcess($flow->process_id);
        $process->abort();
    }
}