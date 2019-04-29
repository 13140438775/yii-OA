<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/2 11:09:35
 */

namespace app\behaviors;

use app\exceptions\LoginException;
use app\models\Staff;
use yii\filters\auth\HttpBearerAuth;

class CustomizeHttpBearerAuth extends HttpBearerAuth
{

    /**
     * 检测是否登录
     *
     * @param $user
     * @param $request
     * @param $response
     * @return bool|null|\yii\web\IdentityInterface
     * @throws LoginException
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/23 12:01:20
     * @Author: fangxing@likingfit.com
     */
    public function authenticate($user, $request, $response)
    {
        /**
         * @var Staff $identity
         */
        $identity = parent::authenticate($user, $request, $response);
        if($identity){
            if($identity->expire_time < time()){
                throw new LoginException(LoginException::LOGIN_INVALID);
            }
        }
        return $identity;
    }

    /**
     * @param $response
     * @throws LoginException
     * @CreateTime 18/3/2 11:16:59
     * @Author: fangxing@likingfit.com
     */
    public function handleFailure($response)
    {
        throw new LoginException(LoginException::UN_LOGIN);
    }
}
