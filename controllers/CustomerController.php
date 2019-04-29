<?php
/**
 * Created by PhpStorm.
 * User: wfeng
 * Date: 2017/1/22
 * Time: 14:17
 */

namespace app\controllers;

use app\models\Customer;
use app\models\CustomerFollow;
use app\models\CustomerRemark;
use app\models\WorkItem;
use app\services\CustomerService;
use app\services\RegionService;
use yii\helpers\VarDumper;

class CustomerController extends BaseController
{
    /**
     * 客户列表
     * @CreateTime 2018/3/2 13:02:33
     * @Author: heyafei@likingfit.com
     */
    public function actionList()
    {
        $time_arr = [
            [0, 1000],
            [0, 5],
            [5, 10],
            [10, 20],
            [20, 30],
            [30, 1000],
        ];
        $data = \yii::$app->Request->post();
        $pagesize = isset($data['pagesize']) ? $data['pagesize'] : 15;
        $page = isset($data['page']) ? $data['page'] : 1;
        $docking_status = isset($data['docking_status']) ? $data['docking_status'] : '';
        $source = isset($data['source']) ? $data['source'] : '';
        $intention = isset($data['intention']) ? $data['intention'] : '';
        $type = isset($data['type']) ? $data['type'] : '';
        $dock_staff_id = isset($data['dock_staff_id']) ? $data['dock_staff_id'] : '';
        $phone = isset($data['phone']) ? $data['phone'] : '';
        $deadline_time = isset($data['deadline_time']) && $data['deadline_time'] ? $data['deadline_time'] : 0;
        $operator_name = isset($data['operator_name']) ? $data['operator_name'] : '';
        $address = isset($data['address']) ? $data['address'] : '';
        list($start_time, $end_time) = $time_arr[$deadline_time];
        $start_time = time() + $start_time * 3600 * 24;
        $end_time = time() + $end_time * 3600 * 24;

        $param = [
            'and',
            [
                't_customer.docking_status' => $docking_status ? $docking_status : '',
                't_customer.source' => $source ? $source : '',
                't_customer.intention' => $intention ? $intention : '',
                't_customer.is_event' => $type,
            ],
            ['like', 't_staff.name', $operator_name],
            ['like', 't_customer.address', $address],
            // ['!=', 't_customer.dock_staff_id', $dock_staff_id],
            [
                "or",
                ['like', 't_customer.name', $phone],
                ['like', 't_customer.phone', $phone]
            ]
        ];
        // if ($dock_staff_id === '') $param[] = ['!=', 't_customer.dock_staff_id', 0];
        if ($dock_staff_id === 0) $param[1]['t_customer.dock_staff_id'] = 0;

        if (!$type && $deadline_time) {
            $param[] = ['>', 't_customer.deadline_time', $start_time];
            $param[] = ['<=', 't_customer.deadline_time', $end_time];
        }
        // VarDumper::dump($param);die;
        $customerArr = CustomerService::getList($param, $page, $pagesize);
        return $customerArr;
    }

    /**
     * 备注列表
     * @CreateTime 2018/3/2 15:31:37
     * @Author: heyafei@likingfit.com
     */
    public function actionRemarkList()
    {
        $data = \yii::$app->Request->post();
        $customer_id = $data['customer_id'];
        $remark_list = CustomerService::remarkList($customer_id);
        return $remark_list;
    }

    /**
     * 属性列表/来源+加盟意向+对接状态
     * @CreateTime 2018/3/4 11:34:10
     * @Author: heyafei@likingfit.com
     */
    public function actionAttributeLabels()
    {
        return CustomerService::attributeLabels();
    }

    /**
     * 新增客户
     * @CreateTime 2018/3/2 17:12:57
     * @Author: heyafei@likingfit.com
     */
    public function actionAddCustomer()
    {
        $data = \yii::$app->Request->post();
        $params = [
            'phone' => $data['phone'],
            'name' => $data['name'],
            'email' => isset($data['email']) ? $data['email'] : '',
            'wechat' => isset($data['wechat']) ? $data['wechat'] : '',
            'qq' => isset($data['qq']) ? $data['qq'] : '',
            'source' => $data['source'],
            'intention' => isset($data['intention']) ? $data['intention'] : '0',
            'province_id' => $data['province_id'],
            'city_id' => $data['city_id'],
            'district_id' => $data['district_id'],
            'address' => isset($data['address']) ? $data['address'] : '',
            'operator_id' => \yii::$app->user->getId(),
            'remark' => isset($data['remark']) ? $data['remark'] : '',
            'create_time' => time(),
            'update_time' => time()
        ];
        $params['province_name'] = RegionService::provinceInfo($params['province_id'])['province_name'];
        $params['city_name'] = RegionService::cityInfo($params['city_id'])['city_name'];
        $params['district_name'] = RegionService::districtInfo($params['district_id'])['district_name'];
        return CustomerService::saveCustomer($params);
    }

    /**
     * 客户详情
     * @CreateTime 2018/3/4 20:29:41
     * @Author: heyafei@likingfit.com
     */
    public function actionGetInfo()
    {
        $data = \yii::$app->Request->post();
        return CustomerService::getInfo($data['customer_id']);
    }

    /**
     * 员工列表
     * @CreateTime 2018/3/4 22:34:56
     * @Author: heyafei@likingfit.com
     */
    public function actionStaffList()
    {
        $data = \yii::$app->Request->post();
        return CustomerService::staffList($data['type']);
    }

    /**
     * 更新用户
     * @CreateTime 2018/3/4 21:50:42
     * @Author: heyafei@likingfit.com
     */
    public function actionUpdateCustomer()
    {
        $data = \yii::$app->Request->post();
        $params = [
            'name' => $data['name'],
            'email' => isset($data['email']) ? $data['email'] : '',
            'wechat' => isset($data['wechat']) ? $data['wechat'] : '',
            'qq' => isset($data['qq']) ? $data['qq'] : '',
            'source' => $data['source'],
            'intention' => isset($data['intention']) ? $data['intention'] : '4',
            'province_name' => $data['province_name'],
            'province_id' => $data['province_id'],
            'city_name' => $data['city_name'],
            'city_id' => $data['city_id'],
            'district_name' => $data['district_name'],
            'district_id' => $data['district_id'],
            'address' => isset($data['address']) ? $data['address'] : '',
            'remark' => isset($data['remark']) ? $data['remark'] : '',
            'update_time' => time()
        ];
        $params['province_name'] = RegionService::provinceInfo($params['province_id'])['province_name'];
        $params['city_name'] = RegionService::cityInfo($params['city_id'])['city_name'];
        $params['district_name'] = RegionService::districtInfo($params['district_id'])['district_name'];
        return CustomerService::updateCustomer($data['customer_id'], $params);
    }

    /**
     * 指定对接组
     */
    public function actionAppointGroup()
    {
        $data = \yii::$app->Request->post();
        $params['dock_group_id'] = $data['staff_id'];
        return CustomerService::updateCustomer($data['customer_ids'], $params);
    }

    /**
     * 对接记录
     * @CreateTime 2018/3/5 15:34:10
     * @Author: heyafei@likingfit.com
     */
    public function actionDockingRecord()
    {
        $data = \yii::$app->Request->post();
        return CustomerService::dockingRecord($data['customer_id']);
    }

    /**
     * 健身房列表
     * @CreateTime 2018/3/9 18:04:48
     * @Author: heyafei@likingfit.com
     */
    public function actionCustomerLiking()
    {
        $data = \yii::$app->Request->post();
        return CustomerService::customerLiking($data['customer_id']);
    }

    /**
     * 校验加盟商
     * @return array
     * @CreateTime 2018/3/6 16:04:20
     * @Author     : screamwolf@likingfit.com
     */
    public function actionCheckCustomer()
    {
        $param = \Yii::$app->request->post();
        $condition = [
            'OR',
            [
                'name' => $param['param']
            ],
            [
                'phone' => $param['param']
            ]
        ];

        $customer = CustomerService::getOne($condition);
        return ["customer_id" => !empty($customer) ? $customer['id'] : 0];
    }

    /**
     * 移入公海
     */
    public function actionMoveSea()
    {
        $data = \Yii::$app->request->post();
        return CustomerService::moveSea($data['customer_id']);
    }

    /**
     * 置为无效的客户
     */
    public function actionInvalidCustomer()
    {
        $data = \yii::$app->Request->post();
        $params['is_available'] = $data['is_available'];
        $params['docking_status'] = Customer::NO_AVAILABLE;
        $params['invalid_reason'] = $data['invalid_reason'];
        $params['update_time'] = time();
        $gym_info = CustomerService::customerLiking($data['customer_id']);
        $opening_list = $gym_info['project_arr']['opening_list']['project_opening'];
        $open_list = $gym_info['project_arr']['open_list']['in_operation'];
        $gym_list = [];
        // 开店中
        foreach($opening_list as $val) {
            $gym_list['gym_opening'][] = $val['gym_name'];
        }
        // 营业中
        foreach($open_list as $val) {
            if($val['gym_status'] == Customer::GYM_OPENED) $gym_list['gym_opened'][] = $val['gym_name'];
        }
        if($gym_list) {
            $gym_list['gym_opening'] = implode('、', $gym_list['gym_opening']);
            $gym_list['gym_opened'] = implode('、', $gym_list['gym_opened']);
            //$gym_list['gym_opening'] = $gym_list['gym_opened'] = '1111';
            return $gym_list;
        }
        return CustomerService::updateCustomer($data['customer_id'], $params);
    }

    /**
     * 新增customer remark
     * @CreateTime 2018/3/29 15:17:41
     * @Author     : pb@likingfit.com
     */
    public function actionAddRemark()
    {
        $customer = Customer::find()
            ->where([
                'phone' => \yii::$app->request->post('phone')
            ])->one();
        $work = WorkItem::find()
            ->where([
                'id' => \yii::$app->request->post('work_item_id')
            ])
            ->one();

        $remark = CustomerRemark::find()
            ->where([
                'customer_id'=>$customer->id,
                'series_id'   => $work->series_id
            ])->one();
        if(is_null($remark)) {
            // 更新对接时间
            $customer->deadline_time = strtotime("+45 day");
            $customer->save();
        }

        $customerRemark = new CustomerRemark();
        $customerRemark->setAttributes([
            'customer_id' => $customer->id,
            'remark'      => \yii::$app->request->post('remark'),
            'operator_id' => \Yii::$app->user->getIdentity()->id,
            'series_id'   => $work->series_id,
        ], false);

        return $customerRemark->save();
    }
}