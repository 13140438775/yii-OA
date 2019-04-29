<?php
namespace app\models;

use yii\db\ActiveRecord;
class TaskInstance extends  ActiveRecord{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'T_FF_RT_TASKINSTANCE';
    }
}