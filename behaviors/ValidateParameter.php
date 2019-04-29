<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 下午4:01
 */

namespace app\behaviors;

use Yii;
use app\exceptions\Param;
use yii\base\Behavior;
use yii\base\Controller;
use yii\base\DynamicModel;
use yii\helpers\ArrayHelper;

class ValidateParameter extends Behavior
{
    private $_validateKey = [];

    public function events()
    {
        return [
            Controller::EVENT_BEFORE_ACTION => 'beforeAction'
        ];
    }

    /**
     * @param $event
     * @throws Param
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 2018/3/20 14:28:03
     * @Author     : pb@likingfit.com
     */
    public function beforeAction($event)
    {
        $post = Yii::$app->request->post();

        $id    = rtrim($event->action->getUniqueId() . '/' . @$post['command'], "/");
        $rules = ArrayHelper::getValue(Yii::$app->params, "rules.{$id}.rules", []);

        $this->setValidateKey($rules);
        $this->setValidateVal($post);

        $DynamicModel = DynamicModel::validateData($this->_validateKey, $rules);
        if ($DynamicModel->hasErrors()) {
            $event->isValid = false;
            $event->handled = true;
            throw new Param(null, $DynamicModel->getFirstErrors());
        }

        Yii::$app->request->setBodyParams(array_merge($post, $DynamicModel->getAttributes()));
    }

    public function setValidateKey($rules)
    {
        foreach ($rules as $rule) {
            if (is_array($rule[0])) {
                foreach ($rule[0] as $v) {
                    $this->_validateKey[$v] = '';
                }
                continue;
            }

            $this->_validateKey[$rule[0]] = '';
        }
    }

    public function setValidateVal($post)
    {
        foreach ($this->_validateKey as $k => $v) {
            if (isset($post[$k])) {
                $this->_validateKey[$k] = $post[$k];
            }
        }
    }
}