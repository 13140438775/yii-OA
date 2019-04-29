<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/14 13:46:33
 */

namespace likingfit\Handlers;


use app\models\Flow;
use app\models\FlowSuspend;
use app\models\GymSeries;
use app\models\OpenProject;
use app\models\OrderEntry;
use app\models\PayList;
use app\services\FlowService;
use app\services\GymService;
use app\services\PayListService;
use app\services\WorkflowPersistenceService;
use likingfit\Events\ActivityEvent;
use likingfit\Events\TaskEvent;
use likingfit\Workflow\Base\Activity;
use likingfit\Workflow\Base\TaskInstance;
use likingfit\Workflow\Base\Token;
use likingfit\Workflow\Workflow;
use yii\base\Event;

class ReplenishOrderHandler extends InterruptedHandler
{
    public function events()
    {
        return [
            Activity::EVENT_BEFORE_START => "activityBeforeStart",
            Activity::EVENT_AFTER_COMPLETE => "activityAfterComplete"
        ];
    }

    public function breakPoint()
    {
        return [
            "Main.Activity13" => "confirmOpenOrder",
            "Main.Activity22" => "confirmDecorationOrder",
            "Relenishment.Activity35" => "confirmReplenishmentOpenOrder",
            "Relenishment.Activity38" => "confirmReplenishmentDecorationOrder"
        ];
    }

    /**
     * 补货单越过录入确认款项
     *
     * @param TaskEvent $event
     * @throws \ReflectionException
     * @throws \likingfit\Workflow\Exception\ProcessException
     * @CreateTime 18/4/13 18:08:40
     * @Author: fangxing@likingfit.com
     */
    public function taskBeforeStart(TaskEvent $event)
    {
        $taskInstance = $event->sender;
        /**
         * @var $taskInstance TaskInstance
         */
        if(in_array($taskInstance->getActivityId(), [
            "Relenishment.Activity35",
            "Relenishment.Activity38",
            "Relenishment.Activity47",
            "Relenishment.Activity48"])){
            $process = Workflow::getProcess($taskInstance->getProcessId());
            $taskInstance->start();
            $persistenceService = Workflow::getPersistenceService();
            $persistenceService->saveTask($taskInstance);
            $process->completeTask($taskInstance->getId());
            $event->is_valid = false;
        }
    }

    /**
     * 确认款项完成后唤醒补单流程
     *
     * @param ActivityEvent $event
     * @return bool|void
     * @throws \ReflectionException
     * @CreateTime 18/4/26 17:36:18
     * @Author: fangxing@likingfit.com
     */
    public function activityAfterComplete(ActivityEvent $event)
    {
        if(!$event->token->isAlive()){
            return;
        }
        $base = [
            "Main.Activity14" => "Relenishment.Activity35",
            "Main.Activity23" => "Relenishment.Activity38"
        ];
        $activity = $event->sender;
        /**
         * @var $activity Activity
         */
        $activityId = $activity->getId();

        //声明节点
        if(!array_key_exists($activityId, $base)){
            return;
        }
        $token = $event->token;
        $flow = FlowService::getProcessFlow($token->getProcessId());
        $gym = GymService::getGymBySeriesId($flow->series_id);

        if($activityId == "Main.Activity14"){
            $cost_type = PayList::OPEN_ORDER_FEE;
        }else{
            $cost_type = PayList::DECORATION_ORDER_FEE;
        }
        $payList = PayListService::getPayListInfo([
            "cost_type" => $cost_type,
            "series_id" =>  $gym->series_id,
            "project_id" => $gym->id,
            "is_valid" => AVAILABLE
        ]);
        //如果未全部到账，不做任何处理
        if($payList->pay_status != PayList::ALL_ARRIVED){
            return;
        }
        $suspends = FlowSuspend::find()
            ->joinWith("gymSeries", false)
            ->where([
                "is_valid" => AVAILABLE,
                "series_type" => GymSeries::APPEND,
                "project_id" => $gym->id,
                "activity_id" => $base[$activityId]
            ])
            ->all();
        if(empty($suspends)){
            return;
        }

        $key = "IsMainDeviceOrderAmount";
        if ($activityId === "Main.Activity23") {
            $key = "IsMainFinishOrderAmount";
        }

        Event::on(TaskInstance::class, TaskInstance::EVENT_BEFORE_START, [$this, "taskBeforeStart"]);

        foreach ($suspends as $suspend){
            //$appendFlow = FlowService::getFlow($suspend->series_id);
            $appendProcess = Workflow::getProcess($suspend->process_id);
            /**
             * @var $breakActivity Activity
             * @var $token Token[]
             */
            $breakActivity = $appendProcess->getNet()->getElement($suspend->activity_id);
            $appendToken = WorkflowPersistenceService::getProcessToken($suspend->process_id, $suspend->activity_id);
            $appendProcess->setVariable($key, AVAILABLE);
            $appendProcess->createActivityTask($breakActivity, $appendToken[0]);
            $suspend->is_valid = UNAVAILABLE; //恢复流程
            $suspend->save();
        }

        Event::off(TaskInstance::class, TaskInstance::EVENT_BEFORE_START, [$this, "taskBeforeStart"]);
    }

    protected function checkOrder(Flow $flow, $sub_type, $is_replenishment = 1)
    {
        $gym = GymService::getGymBySeriesId($flow->series_id);
        $orders = OrderEntry::findAll([
            "is_replenishment" => $is_replenishment,
            "project_id" => $gym->id,
            "sub_order_type" => $sub_type,
            "order_status" => [0, OrderEntry::SUBMIT_ORDER],
            "is_valid" => AVAILABLE
        ]);
        if (empty($orders)) {
            return true;
        }
        return false;
    }

    /**
     * 器械等待补单
     *
     * @param Activity $activity
     * @param Token $token
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 18/4/13 12:54:14
     * @Author: fangxing@likingfit.com
     */
    protected function confirmOpenOrder(Activity $activity, Token $token)
    {
        if(!$token->isAlive()){
            return true;
        }
        $process = Workflow::getProcess($token->getProcessId());
        $flow = FlowService::getProcessFlow($process->getId());
        $activityId = $activity->getId();
        if(!$this->checkOrder($flow, OrderEntry::OPEN)){
            //有补货订单先暂停流程
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activityId;
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }
        return true;
    }

    /**
     * 装修等待补单
     *
     * @param Activity $activity
     * @param Token $token
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 18/4/13 12:54:22
     * @Author: fangxing@likingfit.com
     */
    protected function confirmDecorationOrder(Activity $activity, Token $token)
    {
        if(!$token->isAlive()){
            return true;
        }
        $process = Workflow::getProcess($token->getProcessId());
        $flow = FlowService::getProcessFlow($process->getId());
        $activityId = $activity->getId();
        if(!$this->checkOrder($flow, OrderEntry::DECORATION)){
            //有补货订单先暂停流程
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activityId;
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }
        return true;
    }

    /**
     * 器械补单等待主流程
     *
     * @param Activity $activity
     * @param Token $token
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 18/4/13 14:16:20
     * @Author: fangxing@likingfit.com
     */
    protected function confirmReplenishmentOpenOrder(Activity $activity, Token $token)
    {
        $process = Workflow::getProcess($token->getProcessId());
        $flow = FlowService::getProcessFlow($process->getId());
        $gym = GymService::getGymBySeriesId($flow->series_id);
        if($gym->open_type == OpenProject::DIRECT || !$token->isAlive()){
            return true;
        }
        $orders = OrderEntry::findAll([
            "project_id" => $gym->id,
            "sub_order_type" => OrderEntry::OPEN,
            "order_status" => [0, OrderEntry::SUBMIT_ORDER],
            "is_valid" => AVAILABLE
        ]);

        if (!empty($orders)) {
            //其他订单流程未确认所有订单, 暂停当前流程
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activity->getId();
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }

        //查找主流程
        $mainSeries = FlowSuspend::find()
            ->joinWith("gymSeries", false)
            ->where([
                "is_valid" => AVAILABLE,
                "series_type" => GymSeries::MAIN,
                "project_id" => $gym->id,
                "activity_id" => "Main.Activity13"
            ])
            ->one();

        //主流程是暂停状态(唤醒主流程)
        if ($mainSeries && $mainSeries["series_status"] == FlowSuspend::SUSPEND) {
            //$mainFlow = FlowService::getFlow($mainSeries->series_id);
            $mainProcess = Workflow::getProcess($mainSeries["process_id"]);
            $breakActivity = $mainProcess->getNet()->getElement($mainSeries["activity_id"]);
            /**
             * @var $breakActivity Activity
             * @var $mainToken Token[]
             */
            $mainToken = WorkflowPersistenceService::getProcessToken($mainSeries["process_id"], $mainSeries["activity_id"]);
            $mainProcess->createActivityTask($breakActivity, $mainToken[0]);

            $mainSeries->is_valid = UNAVAILABLE;
            $mainSeries->save();

            //暂停当前流程, 等待确认款项结束
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activity->getId();
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }

        //主流程已经过了录入款项的点
        return true;
    }

    /**
     * 装修补单等待主流程
     *
     * @param Activity $activity
     * @param Token $token
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 18/4/13 14:31:21
     * @Author: fangxing@likingfit.com
     */
    protected function confirmReplenishmentDecorationOrder(Activity $activity, Token $token)
    {
        $process = Workflow::getProcess($token->getProcessId());
        $flow = FlowService::getProcessFlow($process->getId());
        $gym = GymService::getGymBySeriesId($flow->series_id);
        if($gym->open_type == OpenProject::DIRECT || !$token->isAlive()){
            return true;
        }
        $orders = OrderEntry::findAll([
            "is_replenishment" => UNAVAILABLE,
            "project_id" => $gym->id,
            "sub_order_type" => OrderEntry::DECORATION,
            "order_status" => OrderEntry::SUBMIT_ORDER,
            "is_valid" => AVAILABLE
        ]);

        if (!empty($orders)) {
            //主流程未确认所有订单, 暂停补货流程
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activity->getId();
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }

        $mainSeries = FlowSuspend::find()
            ->joinWith("gymSeries", false)
            ->where([
                "is_valid" => AVAILABLE,
                "series_type" => GymSeries::MAIN,
                "project_id" => $gym->id,
                "activity_id" => "Main.Activity22"
            ])
            ->one();

        //主流程是暂停状态(唤醒主流程)
        if ($mainSeries && $mainSeries["series_status"] == FlowSuspend::SUSPEND) {

            //$mainFlow = FlowService::getFlow($mainSeries->series_id);
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

            //暂停补货流程, 等待确认款项结束
            $flowSuspend = new FlowSuspend;
            $flowSuspend->series_id = $flow->series_id;
            $flowSuspend->activity_id = $activity->getId();
            $flowSuspend->process_id = $token->getProcessId();
            $flowSuspend->series_status = FlowSuspend::SUSPEND;
            $flowSuspend->save();
            return false;
        }
        //主流程已经过了录入款项的点
        return true;
    }
}