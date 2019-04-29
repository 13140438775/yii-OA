<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_login_token".
 *
 * @property int $id
 * @property int $staff_id 员工id
 * @property string $access_token 授权token
 * @property int $expire_time 过期时间
 * @property int $token_status 是否有效
 * @property int $create_time
 */
class LoginToken extends Base
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_login_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
//        return [
//            [['staff_id', 'expire_time', 'create_time'], 'integer'],
//            [['access_token'], 'string', 'max' => 32],
//            [['token_status'], 'string', 'max' => 1],
//        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'staff_id' => 'Staff ID',
            'access_token' => 'Access Token',
            'expire_time' => 'Expire Time',
            'token_status' => 'Token Status',
            'create_time' => 'Create Time',
        ];
    }
}
