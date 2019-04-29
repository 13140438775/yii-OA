<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/8 17:28:42
 */

namespace app\services;

use app\models\Customer;
use app\models\Department;
use app\models\OpenContract;
use app\models\ProjectDirector;
use app\models\Staff;
use yii\db\ActiveQuery;

class WorkflowStaffService
{
    /**
     * 侧边栏-指定流程专员
     * @param $request
     * @return array
     * @CreateTime 2018/3/6 18:45:25
     * @Author     : pb@likingfit.com
     */
    public static function recordFlowStaffInit($request)
    {
        $staffs = WorkflowService::getDepartmentStaffs(Department::$flowStaffType, 0);
        return ['staffs' => $staffs];
    }

    /**
     * 侧边栏-指定流程专员/财务专员／项目专员
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 17:46:27
     * @Author     : pb@likingfit.com
     */
    public static function recordStaffSave($request)
    {
        $staff = Staff::find()->where(['id' => $request['staff_id']])->one();

        $roleName = \Yii::$app->authManager->getRolesByUser($request['staff_id']);
        if(!empty($roleName)){
            $roleName = key($roleName);
        }else{
            $roleName = '';
        }

        $projectDirector                  = new ProjectDirector();
        $projectDirector->series_id       = $request['flow']['series_id'];
        $projectDirector->flow_id         = $request['flow']["flow_id"];
        $projectDirector->department_id   = $staff->department_id;
        $projectDirector->department_name = $staff->getDepartment()->one()->name;
        $projectDirector->staff_name      = $staff->name;
        $projectDirector->staff_id        = $request['staff_id'];
        $projectDirector->customer_id     = $request['flow']['customer_id'];
        $projectDirector->role_name       = $roleName;
        return $projectDirector->save();
    }

    /**
     * 侧边栏-指定财务专员
     * @param $request
     * @return Department[]|\app\models\PayTask[]|ProjectDirector[]|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/8 18:01:30
     * @Author     : pb@likingfit.com
     */
    public static function recordFinanceStaffInit($request)
    {
        return ['staffs' => WorkflowService::getDepartmentStaffs(Department::$financeStaffType, 0)];
    }

    /**
     * 侧边栏-指定项目专员
     * @param $request
     * @return Department[]|\app\models\PayTask[]|ProjectDirector[]|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/8 18:37:58
     * @Author     : pb@likingfit.com
     */
    public static function recordProjectStaffInit($request)
    {
        $customer = Customer::find()
            ->select(['name','phone', 'province_name', 'city_name', 'district_name', 'address'])
            ->where(['id' => $request['flow']['customer_id']])
            ->asArray()
            ->one();
        if(is_null($customer)) {
            $customer = [];
        }

        $staffs = WorkflowService::getDepartmentStaffs(Department::$projectStaffType, 0);

        return ['customer' => $customer, 'staffs' => $staffs];
    }
}