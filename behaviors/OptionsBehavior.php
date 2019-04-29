<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/23 11:27:33
 */

namespace app\behaviors;


use yii\base\ActionEvent;
use yii\base\Behavior;
use yii\base\Controller;

class OptionsBehavior extends Behavior
{
    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction'
        ];
    }

    public function beforeAction(ActionEvent $event)
    {
        if(\Yii::$app->request->method == "OPTIONS"){
            $event->isValid = false;
            $event->handled = true;
        }
    }
}