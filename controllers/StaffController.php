<?php

namespace app\controllers;

use app\services\WorkflowService;
use Yii;
use app\services\MessageService;
use app\services\StaffService;
use app\services\RegionService;

class StaffController extends BaseController
{
    /**
     * 团队列表
     * @return array
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/19 18:08:47
     * @Author: heyafei@likingfit.com
     */
    public function actionInvestmentManager()
    {
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $department_ids = StaffService::getDepartment($department_id);
        array_push($department_ids, $department_id);
        $data = \Yii::$app->request->post();
        $page = isset($data['page']) ? $data['page']: 1;
        $pagesize = isset($data['pagesize']) ? $data['pagesize']: 15;
        $staff_name = isset($data['staff_name']) ? $data['staff_name']: '';
        $params = [
            'and',
            ['t_staff.department_id' => $department_ids]
        ];
        if(preg_match("/^1[34578]{1}\d{9}$/",$staff_name)){  
            $params[1]['t_staff.phone'] = $staff_name;
        }else{  
            $params[] = ['like', 't_staff.name', $staff_name];
        }
        return StaffService::investmentManager($params, $page, $pagesize);
    }

    /**
     * 新增员工
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/19 16:08:22
     * @Author: heyafei@likingfit.com
     */
    public function actionAddStaff()
    {
        $data = \Yii::$app->request->post();
        return StaffService::addStaff($data);
    }

    /**
     * 编辑成员
     */
    public function actionEditStaff()
    {
        $data = \Yii::$app->request->post();
        return StaffService::editStaff($data);
    }

    /**
     * 员工信息
     * @return mixed
     * @CreateTime 2018/3/19 16:08:28
     * @Author: heyafei@likingfit.com
     */
    public function actionStaffInfo()
    {
        $data = \Yii::$app->request->post();
        return StaffService::staffInfo($data['staff_id']);
    }

    /**
     * @throws \Exception
     * @throws \Throwable
     * @throws \app\exceptions\StaffException
     * @CreateTime 2018/3/19 18:23:15
     * @Author: heyafei@likingfit.com
     */
    public function actionAddDepartment()
    {
        $data = \Yii::$app->request->post();
        return StaffService::addDepartment($data);
    }

    /**
     *  编辑组
     */
    public function actionEditDepartment()
    {
        $data = \Yii::$app->request->post();
        return StaffService::editDepartment($data);
    }

    /**
     * 组列表
     */
    public function actionGroupList()
    {
        return StaffService::groupList();
    }


    /**
     * 角色列表
     */
    public function actionGetRoles()
    {
        return StaffService::getRoles();
    }

    /**
     * 员工列表
     */
    public function actionStaffList()
    {
        $data = \Yii::$app->request->post();
        return StaffService::staffList($data['type']);
    }

    /**
     * 获取未读消息
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 16:55:34
     * @Author: fangxing@likingfit.com
     */
    public function actionGetMessage()
    {
        $param = [
            'staff_id' => Yii::$app->user->getId(),
            'read_status' => UNAVAILABLE
        ];
        return MessageService::getList($param);
    }

    /**
     * 置为已读
     *
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 17:01:44
     * @Author: fangxing@likingfit.com
     */
    public function actionReadMessage(){
        $messageId = Yii::$app->request->post('message_id');
        $message = [
            'id' => $messageId,
            'read_status' => AVAILABLE
        ];
        MessageService::save($message);
    }

    /**
     * 全部置为已读
     *
     * @CreateTime 18/3/23 17:02:59
     * @Author: fangxing@likingfit.com
     */
    public function actionReadAllMessage(){
        MessageService::readAll(Yii::$app->user->getId());
    }

    /**
     * 组员工
     */
    public function actionGroupStaff()
    {
        return StaffService::groupStaff();
    }

    public function actionDepartmentInfo()
    {
        $data = \Yii::$app->request->post();
        return StaffService::departmentInfo($data['department_id']);
    }

    /**
     * 选址列表
     */
    public function actionSelectionList()
    {
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $department_ids = StaffService::getDepartment($department_id);
        array_push($department_ids, $department_id);
        $data = \Yii::$app->request->post();
        $page = isset($data['page']) ? $data['page']: 1;
        $pagesize = isset($data['pagesize']) ? $data['pagesize']: 15;
        $staff_name = isset($data['staff_name']) ? $data['staff_name']: '';
        $params = [
            'and',
            ['t_staff.department_id' => $department_ids]
        ];
        if(preg_match("/^1[34578]{1}\d{9}$/",$staff_name)){  
            $params[1]['t_staff.phone'] = $staff_name;
        }else{  
            $params[] = ['like', 't_staff.name', $staff_name];
        }
        return StaffService::selectionList($params, $page, $pagesize);
    }

    /**
     * 组所负责的城市
     */
    public function actionCityList()
    {
        $data = \Yii::$app->request->post();
        return RegionService::departmentCity($data['city_name']);
    }

    /**
     * 获取部门下员工
     * @return \app\models\Department[]|\app\models\PayTask[]|\app\models\ProjectDirector[]|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/4/10 16:35:35
     * @Author     : pb@likingfit.com
     */
    public function actionList(){
        $departmentId = \Yii::$app->request->post('department_id');
        return WorkflowService::getDepartmentStaffs($departmentId, 0);
    }

    /**
     * 组所负责的城市列表
     */
    public function actionDepartmentCity()
    {
        $data = \Yii::$app->request->post();
        return StaffService::departmentCity($data);
    }

    /**
     * 客户跟进设置
     */
    public function actionCustomerFollow()
    {
        return StaffService::customerFollow();
    }

    /**
     * 保存设置
     */
    public function actionSaveFollow()
    {
        $data = \Yii::$app->request->post();
        return StaffService::saveFollow($data);
    }

}