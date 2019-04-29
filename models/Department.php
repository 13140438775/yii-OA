<?php

namespace app\models;

use Yii;

/**
 * Class Department
 * @package app\models
 * @property string $name
 * @CreateTime 18/3/23 10:56:04
 * @Author: fangxing@likingfit.com
 */
class Department extends Base
{
    const PROJECT = 10;

    const PURCHASE = 7;

    const FINANCIAL = 6;

    const DEPARTMENT_STATUS = 1;
    /**
     * 流程人员类型
     * @var int
     */
    public static $flowStaffType = 8;

    /**
     * 财务人员类型
     * @var int
     */
    public static $financeStaffType = 6;

    /**
     * 项目人员类型
     * @var int
     */
    public static $projectStaffType = 10;
    
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_department';
    }

    public function getStaff(){
        return $this->hasMany(Staff::class, ['department_id'=>'id']);
    }

    public static function getDepartment($params)
    {
        $parentId = self::find()
            ->select("id")
            ->where(['name' => $params])
            ->one();
        return self::find()
            ->select("id")
            ->where(['department_status' => self::DEPARTMENT_STATUS])
            ->andWhere(['parent_id' => $parentId])
            ->asArray()
            ->all();
    }

    public function getDepartmentCity(){
        return $this->hasMany(DepartmentCity::class, ['department_id'=>'id']);
    }

    public static function getRelationDepartmentsById($id)
    {
        $departments = static::find()->all();
        return static::getParents($departments, $id);

    }

    public static function getTopDepartment($id)
    {
        return static::getRelationDepartmentsById($id)[0];
    }

    public static function getParents($departments, $id)
    {
        $arr = [];
        foreach ($departments as $department){
            if($department->id == $id){
                $arr = array_merge($arr, static::getParents($departments, $department->parent_id));
                $arr[] = $department;
            }
        }
        return $arr;
    }
}