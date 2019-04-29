<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/4/20 15:14:22
 */

namespace app\services;

use Yii;

class RoleService {
    public static $role;

    public $userId;
    public $userRoles;
    public $currentRoleName = '';

    // 选址组长
    const SELECTION_GROUPER = 'selection-leader';
    // 选址专员
    const SELECTION_STAFF = 'selection-specialist';

    // 招商经理
    const MERCHANTS_MANAGER = 'merchants-manager';
    // 招商专员
    const MERCHANTS_STAFF = 'merchants-specialist';

    public function __construct()
    {
        $this->userId = Yii::$app->user->getId();
        $this->userRoles = Yii::$app->authManager->getRolesByUser($this->userId);
        // 用户角色1对1
        if(!empty($this->userRoles)){
            $this->currentRoleName = key($this->userRoles);
        }
    }

    public static function getInstance(){
        if (!(self::$role instanceof self)){
            self::$role = new self();
        }

        return self::$role;
    }

    /**
     * 检测当前角色
     * @param $roleName
     * @return bool
     * @CreateTime 2018/4/20 15:30:42
     * @Author     : pb@likingfit.com
     */
    public function checkRole($roleName){
        return $roleName == $this->currentRoleName;
    }
}