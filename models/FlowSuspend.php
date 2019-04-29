<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_flow_suspend".
 *
 * @property int $series_id 流程组标识,顶级流程id
 * @property string $process_id 工作流实例ID
 * @property string $activity_id 引擎Activity ID
 * @property int $is_valid 1 有效 0无效
 * @property int $series_status 1正常 2暂停
 * @property int $series_type 1主流程 2附属流程
 * @property string $create_time
 * @property string $update_time
 */
class FlowSuspend extends \yii\db\ActiveRecord
{
    const OK = 1;
    const SUSPEND = 2;
    const CLOSE = 3;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_flow_suspend';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['series_id', 'process_id', 'activity_id'], 'required'],
            [['series_id'], 'integer'],
            [['create_time', 'update_time'], 'safe'],
            [['process_id'], 'string', 'max' => 100],
            [['activity_id'], 'string', 'max' => 64],
            [['is_valid', 'series_status', 'series_type'], 'string', 'max' => 1],
            [['series_id', 'activity_id'], 'unique', 'targetAttribute' => ['series_id', 'activity_id']],*/
        ];
    }

    public function getGymSeries()
    {
        return $this->hasOne(GymSeries::class, ["series_id" => "series_id"]);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'series_id' => 'Series ID',
            'process_id' => 'Process ID',
            'activity_id' => 'Activity ID',
            'is_valid' => 'Is Valid',
            'series_status' => 'Series Status',
            'series_type' => 'Series Type',
            'create_time' => 'Create Time',
            'update_time' => 'Update Time',
        ];
    }
}
