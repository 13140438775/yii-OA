<?php

namespace app\controllers;

use app\exceptions\FlowException;
use app\exceptions\Param;
use app\exceptions\WorkItemException;
use app\models\DataStash;
use app\models\WorkItem;
use app\services\FlowService;

class FlowController extends BaseController
{
    /**
     * 侧边栏保存数据
     *
     * @throws FlowException
     * @throws Param
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/19 17:58:19
     * @Author: fangxing@likingfit.com
     */
    public function actionSave()
    {
        $postData = \Yii::$app->request->post();
        if (!isset($postData['work_item_id']) || empty($postData['work_item_id'])) {
            throw new Param(null, [
                'work_item_id' => \Yii::t("yii", "{attribute} cannot be blank.", ['attribute' => "Work Item Id"])
            ]);
        }
        $workItemId = $postData['work_item_id'];
        unset($postData['work_item_id']);

        $workItem = FlowService::getWorkItem($workItemId);
       /* if($workItem->state == WorkItem::COMPLETED){
            throw new WorkItemException;
        }*/
        //处理暂存数据
        if (isset($postData["stash"]) && $postData["stash"] == AVAILABLE) {
            unset($postData["command"]);
            DataStash::stashData($postData, $workItemId);
            return;
        }

        $workItem->remark = @$postData['remark'] ?: '';

        $customer = FlowService::getCustomerBySeriesId($workItem->series_id);
        $postData['flow'] = [
            'work_item_id' => $workItemId,
            "series_id" => $workItem->series_id,
            'flow_id' => $workItem->flow_id,
            'customer_id' => is_null($customer) ? 0 : $customer->id,
            'process_id' => $workItem->process_id
        ];
        unset($postData['remark']);
        if ($this->proxyService("save", $postData)) {
            //$workItem->staff_id = \Yii::$app->user->id;
            FlowService::completeWorkItem2($workItem);
        }
    }

    /**
     * 侧边栏初始化数据
     *
     * @return array
     * @throws FlowException
     * @throws Param
     * @throws \ReflectionException
     * @CreateTime 18/3/29 11:54:38
     * @Author: fangxing@likingfit.com
     */
    public function actionInit()
    {
        $postData = \Yii::$app->request->post();
        if (!isset($postData['work_item_id']) || empty($postData['work_item_id'])) {
            throw new Param(null, [
                'work_item_id' => \Yii::t("yii", "{attribute} cannot be blank.", ['attribute' => "Work Item Id"])
            ]);
        }
        $workItemId = $postData['work_item_id'];
        unset($postData['work_item_id']);
        $workItem = FlowService::getWorkItem($workItemId);
        $customer = FlowService::getCustomerBySeriesId($workItem->series_id);
        $postData['flow'] = [
            'work_item_id' => $workItemId,
            "series_id" => $workItem->series_id,
            'flow_id' => $workItem->flow_id,
            'customer_id' => is_null($customer) ? 0 : $customer->id,
            'process_id' => $workItem->process_id
        ];
        $nearlyWork = FlowService::getNearByWorkItem($workItem);
        $logic = $this->proxyService("init", $postData)?:[];
        $initData = DataStash::getStashData($workItemId);
        if ($initData) {
            $logic["init_data"] = $initData;
        }
        return [
            "logic" => $logic,
            "nearly" => $nearlyWork
        ];
    }

    /**
     * @param $type
     * @param $postData
     * @return mixed
     * @throws FlowException
     * @CreateTime 18/3/6 11:48:04
     * @Author: fangxing@likingfit.com
     */
    protected function proxyService($type, $postData)
    {
        $commands = \Yii::$app->params['commands'];
        if (!($command = @$postData['command']) || !($actionInfo = @$commands[$command])) {
            throw new FlowException(FlowException::INVALID_COMMAND);
        }
        if(!isset($actionInfo[$type])){
            return true;
        }
        $callInfo = [$actionInfo['class'], $actionInfo[$type]];
        if (is_callable($callInfo) && method_exists($actionInfo['class'], $actionInfo[$type])) {
            unset($postData['command']);
            return call_user_func($callInfo, $postData);
        }
        throw new FlowException(FlowException::INVALID_METHOD);
    }

}
