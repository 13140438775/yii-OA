<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/24
 * Time: 下午2:42
 */

namespace app\services;

use app\exceptions\StaffException;
use app\helpers\Helper;
use app\models\Base;
use app\models\CustomerFollow;
use app\models\Staff;
use app\models\Customer;
use app\models\Department;
use app\models\Roles;
use app\models\AuthAssignment;
use app\models\StaffCity;
use app\models\DepartmentCity;
use app\models\Address;
use yii\helpers\VarDumper;
use yii\db\Expression;


class StaffService
{
    /**
     * @param $department_id
     * @return array
     * @CreateTime 2018/3/19 18:05:14
     * @Author: heyafei@likingfit.com
     */
    public static function getDepartment($department_id)
    {
        $query = Department::findOne($department_id);
        if ($query['parent_id']) {
            return [$department_id];
        } else {
            return Department::find()->select(['id'])->where(['parent_id' => $department_id])->column();
        }
    }

    /**
     * 我的团队列表
     */
    public static function investmentManager($params, $page, $pagesize)
    {
        $extra = [
            'select' => [[
                'staff_id' => 't_staff.id',
                'department_id' => 't_department.id',
                'staff_name' => 't_staff.name',
                'phone' => 't_staff.phone',
                'email' => 't_staff.email',
                'department_name' => 't_department.name',
                'staff_status' => 't_staff.staff_status'
            ]],
            'orderBy' => [['t_staff.id' => SORT_DESC]]
        ];
        $join = [['department']];
        $staff = new Staff($extra);
        $result = $staff->paginate($page, $pagesize, $join, $params);
        // return $result;
        $staff_ids = [];
        foreach ($result['rows'] as &$val) {
            unset($val['department']);
            $staff_ids[] = $val['staff_id'];
            $val['follow_customer'] = $val['staff_id'].'2';// 沟通中
            $val['deal_customer'] = $val['staff_id'].'3'; // 已成交
        }

        // 客户的交接状态
        $dock_list = $customer = $role_list = [];
        if($staff_ids) $customer = Customer::find()
                    ->select(['dock_staff_id', 'docking_status', 'count(*) As count'])
                    ->filterWhere(['dock_staff_id' => $staff_ids])
                    ->groupBy('dock_staff_id, docking_status')
                    ->asArray()
                    ->all();
        foreach ($customer as $_val) {
            $dock_list[$_val['dock_staff_id'].$_val['docking_status']] = $_val['count'];
        }

        // 员工角色
        if ($staff_ids) {
            $auth_assignment = AuthAssignment::find()
                            ->select(['user_id', 'item_name'])
                            ->where(['user_id' => $staff_ids])
                            ->asArray()
                            ->all();
            // 角色名
            $roles = Roles::find()
                    ->select(['display_name', 'role_name'])
                    ->indexBy('role_name')
                    ->asArray()
                    ->all();
            foreach ($auth_assignment as $_val) {
                $role_list[$_val['user_id']] = isset($roles[$_val['item_name']]) ? $roles[$_val['item_name']]['display_name']: '';
            }
        }

        // 拼接数据
        foreach ($result['rows'] as &$_tmp) {
            $_tmp['follow_customer'] = isset($dock_list[$_tmp['follow_customer']]) ? $dock_list[$_tmp['follow_customer']]: 0;
            $_tmp['deal_customer'] = isset($dock_list[$_tmp['deal_customer']]) ? $dock_list[$_tmp['deal_customer']]: 0;
            $_tmp['role'] = isset($role_list[$_tmp['staff_id']]) ? $role_list[$_tmp['staff_id']]: '未分配角色';
            $_tmp['staff_status'] = $_tmp['staff_status'] ? "在职": "离职";
        }
        return $result;
    }

    /**
     * 新增员工
     */
    public static function addStaff($data)
    {
        $operator_id = \Yii::$app->user->getId();
        $email = isset($data['email']) ? $data['email']: '';
        $query = Staff::find()->where(['phone' => $data['phone']])->asArray()->one();
        if ($query) {
            throw new StaffException(StaffException::PHONE_EXIST);
        }

        if ($email) {
            $query = Staff::find()->where(['email' => $email])->one();
            if ($query) {
                throw new StaffException(StaffException::EMAIL_EXIST);
            }
        }

        $department_id = \Yii::$app->user->getIdentity()->department_id;

        if (isset($data['department_id']) && $data['department_id']) {
            if ($data['is_leader']) {
                $staff_info = Staff::find()->where(['department_id' => $data['department_id'], 'is_leader' => $data['is_leader']])->asArray()->one();
                if ($staff_info) throw new StaffException(StaffException::NAME_EXIST);
            }
            $department_id = $data['department_id'];
        }

        $rand = mt_rand(100000, 999999);
        $_password = Staff::PASSWORD.$rand;
        $password = md5(md5($_password).$email);
        $params = [
            'email' => $email,
            'name' => $data['name'],
            'phone' => $data['phone'],
            'is_leader' => $data['is_leader'],
            'operator_id' => $operator_id,
            'department_id' => $department_id,
            'password' => $password,
            'create_time' => time()
        ];
        $staff = new Staff();
        $staff->setAttributes($params, false);
        $staff->save();
        $staff_id = $staff->attributes['id'];

        // 调用方法生成角色
        $auth = \Yii::$app->getAuthManager();
        $role = $auth->createRole($data['role']);
        $auth->assign($role, $staff_id);
        if (isset($data['city_ids']) && $data['city_ids']) {
            $staff_city = new StaffCity();
            $_data = array();
            foreach ($data['city_ids'] as $val) {
                $_data[] = [
                    'staff_id' => $staff_id,
                    'department_id' => $data['department_id'],
                    'city_id' => $val
                ];
            }
            $staff_city->batchInsert($_data);
        }

        // 发送短信密码
        $_data = [$_password];
        Helper::sendSms($data['phone'], SMS_PASSWORD,  $_data);
    }

    /**
     * 编辑成员
     */
    public static function editStaff($data)
    {
        $staff_id = $data['staff_id'];
        $where = [
            'and',
            ['phone' => $data['phone']],
            ['!=', 'id', $staff_id]
        ];
        $query = Staff::find()->where($where)->asArray()->one();
        if ($query) {
            throw new StaffException(StaffException::PHONE_EXIST);
        }

        $params = [
            'name' => $data['staff_name'],
            'phone' => $data['phone'],
            'staff_status' => $data['staff_status'],
            'is_leader' => $data['is_leader']
        ];

        if (isset($data['department_id']) && $data['department_id']) {
            if ($data['is_leader']) {
                $_where = [
                    'and',
                    ['department_id' => $data['department_id'], 'is_leader' => $data['is_leader']],
                    ['!=', 'id', $staff_id]
                ];
                $staff_info = Staff::find()->where($_where)->asArray()->one();
                if ($staff_info) throw new StaffException(StaffException::NAME_EXIST);
            }
            $params['department_id'] = $data['department_id'];
        }

     
        // 角色的修改还没有添加
        \Yii::$app->getAuthManager()->revokeAll($staff_id);
        $auth = \Yii::$app->getAuthManager();
        $role = $auth->createRole($data['role']);
        $auth->assign($role, $staff_id);
        Staff::updateAll($params, ['id' => $staff_id]);

        // 城市
        if (isset($data['city_ids']) && $data['city_ids']) {
            // 先更新为无效在新增
            $staff_params['is_valid'] = Staff::NO_VAILID;
            StaffCity::updateAll($staff_params, ['staff_id' => $data['staff_id']]);

            $staff_city = new StaffCity();
            $_data = array();
            foreach ($data['city_ids'] as $val) {
                $_data[] = [
                    'staff_id' => $staff_id,
                    'department_id' => $data['department_id'],
                    'city_id' => $val
                ];
            }
            return $staff_city->batchInsert($_data);
        }
    }

    /**
     * 员工信息
     */
    public static function staffInfo($staff_id)
    {
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $parent_id = self::getParentId($department_id);
        $staff = Staff::find()->select(['email', 'name', 'phone', 'staff_status'])->where(['id' => $staff_id])->one();
        $select = [
            'staff_id' => 't_staff.id',
            'email' => 't_staff.email',
            'name' => 't_staff.name',
            'phone' => 't_staff.phone',
            'staff_status' => 't_staff.staff_status',
            'role' => 't_auth_assignment.item_name',
            'department_id' => 't_department.id',
            'department_name' => 't_department.name'
        ];
        $staff = Staff::find()
                ->select($select)
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->leftJoin('t_department', 't_department.id = t_staff.department_id')
                ->where(['t_staff.id' => $staff_id])
                ->asArray()
                ->one();


        // 负责的城市
        $staff_city = StaffCity::find()
                ->select(['t_region.city_id', 't_region.city_name', 't_staff_city.staff_id'])
                ->leftJoin('t_region', 't_region.city_id = t_staff_city.city_id')
                ->filterWhere(['t_staff_city.staff_id' => $staff['staff_id'], 't_staff_city.is_valid' => Staff::IS_VAILID])
                ->distinct(TRUE)
                ->asArray()
                ->all();
        $staff['city_ids'] = [];
        foreach ($staff_city as &$val) {
            $staff['city_ids'][] = $val['city_id'];
            unset($val['city_id']);
        }
        $staff['staff_city'] = $staff_city;
        if($staff['department_id'] == $parent_id) $staff['department_id'] = '';
        return $staff;
    }

    /**
     * 新增小组
     */
    public static function addDepartment($data)
    {
        $name = $data['name'];
        $query = Department::find()->where(['name' => $name])->one();
        if ($query) {
            throw new StaffException(StaffException::DEPARTMENT_EXIST);
        }
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $params = [
            'name' => $name,
            'parent_id' => $department_id,
            'create_time' => time()
        ];
        $department = new Department();
        $department->setAttributes($params, false);
        $department->save();

        if (isset($data['city_ids']) && $data['city_ids']) {
            $department_id = $department->attributes['id'];
            $department_city = new DepartmentCity();
            $_data = [];
            foreach ($data['city_ids'] as $val) {
                $_data[] = [
                    'department_id' => $department_id,
                    'city_id' => $val
                ];
            }
            return $department_city->batchInsert($_data);
        }

    }

    /**
     * 编辑小组
     */
    public static function editDepartment($data)
    {
        $role_list = Roles::find()->asArray()->all();
        $user_id = \Yii::$app->user->getId();
        $query = \Yii::$app->getAuthManager()->getRolesByUser($user_id);
        $key_list = array_keys($query);
        $_temp = self::getRoleChildren($role_list, $key_list[0]);
        $group_role_name = isset($_temp[1]) ? $_temp[1]['role_name']: $_temp[1]['role_name'];// 组长角色
        $staff_role_name = isset($_temp[2]) ? $_temp[2]['role_name']: $_temp[1]['role_name'];// 组员角色
        $department_info = Department::findOne($data['department_id']);
        $department = Department::find()->where(['name' => $data['name']])->one();
        $department_params = ['name' => $data['name']];



        if (isset($department['name']) && $department['name'] != $department_info['name']) {
            throw new StaffException(StaffException::GROUP_EXIST);
        }

        $staff_id = isset($data['staff_id']) ? $data['staff_id']: '';// 组长ID
        $staff_ids = isset($data['staff_ids']) ? $data['staff_ids']: []; // 组员ID结合
        $department_id = \Yii::$app->user->getIdentity()->department_id; // 部门ID


        // 原来的组长和组员列表
        $group_staff = Staff::find()
                ->select(['t_staff.id'])
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->where(['t_auth_assignment.item_name' => $group_role_name, 't_staff.department_id' => $data['department_id']])
                ->asArray()
                ->one();

        $staff_list = Staff::find()
                ->select(['t_staff.id'])
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->where(['t_auth_assignment.item_name' => $staff_role_name, 't_staff.department_id' => $data['department_id']])
                ->column();

        // 更新组名
        Department::updateAll($department_params, ['id' => $data['department_id']]);
        // 分配组长
        if (isset($group_staff['id']) && $staff_id != $group_staff['id']) {
            // 更新老的组长
            $staff_params['department_id'] = $department_id;
            Staff::updateAll($staff_params, ['id' => $group_staff['id']]);
        } 
        if($staff_id) {
            // 更新新的组长
            $staff_params['department_id'] = $data['department_id'];
            Staff::updateAll($staff_params, ['id' => $staff_id]);
        }

        //分配组员
        $new_staff = array_diff($staff_ids, $staff_list); // 更新的员工到小组
        $old_staff = array_diff($staff_list, $staff_ids); // 删除小组的员工
        if ($new_staff) {
            $staff_params['department_id'] = $data['department_id'];
            Staff::updateAll($staff_params, ['id' => $new_staff]);
        }
        if ($old_staff) {
            $staff_params['department_id'] = $department_id;
            Staff::updateAll($staff_params, ['id' => $old_staff]);
        }

        // 城市
        if (isset($data['city_ids']) && $data['city_ids']) {
            // 先更新为无效在新增
            $_department_params['is_valid'] = Staff::NO_VAILID;
            DepartmentCity::updateAll($_department_params, ['department_id' => $data['department_id']]);

            // 部门城市删除，更新对应的部门下的专员负责的城市的变更
            $_where = ['department_id' => $data['department_id'], 'is_valid' => Staff::IS_VAILID];
            $staff_city_ids = StaffCity::find()->select(['city_id'])->where($_where)->Column();
            $diff_city_ids = array_diff($staff_city_ids, $data['city_ids']); // 专员是否有部门删除的城市
            if($diff_city_ids) {
                StaffCity::updateAll($_department_params, ['department_id' => $data['department_id']]);
            }
            
            $department_city = new DepartmentCity();
            $_data = [];
            foreach ($data['city_ids'] as $val) {
                $_data[] = [
                    'department_id' => $data['department_id'],
                    'city_id' => $val
                ];
            }
            return $department_city->batchInsert($_data);
        }
    }

    /**
     * 组列表
     */
    public static function groupList()
    {
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $query = Department::find()
                ->select(['department_id' => 'id', 'department_name' => 'name'])
                ->where(['parent_id' => $department_id])
                ->asArray()->all();
        return $query;
    }

    /**
     * 获取当前登录人的下属角色
     *
     * @return array
     * @CreateTime 18/4/4 18:26:08
     * @Author: fangxing@likingfit.com
     */
    public static function getRoles()
    {
        $role_list = Roles::find()->asArray()->all();
        $user_id = \Yii::$app->user->getId();
        $query = \Yii::$app->getAuthManager()->getRolesByUser($user_id);
        $role = key($query);
        return self::getRoleChildren($role_list, $role);
    }

    /**
     * 获取角色对应的子角色
     *
     * @param $roles
     * @param string $pid
     * @return array
     * @CreateTime 18/4/4 18:27:51
     * @Author: fangxing@likingfit.com
     */
    public static function getRoleChildren($roles, $pid="")
    {
        $children = [];
        foreach ($roles as $key => $role){
            if(!isset($role["pid"])){
                $role["pid"] = "";
            }
            if($role["pid"] == $pid){
                array_splice($roles, $key, 1);
                $children[] = $role;
                $children = array_merge($children, static::getRoleChildren($roles, $role["role_name"]));
                break;
            }
        }
        return $children;
    }

    /**
     * 员工列表
     */
    public static function staffList($type)
    {
        $role_list = Roles::find()->asArray()->all();
        $user_id = \Yii::$app->user->getId();
        $query = \Yii::$app->getAuthManager()->getRolesByUser($user_id);
        $key_list = array_keys($query);
        $_temp = self::getRoleChildren($role_list, $key_list[0]);
        $item_name = isset($_temp[$type]) ? $_temp[$type]['role_name']: $_temp[1]['role_name'];
        $department_id = \Yii::$app->user->getIdentity()->department_id;

        $select = [
            'staff_id' => 't_staff.id',
            'staff_name' => 't_staff.name'
        ];
        $where = [
            't_auth_assignment.item_name' => $item_name,
            't_staff.staff_status' => Staff::IS_VAILID
        ];
        if ($key_list[0] != 'selection-manager') {
            $where['t_staff.department_id'] = $department_id;
        }
        $query = Staff::find()
                ->select($select)
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->where($where)
                ->asArray()
                ->all();
        return $query;
    }

    /**
     * 组员工
     */
    public static function groupStaff()
    {
        $role_list = self::getRoles();
        $roles = $group_staff = [];
        foreach ($role_list as $k => $v) {
            if($k == 0) continue;
            $roles[] = $v['role_name'];
        }
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        $select = [
            'department_id' => 't_department.id',
            'department_name' => 't_department.name',
            'staff_id' => 't_staff.id',
            'staff_name' => 't_staff.name',
            'item_name' => 't_auth_assignment.item_name'
        ];
        $department_list = Department::find()
                    ->select($select)
                    ->where(['t_department.parent_id' => $department_id])
                    ->leftJoin('t_staff', 't_staff.department_id = t_department.id')
                    ->leftJoin('t_auth_assignment','t_auth_assignment.user_id = t_staff.id')
                    ->asArray()
                    ->all();
        $department_ids = $department_city = $_department_city = [];
        foreach ($department_list as $val) {
            $department_ids[] = $val['department_id'];
        }
        $department_ids = array_unique($department_ids);
        if ($department_ids) {
            $department_city = DepartmentCity::find()
                    ->select(['t_department_city.department_id', 't_region.city_name'])
                    ->leftJoin('t_region', 't_region.city_id = t_department_city.city_id')
                    ->where(['t_department_city.department_id' => $department_ids, 't_department_city.is_valid' => Staff::IS_VAILID])
                    ->distinct(TRUE)
                    ->asArray()
                    ->all();
            foreach ($department_city as $val) {
                $_department_city[$val['department_id']][] = $val['city_name'];
            }
        }


        $_tmp = array_group($department_list, 'department_id');

        $group_staff = [];
        foreach ($_tmp as $key => $val) {
            $group_staff[$key]['group_name'] = $group_staff[$key]['city_name'] = '';
            $group_staff[$key]['staff_name'] = [];
            foreach ($val as $v) {
                $group_staff[$key]['department_id'] = $v['department_id'];
                $group_staff[$key]['department_name'] = $v['department_name'];
                if ($roles[0] == $v['item_name']) {
                    $group_staff[$key]['group_name'] = $v['staff_name'];
                } elseif($roles[1] == $v['item_name']) {
                    $group_staff[$key]['staff_name'][] = $v['staff_name'];
                }
            }
            $group_staff[$key]['staff_name'] = implode(',', $group_staff[$key]['staff_name']);
            if (isset($_department_city[$key])) {
                $group_staff[$key]['city_name'] = implode(',', $_department_city[$key]);
            }
        }
        $group_staff = array_values($group_staff);
        return $group_staff;
    }

    /**
     * 小组详情
     */
    public static function departmentInfo($department_id){
        $role_list = Roles::find()->asArray()->all();
        $user_id = \Yii::$app->user->getId();
        $query = \Yii::$app->getAuthManager()->getRolesByUser($user_id);
        $key_list = array_keys($query);
        $_temp = self::getRoleChildren($role_list, $key_list[0]);
        $group_role_name = isset($_temp[1]) ? $_temp[1]['role_name']: $_temp[1]['role_name'];// 组长角色
        $staff_role_name = isset($_temp[2]) ? $_temp[2]['role_name']: $_temp[1]['role_name'];// 组员角色

        $select = [
            'staff_id' => 't_staff.id',
            'staff_name' => 't_staff.name'
        ];

        $_department = Department::find()->where(['id' => $department_id])->asArray()->one();

        $group_staff = Staff::find()
                ->select($select)
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->where(['t_auth_assignment.item_name' => $group_role_name, 't_staff.department_id' => $department_id])
                ->asArray()
                ->one();

        $staff_list = Staff::find()
                ->select($select)
                ->leftJoin('t_auth_assignment', 't_auth_assignment.user_id = t_staff.id')
                ->where(['t_auth_assignment.item_name' => $staff_role_name, 't_staff.department_id' => $department_id])
                ->asArray()
                ->all();

        $department_city = DepartmentCity::find()
                    ->select(['t_region.city_name', 't_region.city_id'])
                    ->leftJoin('t_region', 't_region.city_id = t_department_city.city_id')
                    ->where(['t_department_city.department_id' => $department_id, 't_department_city.is_valid' => Staff::$available])
                    ->distinct(TRUE)
                    ->asArray()
                    ->all();
        $city_ids = [];
        foreach ($department_city as $val) {
            $city_ids[] = $val['city_id'];
        }

        $department_info = [
            'staff_id' => isset($group_staff['staff_id']) ? $group_staff['staff_id']: '',
            'staff_name' => isset($group_staff['staff_name']) ? $group_staff['staff_name']: '',
            'department_name' => $_department['name'],
            'staff_list' => $staff_list,
            'department_city' => $department_city,
            'city_ids' => $city_ids
        ];
        return $department_info;
    }

    /**
     * 获取当前登录人的下属员工
     *
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/4/4 18:40:54
     * @Author: fangxing@likingfit.com
     */
    public static function getSubordinate(){
        $role_names = array_column(self::getRoles(), "role_name");
        $user = \Yii::$app->getUser()->getIdentity();

        //非招商团队看到所有
        $specialRole = ["merchants-manager", "merchants-leader", "merchants-specialist"];
        $merchants = array_intersect($role_names, $specialRole);
        if(empty($merchants)){
            return [];
        }
        /**
         * @var $user Staff
         */
        if($user->is_leader){
            $ids = \Yii::$app->getAuthManager()->getUserIdsByRole($role_names);
        }else{
            $ids = $user->getId();
        }
        return Staff::findAll(["id" => $ids, "department_id" => $user->department_id]);
    }

    /**
     * 获取部门的ID
     */
    public static function getParentId($department_id)
    {
        $query = Department::find()->where(['id' => $department_id])->asArray()->one();
        if ($query['parent_id']) {
            $_tmp = Department::find()->select(['id'])->where(['id' => $query['parent_id']])->asArray()->one();
            return $_tmp['id'];
        } else {
            return $department_id;
        }
    }

    /**
     * 选址列表
     */
    public static function selectionList($params, $page, $pagesize)
    {
        $extra = [
            'select' => [[
                'staff_id' => 't_staff.id',
                'department_id' => 't_department.id',
                'staff_name' => 't_staff.name',
                'phone' => 't_staff.phone',
                'email' => 't_staff.email',
                'department_name' => 't_department.name',
                'staff_status' => 't_staff.staff_status'
            ]],
            'orderBy' => [['t_staff.id' => SORT_DESC]]
        ];
        $join = [['department']];
        $staff = new Staff($extra);
        $result = $staff->paginate($page, $pagesize, $join, $params);
        $staff_ids = [];
        foreach ($result['rows'] as $val) {
            $staff_ids[] = $val['staff_id'];
        }

        if ($staff_ids) {
            // 员工角色
            $auth_assignment = AuthAssignment::find()
                            ->select(['user_id', 'item_name'])
                            ->where(['user_id' => $staff_ids])
                            ->asArray()
                            ->all();
            // 角色名
            $roles = Roles::find()
                    ->select(['display_name', 'role_name'])
                    ->indexBy('role_name')
                    ->asArray()
                    ->all();
            foreach ($auth_assignment as $_val) {
                $role_list[$_val['user_id']] = isset($roles[$_val['item_name']]) ? $roles[$_val['item_name']]['display_name']: '';
            }

            // 负责的城市
            $staff_city = StaffCity::find()
                    ->select(['t_staff_city.staff_id', 't_region.city_name'])
                    ->leftJoin('t_region', 't_region.city_id = t_staff_city.city_id')
                    ->where(['t_staff_city.staff_id' => $staff_ids, 't_staff_city.is_valid' => Staff::IS_VAILID])
                    ->distinct(TRUE)
                    ->asArray()
                    ->all();
            $staff_city_list = [];

            foreach ($staff_city as $key => $val) {
                if(!isset($staff_city_list[$val['staff_id']])) $staff_city_list[$val['staff_id']] = [];
                $staff_city_list[$val['staff_id']][] = $val['city_name'];
            }

            // 当前跟进地址
            $select = [
                'staff_id' => 't_address.staff_id',
                'count' => new Expression('count(t_address.staff_id)')
            ];
            $current_list = Address::find()
                    ->select($select)
                    ->where(['staff_id' => $staff_ids, 'stage' => Address::$waitStageContract])
                    ->indexBy('staff_id')
                    ->groupBy('staff_id')
                    ->asArray()
                    ->all();

            // 签约地址
            $sign_list = Address::find()
                    ->select($select)
                    ->where(['staff_id' => $staff_ids, 'stage' => Address::$stageContract])
                    ->indexBy('staff_id')
                    ->groupBy('staff_id')
                    ->asArray()
                    ->all();
        }

        // 拼接数据
        foreach ($result['rows'] as &$_tmp) {
            $_tmp['staff_city'] = isset($staff_city_list[$_tmp['staff_id']]) ? implode(',', $staff_city_list[$_tmp['staff_id']]): '';
            $_tmp['current_count'] = isset($current_list[$_tmp['staff_id']]) ? $current_list[$_tmp['staff_id']]['count']: 0;
            $_tmp['sign_count'] = isset($sign_list[$_tmp['staff_id']]) ? $sign_list[$_tmp['staff_id']]['count']: 0;
            $_tmp['role'] = isset($role_list[$_tmp['staff_id']]) ? $role_list[$_tmp['staff_id']]: '未分配角色';
            $_tmp['staff_status'] = $_tmp['staff_status'] ? "在职": "离职";
        }
        return $result;
    }

    /**
     * 组所负责的城市列表
     */
    public static function departmentCity($data)
    {
        $department_city = DepartmentCity::find()
                    ->select(['t_region.city_name', 't_region.city_id'])
                    ->leftJoin('t_region', 't_region.city_id = t_department_city.city_id')
                    ->where(['t_department_city.department_id' => $data['department_id']])
                    ->distinct(TRUE)
                    ->asArray()
                    ->all();
        return $department_city;
    }

    /**
     * 客户跟进设置
     */
    public static function customerFollow()
    {
        return CustomerFollow::find()->where(['is_valid' => 1])->asArray()->all();
    }

    /**
     * 保存设置
     */
    public static function saveFollow($data)
    {
        $customer_follow = $data['customer_follow'];
        foreach ($customer_follow as $_val) {
            $_data = [
                'dock_ceiling' => $_val['dock_ceiling'],
                'time_limit' => $_val['time_limit'],
                'dock_time' => $_val['dock_time'],
            ];
            CustomerFollow::updateAll($_data, ['id' => $_val['id']]);
        }
        return true;
    }

    /**
     * 根据userId获取组负责城市id
     * @param $userId
     * @return Address[]|AuthAssignment[]|Customer[]|CustomerFollow[]|Department[]|DepartmentCity[]|Roles[]|Staff[]|StaffCity[]|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/4/20 15:55:04
     * @Author     : pb@likingfit.com
     */
    public static function getGroupCity($userId){
        return DepartmentCity::find()
            ->alias('dc')
            ->select(['dc.city_id'])
            ->innerJoin(Staff::tableName().' s', 's.department_id=dc.department_id')
            ->where([
                'dc.is_valid' => Base::$available,
                's.id' => $userId
            ])
            ->asArray()
            ->all();
    }

    /**
     * 根据userId获取负责城市id
     * @param $userId
     * @return Address[]|AuthAssignment[]|Customer[]|CustomerFollow[]|Department[]|DepartmentCity[]|Roles[]|Staff[]|StaffCity[]|array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/4/20 15:57:34
     * @Author     : pb@likingfit.com
     */
    public static function getCity($userId){
        return StaffCity::find()
            ->alias('sc')
            ->select(['sc.city_id'])
            ->where([
                'sc.is_valid' => Base::$available,
                'sc.staff_id' => $userId
            ])
            ->asArray()
            ->all();
    }
}