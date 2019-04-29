<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_open_log".
 *
 * @property int $id
 * @property int $work_item_id
 * @property int $series_id
 * @property string $work_name_format
 * @property string $user_data
 * @property string user_name
 * @property string role_name
 * @property string remark
 * @property int $create_time
 * @property int $update_time
 */
class OpenLog extends Base
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_open_log';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['work_item_id', 'series_id'], 'required'],
            [['work_item_id', 'series_id', 'create_time', 'update_time'], 'integer'],
            [['work_name_format', 'user_data'], 'string', 'max' => 255],
            [['work_item_id'], 'unique'],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'work_item_id' => 'Work Item ID',
            'series_id' => 'Series ID',
            'work_name_format' => 'Work Name Format',
            'user_data' => 'User Data',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public function getWorkItem()
    {
        return $this->hasOne(WorkItem::class, ["id" => "work_item_id"]);
    }

    public function beforeSave($insert)
    {
        $time = time();
        if ($insert){
            $this->create_time = $time;
        }
        $this->update_time = $time;
        return parent::beforeSave($insert);
    }
}
