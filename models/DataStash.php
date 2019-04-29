<?php

namespace app\models;

use Yii;
use yii\helpers\Json;

/**
 * This is the model class for table "t_data_stash".
 *
 * @property int $id
 * @property int $work_item_id 工作事项id
 * @property string $data_text 暂存数据
 * @property int $is_valid 是否有效 1有效 0无效
 * @property int $create_time
 * @property int $update_time
 */
class DataStash extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_data_stash';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['work_item_id', 'create_time', 'update_time'], 'integer'],
            [['data_text'], 'required'],
            [['data_text'], 'string'],
            [['is_valid'], 'string', 'max' => 1],
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
            'data_text' => 'Data Text',
            'is_valid' => 'Is Valid',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public static function stashData($data, $work_item_id)
    {
        $model = static::findOne([
            "work_item_id" => $work_item_id,
            "is_valid" => AVAILABLE
        ]);
        if($model == null){
            $model = new static;
        }
        $model->setAttributes([
            "work_item_id" => $work_item_id,
            "data_text" => json_encode($data)
        ], false);
        $model->save(false);
    }

    public static function getStashData($work_item_id)
    {
        $dataStash = static::findOne([
            "work_item_id" => $work_item_id,
            "is_valid" => AVAILABLE
        ]);
        if($dataStash == null){
            return null;
        }
        return Json::decode($dataStash->getAttribute("data_text"), true);
    }

    public static function disabled($work_item_id)
    {
        static::updateAll(["is_valid" => UNAVAILABLE], compact("work_item_id"));
    }

    public function beforeSave($insert)
    {
        $time = time();
        if($insert){
            $this->create_time = $time;
        }
        $this->update_time = $time;
        return parent::beforeSave($insert);
    }
}
