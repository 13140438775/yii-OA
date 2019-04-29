<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_project_director".
 *
 * @property int $id
 * @property int $flow_id 流程ID
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $department_id 部门ID
 * @property string $department_name 部门名称
 * @property string $staff_name 员工名称
 * @property int $staff_id 对接人ID
 * @property string $create_time 创建时间
 * @property int $customer_id 客户id
 */
class ProjectDirector extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_project_director';
    }

    public static function getStaffInfoBySeriesId($seriesId)
    {
        return self::find()
            ->joinWith("staff", false)
            ->where(['series_id' => $seriesId])
            ->select([
                "staff_name",
                "staff_id",
                self::tableName().".department_id",
                "phone",
                "email"
            ])
            ->indexBy("staff_id")
            ->asArray()
            ->all();
    }

    public function getStaff()
    {
        return $this->hasOne(Staff::class, ["id" => "staff_id"]);
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
            'id' => 'ID',
            'flow_id' => 'Flow ID',
            'series_id' => 'Series ID',
            'department_id' => 'Department ID',
            'department_name' => 'Department Name',
            'staff_name' => 'Staff Name',
            'staff_id' => 'Staff ID',
            'create_time' => 'Create Time',
            'customer_id' => 'Customer ID',
        ];
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ['id' => 'customer_id']);
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = time();
        }
        return true;
    }

}
