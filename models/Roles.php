<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_roles".
 *
 * @property int $id
 * @property string $role_name 角色名称
 * @property string $pid 父角色
 * @property string $display_name 角色名称
 */
class Roles extends Base
{
    const PROJECT_MANAGER = "project-manager";
    const CUSTOMER_MANAGER  = "customer-manager";
    const CUSTOMER_SPECIALIST = "customer-specialist";
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_roles';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['role_name'], 'required'],
            [['role_name', 'pid', 'display_name'], 'string', 'max' => 32],
            [['role_name'], 'unique'],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'role_name' => 'Role Name',
            'pid' => 'Pid',
            'display_name' => 'Display Name',
        ];
    }
}
