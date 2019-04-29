<?php

namespace app\models;


/**
 * This is the model class for table "t_work_item".
 *
 * @property integer $id
 * @property integer $staff_id
 * @property integer $flow_id
 * @property integer $series_id
 * @property string $name
 * @property integer $state
 * @property string $page
 * @property string $deal_date
 * @property string $work_item_id
 * @property string step_name
 * @property string $activity_id
 * @property string $process_id
 * @property string complete_time
 * @property string $remark
 */
class WorkItem extends Base
{
    const INITIALIZED = 1;
    const CLAIMED = 2;
    const COMPLETED = 3;
    const CANCELED = 4;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_work_item';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            // [['staff_id', 'name', 'state', 'page', 'deal_date', 'work_item_id', 'process_id', 'remark'], 'required'],
            // [['staff_id', 'state'], 'integer'],
            // [['name'], 'string', 'max' => 32],
            // [['page', 'remark'], 'string', 'max' => 256],
            // [['deal_date'], 'string', 'max' => 8],
            // [['work_item_id', 'process_id'], 'string', 'max' => 64],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'staff_id' => 'Staff ID',
            'name' => 'Name',
            'state' => 'State',
            'page' => 'Page',
            'deal_date' => 'Deal Date',
            'work_item_id' => 'Work Item ID',
            'process_id' => 'Process ID',
            'remark' => 'Remark',
        ];
    }

    public function beforeSave($insert)
    {
        $datetime = date("Y-m-d H:i:s");
        if ($insert) {
            $this->create_time = $datetime;
        }
        $this->update_time = $datetime;
        return parent::beforeSave($insert);
    }

    public function getFlow(){
        return $this->hasOne(Flow::class, ['id' => 'flow_id']);
    }
    
    public function getStaff(){
    	return $this->hasOne(Staff::class,['id' => 'staff_id']);
    }

    public function getRightSide()
    {
        return $this->hasOne(RightSideConfig::class, ["activity_id" => "activity_id"]);
    }

    public function getOpenProject()
    {
        return $this->hasOne(OpenProject::class, ["series_id" => "series_id"]);
    }

    public function getOpenContract()
    {
        return $this->hasOne(OpenContract::class, ["series_id" => "series_id"]);
    }

    public function getAppendantSeries()
    {
        return $this->hasOne(GymSeries::class, ["series_id" => "series_id"])
            ->joinWith(["openProject.openContract", "customer"], false);
    }

    public function getCollection()
    {
        return $this->hasOne(Collection::class, ["work_item_id" => "id"]);
    }
}
