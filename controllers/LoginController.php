<?php

namespace app\controllers;


use app\behaviors\CheckParametersBehaviors;
use app\behaviors\ValidateParameter;
use app\exceptions\LoginException;
use app\services\LoginService;
use yii\base\Controller;

class LoginController extends Controller
{
    public function behaviors()
    {
        return [
            "checkParameter" => CheckParametersBehaviors::class,
            "validateParameter" => ValidateParameter::class
        ];
    }

    /**
     * X-CSRF-Token EH0eSddR7Y3ma3bfMh3xLDeowO
     * _csrf EH0eSddR7Y3ma3bfMh3xLDeowO
     *
     * @CreateTime 18/3/1 13:53:11
     * @Author: fangxing@likingfit.com
     */
    public function actionGetToken(){
        $request = \Yii::$app->request;
        return ["token" => $request->getCsrfToken(true)];
    }

    /**
     * Authorization: Bearer EH0eSddR7Y3ma3bfMh3xLDeowO
     *
     * @return array
     * @throws LoginException
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 14:01:11
     * @Author: fangxing@likingfit.com
     */
    public function actionLogin(){
        $request = \Yii::$app->request;
        $requestParams = $request->post();
        if($request->validateCsrfToken()){
           return LoginService::login($requestParams['email'], $requestParams['password']);
        }

        throw new LoginException(LoginException::ERR_TOKEN);
    }

    public function actionSendCode()
    {
        $data = \Yii::$app->request->post();
        return LoginService::sendCode($data);
    }

    public function actionSavePassword()
    {
        $data = \Yii::$app->request->post();
        return LoginService::savePassword($data);
    }

    public function actionCheckPhone()
    {
        $data = \Yii::$app->request->post();
        return LoginService::checkPhone($data);
    }
}
