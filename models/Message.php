<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_message".
 *
 * @property integer $id
 * @property integer $staff_id
 * @property string $title
 * @property string $content
 * @property string $param
 * @property integer $read_status
 * @property string $create_time
 */
class Message extends Base
{
    const READ = 1;
    const UNREAD = 0;
    
    const TYPE_WORK_ITEM = 1;

    const GYM = 2;

    const ORDER = 3;
    const PURCHASE = 5;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_message';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
//            [['id', 'staff_id', 'title', 'content', 'param', 'create_time'], 'required'],
//            [['id', 'staff_id', 'read_status'], 'integer'],
//            [['title'], 'string', 'max' => 32],
//            [['content', 'param'], 'string', 'max' => 256],
//            [['create_time'], 'string', 'max' => 19],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'staff_id' => '员工ID',
            'title' => '标题',
            'content' => '内容',
            'param' => '参数 JSON格式',
            'read_status' => '是否已读',
            'create_time' => '创建时间',
        ];
    }
}
