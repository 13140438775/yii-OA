<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_work_sequence".
 *
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $work_item_id t_work_item表id
 */
class WorkSequence extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_work_sequence';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['series_id', 'work_item_id'], 'required'],
            [['series_id', 'work_item_id'], 'integer'],
            [['series_id'], 'unique'],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'series_id' => 'Series ID',
            'work_item_id' => 'Work Item ID',
        ];
    }

    /**
     * 存入上一步work_item_id
     *
     * @param $data
     * @throws \yii\db\Exception
     * @CreateTime 18/3/17 18:12:13
     * @Author: fangxing@likingfit.com
     */
    public function replace($data)
    {
        $sql = 'REPLACE INTO ' . static::tableName() .' (series_id, work_item_id) VALUE (:series_id, :work_item_id)';
        static::getDb()->createCommand($sql, $data)->execute();
    }
}
