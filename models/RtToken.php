<?php
namespace app\models;

use yii\db\ActiveRecord;
class RtToken extends  ActiveRecord{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'T_FF_RT_TOKEN';
    }
}