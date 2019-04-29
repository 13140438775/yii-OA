<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/8 16:34:29
 */

namespace likingfit\Events;


use likingfit\Handlers\FilterAuxiliaryNodesHandler;
use likingfit\Handlers\Handler;
use likingfit\Handlers\LogWorkItemHandler;
use likingfit\Handlers\MessageHandler;
use likingfit\Handlers\PrepareOpenHandler;
use likingfit\Handlers\PurchaseHandler;
use likingfit\Handlers\ReplenishOrderHandler;
use likingfit\Handlers\SetVarHandler;
use likingfit\Workflow\Base\Activity;
use likingfit\Workflow\Base\Process;
use likingfit\Workflow\Base\TaskInstance;
use yii\base\BootstrapInterface;
use yii\base\Event;

class EventListener implements BootstrapInterface
{
    /**
     * 注册事件
     *
     * @param \yii\base\Application $app
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/8 22:56:26
     * @Author: fangxing@likingfit.com
     */
    public function bootstrap($app)
    {
        $this->registerEvents();
    }

    /**
     * 注册事件
     *
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/8 22:28:00
     * @Author: fangxing@likingfit.com
     */
    public function registerEvents()
    {
        foreach ($this->events() as $scope => $handlers) {
            foreach ($handlers as $handler) {
                /**
                 * @var $instance Handler
                 */
                $type = $handler;
                $data = null;
                $append = true;
                if (is_array($handler)) {
                    $type = $handler["type"];
                    $data = isset($handler["data"]) ? $handler["data"] : null;
                    $append = isset($handler["append"]) ? $handler["append"] : true;
                }
                $instance = \Yii::createObject($type);
                foreach ($instance->events() as $name => $action) {
                    Event::on($scope, $name, [$instance, $action], $data, $append);
                }
            }
        }
    }

    public function events()
    {
        return [
            TaskInstance::class => [
                FilterAuxiliaryNodesHandler::class,
                MessageHandler::class, //消息发送
                PurchaseHandler::class, //阶段状态更新
                LogWorkItemHandler::class //记录日志
            ],
            Process::class => [
                SetVarHandler::class,
                MessageHandler::class //消息发送
            ],
            Activity::class => [
                ReplenishOrderHandler::class,
                PrepareOpenHandler::class
            ]
        ];
    }
}