<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_collection".
 *
 * @property int $id
 * @property int $type 1待办事项
 * @property int $work_item_id 1 customer_id 2 work_item_id
 * @property int $staff_id 员工id
 * @property int $relation_status 是否有效
 */
class Collection extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_collection';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['work_item_id', 'staff_id'], 'integer'],
            [['type', 'relation_status'], 'string', 'max' => 1],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'type' => 'Type',
            'work_item_id' => 'Work Item ID',
            'staff_id' => 'Staff ID',
            'relation_status' => 'Relation Status',
        ];
    }
}
