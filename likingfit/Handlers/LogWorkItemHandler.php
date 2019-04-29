<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/24 10:12:43
 */

namespace likingfit\Handlers;


use app\models\OpenLog;
use app\services\FlowService;
use likingfit\Events\WorkItemEvent;
use likingfit\Workflow\Base\TaskInstance;

class LogWorkItemHandler implements Handler
{
    public function events()
    {
        return [
            TaskInstance::EVENT_AFTER_COMPLETE => "log"
        ];
    }

    /**
     * 记录日志
     *
     * @param WorkItemEvent $event
     * @throws \Throwable
     * @CreateTime 18/4/24 10:16:46
     * @Author: fangxing@likingfit.com
     */
    public function log(WorkItemEvent $event)
    {
        $workItem = $event->workItem;
        $openLog = OpenLog::findOne(["work_item_id" => $workItem->id]);
        if($openLog === null){
            $openLog = FlowService::recordOpenLog($workItem->id);
            $openLog->work_name_format = $workItem->step_name;
        }
        $openLog->series_id = $workItem->series_id;
        $openLog->remark = $workItem->remark;
        $openLog->save();
    }
}