<?php
/**
 * Class ProcinstVar
 * @package app\models
 * @CreateTime 18/3/22 16:35:31
 * @Author: fangxing@likingfit.com
 */
namespace app\models;

/**
 * @property string NAME
 * @property string VALUE
 */
class ProcinstVar extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'T_FF_RT_PROCINST_VAR';
    }

    public function getFlow()
    {
        return $this->hasOne(Flow::class, ["process_id" => "PROCESSINSTANCE_ID"]);
    }
}
