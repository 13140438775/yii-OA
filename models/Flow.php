<?php

namespace app\models;

use app\models\OpenFlow;
use app\models\OpenProject;

/**
 * This is the model class for table "t_flow".
 *
 * @property integer $id
 * @property string $process_id
 * @property string $name
 * @property integer $flow_status
 * @property integer $flow_type
 * @property integer $series_id
 * @property integer $level
 * @property integer $creator_id
 * @property string $create_time
 * @property string $update_time
 */
class Flow extends \yii\db\ActiveRecord
{
    const INITIALIZED = 1;
    const RUNNING = 2;
    const COMPLETE = 3;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_flow';
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = date('Y-m-d H:i:s');
        }
        $this->update_time = date('Y-m-d H:i:s');
        return true;
    }
    
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // [['id', 'process_id', 'name', 'flow_status', 'creator_id', 'create_time', 'update_time'], 'required'],
            // [['id', 'flow_status', 'creator_id'], 'integer'],
            // [['process_id'], 'string', 'max' => 100],
            // [['name'], 'string', 'max' => 32],
            // [['create_time', 'update_time'], 'string', 'max' => 19],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'process_id' => 'Process ID',
            'name' => 'Name',
            'flow_status' => 'Flow Status',
            'creator_id' => 'Creator ID',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }

    public function getOpenProject () {

        return $this->hasOne(OpenProject::className(), ['series_id' => 'series_id'])->asArray();
    }

    public static function getProcessId($seriesId){
        return self::find()
            ->where(['series_id' => $seriesId])
            ->select("process_id")
            ->asArray()
            ->column();
    }
}
