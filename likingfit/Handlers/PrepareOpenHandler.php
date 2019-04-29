<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/23 14:40:33
 */

namespace likingfit\Handlers;


use app\models\FlowSuspend;
use app\models\GymSeries;
use app\models\OpenProject;
use app\models\OrderEntry;
use app\services\FlowService;
use app\services\GymService;
use app\services\WorkflowPersistenceService;
use likingfit\Events\ActivityEvent;
use likingfit\Workflow\Base\Activity;
use likingfit\Workflow\Base\Token;
use likingfit\Workflow\Workflow;

class PrepareOpenHandler extends InterruptedHandler
{
    public function events()
    {
        return [
            Activity::EVENT_BEFORE_START => "activityBeforeStart",
            Activity::EVENT_AFTER_COMPLETE => "continueActivity"
        ];
    }

    public function breakPoint()
    {
        return [
            "Main.Activity15" => "confirmAllOrder",
            "OpenDirect.Activity15" => "confirmAllOrder"
        ];
    }

    /**
     * 检测是否所有订单都确认到货
     *
     * @param Activity $activity
     * @param Token $token
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 18/4/23 17:42:05
     * @Author: fangxing@likingfit.com
     */
    public function confirmAllOrder(Activity $activity, Token $token)
    {
        if(!$token->isAlive()){
            return true;
        }
        $processId = $token->getProcessId();
        $process = Workflow::getProcess($processId);
        $flow = FlowService::getProcessFlow($process->getId());
        $gym = GymService::getGymBySeriesId($flow->series_id);

        //更新补单状态
        $gym->can_replenishment = UNAVAILABLE;
        $gym->save();

        $orders = OrderEntry::find()->where([
            "and",
            [
                "is_replenishment" => AVAILABLE,
                "project_id" => $gym->id,
                "is_valid" => AVAILABLE
            ],
            ["<>", "order_status", OrderEntry::CONFIRM_RECEIVER]
        ])->all();
        if (empty($orders)) {
            //all is arrived
            return true;
        }
        //暂停节点
        $flowSuspend = new FlowSuspend();
        $flowSuspend->series_id = $flow->series_id;
        $flowSuspend->activity_id = $activity->getId();
        $flowSuspend->process_id = $processId;
        $flowSuspend->series_status = FlowSuspend::SUSPEND;
        $flowSuspend->save();
        return false;
    }

    /**
     * 补单流程完成
     *
     * @param ActivityEvent $event
     * @throws \ReflectionException
     * @CreateTime 18/4/25 18:49:56
     * @Author: fangxing@likingfit.com
     */
    public function continueActivity(ActivityEvent $event)
    {
        $activity = $event->sender;
        if ($activity->getId() != "Relenishment.Activity50") {
            return;
        }

        $token = $event->token;
        $process = Workflow::getProcess($token->getProcessId());
        $flow = FlowService::getProcessFlow($process->getId());
        $gym = GymService::getGymBySeriesId($flow->series_id);

        $orders = OrderEntry::find()
            ->where([
                "and",
                [
                    "is_replenishment" => AVAILABLE,
                    "project_id" => $gym->id,
                    "is_valid" => AVAILABLE
                ],
                ["<>", "order_status", OrderEntry::CONFIRM_RECEIVER]
            ])->all();

        if (!empty($orders)) {
            return;
        }
        //唤醒主流程
        $mainSeries = FlowSuspend::find()
            ->joinWith("gymSeries", false)
            ->where([
                "is_valid" => AVAILABLE,
                "series_type" => GymSeries::MAIN,
                "project_id" => $gym->id,
                "activity_id" => ["Main.Activity15", "OpenDirect.Activity15"]
            ])
            ->one();

        //主流程是暂停状态(唤醒主流程)
        if ($mainSeries && $mainSeries->series_status == FlowSuspend::SUSPEND) {

            $mainProcess = Workflow::getProcess($mainSeries->process_id);
            $breakActivity = $mainProcess->getNet()->getElement($mainSeries->activity_id);
            /**
             * @var $breakActivity Activity
             * @var $mainToken Token[]
             */
            $mainToken = WorkflowPersistenceService::getProcessToken($mainSeries->process_id, $breakActivity->getId());
            $mainProcess->createActivityTask($breakActivity, $mainToken[0]);
            $mainSeries->is_valid = UNAVAILABLE;
            $mainSeries->save();
        }
    }
}