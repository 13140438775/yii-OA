<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_rightside_config".
 *
 * @property int $id
 * @property string $activity_id activity id
 * @property string $displayName 显示名称
 * @property string $page 前端页面名称
 * @property string $role_name 角色名称
 * @property string $user_data 初始化用户数据
 * @property string $create_time
 */
class RightSideConfig extends Base
{
    public static function primaryKey()
    {
        return ["activity_id"];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_rightside_config';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['create_time'], 'safe'],
            [['activity_id', 'displayName'], 'string', 'max' => 32],
            [['page', 'role_name'], 'string', 'max' => 16],
            [['user_data'], 'string', 'max' => 255],
            [['activity_id'], 'unique'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'activity_id' => 'Activity ID',
            'displayName' => 'Display Name',
            'page' => 'Page',
            'role_name' => 'Role Name',
            'user_data' => 'User Data',
            'create_time' => 'Create Time',
        ];
    }

    public static function getCfgByActivity($activityId)
    {
        return static::findOne($activityId);
    }
}
