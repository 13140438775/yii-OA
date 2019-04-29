<?php

namespace app\controllers;

use Yii;
use app\models\User;

class UserController extends BaseController
{
    /**
     * 获取用户信息
     *
     * @return \app\models\User|array|null|\yii\db\ActiveRecord
     */
    public function actionGet()
    {
        $userId = Yii::$app->getRequest()
            ->post('user_id');
        $user   = User::get($userId);

        return $user;
    }

    /**
     * api 文件下载
     */
    public function actionDown()
    {
        return [
            // 文件路径
            'filePath'      => '/Users/pb/Work/comma-OA/README.md',
            // 文件名称
            'attachmentNae' => '1.md',
        ];
    }
}