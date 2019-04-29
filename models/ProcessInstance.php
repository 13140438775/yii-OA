<?php

namespace app\models;

class ProcessInstance extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'T_FF_RT_PROCESSINSTANCE';
    }
}
