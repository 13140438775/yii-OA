<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/3/1
 * Time: 下午1:23
 */

namespace app\services;

use app\exceptions\LoginException;
use app\exceptions\Param;
use app\helpers\Helper;
use app\models\Department;
use app\models\Roles;
use app\models\Staff;
use app\models\Base;

class LoginService
{
    /**
     * @param $email
     * @param $password
     * @return array
     * @throws Param
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\base\Exception
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 14:00:12
     * @Author: fangxing@likingfit.com
     */
    public static function login($email, $password)
    {
        $errors = [];
        $staff = new Staff;
        $staff->email = $email;
        $staff->password = $password;
        if($staff->login($errors)){
            /**
             * @var $user Staff
             */
            $user = $staff->getUser();
            $department = Department::findOne($user->department_id);
            $authManager = \Yii::$app->authManager;
            $permissions = $authManager->getPermissionsByUser($user->id);
            $authArr = [];
            foreach ($permissions as $permission) {
                if (strpos($permission->name, "/") !== false) {
                    array_push($authArr, $permission->name);
                }
            }
            $role_name = key($authManager->getRolesByUser($user->id));
            $role = Roles::findOne(["role_name" => $role_name]);
            return [
                'token' => $user->access_token,
                "staff" => [
                    "id" => $user->id,
                    "name" => $user->name,
                    "role" => $role->display_name,
                    "is_leader" => $user->is_leader,
                    "department" => $department ? $department->name : '',
                    "department_id" => $user->department_id,
                    "index_page" => $user->show_index
                ],
                "permissions" => $authArr
            ];
        }
        throw new Param(null, $errors);
    }

    public static function logout()
    {
        $id = \Yii::$app->getUser()->getId();
        Staff::updateAll(["access_token" => "", "expire_time" => 0], ["id" => $id]);
    }

    public static function sendCode($data)
    {
        $staff_info = Staff::findOne(['phone' => $data['phone']]);
        if (!$staff_info) {
            throw new LoginException(LoginException::STAFF_NO_EXIST);
        }
        if(!$staff_info->staff_status) {
            throw new LoginException(LoginException::NO_VALID);
        }
        // 发送短信验证码
        $rand = (string)mt_rand(100000, 999999);
        Helper::sendSms($data['phone'], SMS_VCODE, [$rand]);

        $redis = \Yii::$app->redis;
        $redis->SET($data['phone'], $rand);
        $redis->EXPIRE($data['phone'], Staff::$expire_time);
        return true;
    }

    public static function savePassword($data)
    {
        $staff_info = Staff::findOne(['phone' => $data['phone']]);
        if (!$staff_info) {
            throw new LoginException(LoginException::STAFF_NO_EXIST);
        }
        if(!$staff_info->staff_status) {
            throw new LoginException(LoginException::NO_VALID);
        }
        $redis = \Yii::$app->redis;
        $message_code = $redis->GET($data['phone']);
        if ($message_code != $data['password']){
            throw new LoginException(LoginException::STAFF_NO_EXIST);
        }

        $rand = mt_rand(100000, 999999);
        $_password = Staff::PASSWORD.$rand;
        $password = md5(md5($_password).$staff_info->email);

        $staff_info->password = $password;
        $staff_info->save();

        //发送短信密码
        Helper::sendSms($staff_info->phone, SMS_PASSWORD, [$_password]);
    }

    public static function checkPhone($data){
        $staff_info = Staff::findOne(['phone' => $data['phone']]);
        return $staff_info ? ['status' => 1]: ['status' => 0];
    }
}