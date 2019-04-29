<?php

namespace app\controllers;

use app\behaviors\CheckAccessBehavior;
use app\behaviors\CheckParametersBehaviors;
use app\behaviors\CustomizeHttpBearerAuth;
use app\behaviors\OptionsBehavior;
use app\behaviors\ValidateParameter;
use yii\base\Controller;

class BaseController extends Controller
{
    public function behaviors()
    {
        return [
            "options" => OptionsBehavior::class,
            "checkParameter" => CheckParametersBehaviors::class,
            'validateParameter' => ValidateParameter::class,
            'authenticator' => CustomizeHttpBearerAuth::class,
            'access' => CheckAccessBehavior::class
        ];
    }
}
