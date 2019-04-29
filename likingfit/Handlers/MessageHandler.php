<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/8 17:03:52
 */

namespace likingfit\Handlers;


use app\models\Message;
use app\services\FlowService;
use app\services\MessageService;
use likingfit\Events\WorkItemEvent;
use likingfit\Workflow\Base\Process;
use likingfit\Workflow\Base\Task;
use likingfit\Workflow\Base\TaskInstance;
use yii\base\Event;

class MessageHandler implements Handler
{
    public $shouldShip = [
        "OpenDirect.Activity1",
        "Relenishment.Activity10"
    ];

    public function events()
    {
        return [
            TaskInstance::EVENT_AFTER_START => "afterStart",
            //Process::EVENT_AFTER_ABORT => "afterAbort"
        ];
    }

    /**
     * 生成任务发送消息
     *
     * @param WorkItemEvent $event
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 18/4/26 00:50:53
     * @Author: fangxing@likingfit.com
     */
    public function afterStart(WorkItemEvent $event)
    {
        /**
         * @var $taskInstance TaskInstance
         */
        $taskInstance = $event->sender;
        if($taskInstance->getType() == Task::SUBFLOW){
            return;
        }
        $workItem = $event->workItem;
        $var = FlowService::getVariableBySeriesId($workItem->series_id, "IsMainRejoin");
        if(in_array($workItem->activity_id, $this->shouldShip)
            || ($var == "1" && $workItem->activity_id === "Main.Activity4")){
            return;
        }
        if($workItem["staff_id"] != UNAVAILABLE){
            $message = [
                'staff_id' => $workItem["staff_id"],
                'title' => $workItem['step_name'],
                'content' => $workItem['step_name'] . '任务已进入你的工作列表，请尽快完成',
                'message_type' => Message::TYPE_WORK_ITEM,
                'param' => json_encode([
                    'work_item_id' => $workItem['id']
                ])
            ];
            MessageService::save($message);
            MessageService::push($message);
        }else{
            $activityCfg = FlowService::getActivityInfoByCfg($workItem->series_id, $workItem->activity_id);
            $userIds = \Yii::$app->getAuthManager()->getUserIdsByRole($activityCfg["role_name"]);
            $messages = [];
            foreach ($userIds as $id) {
                $message = [
                    'staff_id' => $id,
                    'title' => $workItem['step_name'],
                    'content' => $workItem['step_name'] . '任务已进入你的工作列表，请尽快完成',
                    'message_type' => Message::TYPE_WORK_ITEM,
                    'param' => json_encode([
                        'work_item_id' => $workItem['id']
                    ])
                ];
                MessageService::push($message);
                array_push($messages, $message);
            }
            $model = new Message;
            $model->batchInsert($messages);
        }
    }

    /**
     * 关闭流程发送消息
     *
     * @param Event $event
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 18/4/9 23:20:47
     * @Author: fangxing@likingfit.com
     */
    public function afterAbort(Event $event)
    {
        /**
         * @var $process Process
         * @var
         */
        /*$process = $event->sender;
        $flow = FlowService::getProcessFlow($process->getId());

        $gymSeries = GymSeries::findBySeriesId($flow->series_id);

        if($gymSeries->series_type == GymSeries::APPEND){
            return;
        }
        //通知该流程下所有负责人
        $staff = ProjectDirector::getStaffInfoBySeriesId($flow->series_id);
        $messages = [];
        foreach ($staff as $v) {
            $message = [
                'staff_id' => $v['staff_id'],
                'title' => "取消开店",
                'content' => $gymSeries->openProject->gym_name . '已取消开店, 请知悉',
                'message_type' => Message::GYM
            ];
            MessageService::push($message);
            array_push($messages, $message);
        }
        $model = new Message;
        $model->batchInsert($messages);*/
    }

}