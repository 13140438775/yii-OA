<?php

namespace app\behaviors;

use Yii;
use yii\base\Behavior;
use yii\helpers\ArrayHelper;
use yii\web\Response;
use app\data\ActiveDataProvider;

class ResponseFilter extends Behavior
{
    public $successCode = 0;
    public $successMsg = 'success';

    public function events()
    {
        return array_merge(
            parent::events(), [
                Response::EVENT_BEFORE_SEND => 'eventBeforeSend',
            ]
        );
    }

    public function eventBeforeSend()
    {
        $data = Yii::$app->response->data;

        if ($data instanceof ActiveDataProvider) {
            $data = $this->authFilter($data);
        }

        if (isset($data['err_code']) && isset($data['err_msg'])) {
            return;
        }

        Yii::$app->response->data = [
            'err_code' => $this->successCode,
            'err_msg'  => $this->successMsg,
            'data'     => is_array($data) ? $data : (object)[],
        ];
    }

    public function authFilter(ActiveDataProvider $data)
    {
        // TODO
        //        $id   = Yii::$app->controller->action->getUniqueId();
        //        $role = 'jue';
        //        $select = ArrayHelper::getValue(Yii::$app->params, "rules.$id.$role.select", []);
        //        $data->query->select($select);

        return ['list' => $data->getModels(), 'count' => (int)$data->getCount()];
    }
}