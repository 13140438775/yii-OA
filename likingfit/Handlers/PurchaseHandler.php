<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/8 17:25:24
 */

namespace likingfit\Handlers;


use app\models\OpenFlow;
use likingfit\Events\WorkItemEvent;
use likingfit\Workflow\Base\Task;
use likingfit\Workflow\Base\TaskInstance;

class PurchaseHandler implements Handler
{
    public $beforeActivityMapPurchase = [];

    public $afterActivityMapPurchase = [
        "Main.Activity6" => 2,
        "OpenDirect.Activity1" => 2,
        "Main.Activity8" => 3,
        "ConstructMaterials.Activity5" => 3,
        "Order.Activity16" => 4,
        "TaskFinishWork.Activity2" => 4,
        "Order.Activity8" => 5,
        "Main.Activity28" => 5,
        "Main.Activity17" => 6,
        "OpenDirect.Activity29" => 6
    ];

    public function events()
    {
        return [
            TaskInstance::EVENT_AFTER_START => "afterStart",
            TaskInstance::EVENT_AFTER_COMPLETE => "afterComplete"
        ];
    }

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
        $activityId = $workItem->activity_id;
        foreach ($this->beforeActivityMapPurchase as $key => $purchase){
            if($key == $activityId){
                OpenFlow::updateAll(["purchase" => $purchase], ["series_id" => $workItem->series_id]);
                break;
            }
        }
    }

    public function afterComplete(WorkItemEvent $event)
    {
        $workItem = $event->workItem;
        $activityId = $workItem->activity_id;
        foreach ($this->afterActivityMapPurchase as $key => $purchase){
            if($key == $activityId){
                OpenFlow::updateAll(["purchase" => $purchase], ["series_id" => $workItem->series_id]);
                break;
            }
        }
    }
}