<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/23
 * Time: 下午3:33
 */

namespace app\services;

use app\models\WorkItem;
use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Workflow;
use yii\helpers\ArrayHelper;
use yii\helpers\Json;

class AssignService
{

    /**
     * 生成任务
     *
     * @param TaskInstance $task
     * @return WorkItem|null|static
     * @throws \ReflectionException
     * @throws \likingfit\Workflow\Exception\ProcessException
     * @CreateTime 18/4/14 16:33:06
     * @Author: fangxing@likingfit.com
     */
    public function start(TaskInstance $task)
    {
        $process = Workflow::getProcess($task->getProcessId());
        $activityId = $task->getActivityId();
        $flow = FlowService::getProcessFlow($process->getId());
        $activityCfg = FlowService::getActivityInfoByCfg($flow['series_id'], $activityId);

        $performerId = $this->getPerformer($flow, $activityCfg);

        $workItemId = $process->assignTask($task->getId(), $performerId);
        //自动签收
        $process->claimWorkItem($workItemId);

        $workItem = [
            'work_item_id' => $workItemId,
            'flow_id' => $flow['id'],
            'series_id' => $flow['series_id'],
            'process_id' => $process->getId(),
            'activity_id' => $activityId,
            'state' => WorkItem::CLAIMED,
            'staff_id' => $performerId,
            'deal_date' => date('Y-m-d'),
            'step_name' => isset($activityCfg['display_name']) ? $activityCfg['display_name'] : $task->getDisplayName()
        ];
        return WorkItemService::save($workItem);
    }

    /**
     * 获取执行者
     *
     * @param $flow
     * @param $cfg
     * @return int
     * @CreateTime 18/4/14 16:32:35
     * @Author: fangxing@likingfit.com
     */
    protected function getPerformer($flow, $cfg)
    {
        if(\Yii::$app->user->getId() == 72){
            return 72;
        }
        $director = DirectorService::getSeriesDirector($flow['series_id'], $cfg["role_name"]);
        if ($director){
            return $director->staff_id;
        }

        $userIds = \Yii::$app->getAuthManager()->getUserIdsByRole($cfg["role_name"]);

        if(empty($userIds)){
            return 0;
        }

        $userData = Json::decode($cfg["user_data"]);
        if(isset($userData["order_type"])){
            $cg = ArrayHelper::getValue(\Yii::$app->params, ["order_entry", "order_type", $userData["order_type"], "cg"]);
            if(in_array($cg, $userIds)){
                return $cg;
            }
        }
        return $userIds[0];
    }
}