<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/23 11:27:33
 */

namespace app\behaviors;

use app\exceptions\AccessException;
use yii\base\Action;
use yii\filters\auth\AuthMethod;
use yii\helpers\StringHelper;

class CheckAccessBehavior extends AuthMethod
{
    public $optional = [
        "menu/get-menu",
        "flow/*",
        "staff/get-message",
        "staff/read-all-message",
        "staff/read-message",
        "index/get-work",
        "customer-statistics/num",
        "customer-statistics/customer",
        "index/gym-chart",
        "index/collect",
        "index/logout"
    ];

    /**
     * 验证权限
     *
     * @param \yii\web\User $user
     * @param \yii\web\Request $request
     * @param \yii\web\Response $response
     * @return null|\yii\web\IdentityInterface
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/23 12:44:00
     * @Author: fangxing@likingfit.com
     */
    public function authenticate($user, $request, $response)
    {
        /**
         * @var Action $action
         */
        $action = $this->owner->action;
        if($user->can($action->getUniqueId())){
            return $user->getIdentity();
        }
        return null;
    }

    public function isOptional($action)
    {
        $id = $action->getUniqueId();
        foreach ($this->optional as $pattern) {
            if (StringHelper::matchWildcard($pattern, $id)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param \yii\web\Response $response
     * @throws AccessException
     * @CreateTime 18/3/23 12:08:49
     * @Author: fangxing@likingfit.com
     */
    public function handleFailure($response)
    {
        throw new AccessException(AccessException::NO_ACCESS);
    }
}