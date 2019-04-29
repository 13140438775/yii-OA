<?php
/**
 * Created by PhpStorm.
 * @Author: heyafei@likingfit.com
 * @CreateTime 2018/2/28 18:42:19
 */

namespace app\services;


use app\exceptions\CustomerException;
use app\models\Customer;
use app\models\CustomerEvent;
use app\models\CustomerFollow;
use app\models\CustomerRemark;
use app\models\Roles;
use app\models\Staff;
use app\models\OpenFlow;
use app\models\OpenProject;
use app\models\Department;
use app\models\CustomerStatistics;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use yii\helpers\VarDumper;

class CustomerService
{
    const NEWCUSTOMER = 1; //新客户
    const MIDDLECUSTOMER = 2;  //沟通中客户
    const SUCCESSCUSTOMER = 3;  //成功签约客户

    /**
     * 客户列表
     * @param $conditions
     * @param $page
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 2018/3/8 18:31:26
     * @Author: heyafei@likingfit.com
     */
    public static function getList($conditions, $page, $pagesize)
    {
        $extra = [
            'select' => [[
                'customer_id' => 't_customer.id',
                'dock_staff_id' => 't_customer.dock_staff_id',
                'customer_name' => 't_customer.name',
                'province_name' => 't_customer.province_name',
                'city_name' => 't_customer.city_name',
                'district_name' => 't_customer.district_name',
                'phone' => 't_customer.phone',
                'source' => 't_customer.source',
                'docking_status' => 't_customer.docking_status',
                'update_time' => 't_customer.update_time',
                'deadline_time' => 't_customer.deadline_time',
                'staff_name' => new Expression("IFNULL(t_staff.name, '')"),
                'label_name' => 't_customer.intention'
            ]],
            'groupBy' => ['t_customer.phone'],
            'orderBy' => [['t_customer.id' => SORT_DESC]]
        ];
        $join = [['staff']];
        $customer = new Customer($extra);
        $result = $customer->paginate($page, $pagesize, $join, $conditions);
        $customer_ids = $customer_remark = [];
        foreach ($result['rows'] as $val) {
            $customer_ids[] = $val['customer_id'];
        }

        if ($customer_ids) {
            $customer_event = CustomerEvent::find()
                ->select(['customer_id', new Expression('count(*)')])
                ->where(['customer_id' => $customer_ids])
                ->indexBy('customer_id')
                ->asArray()
                ->all();


            $select = [
                'customer_id' => 't_open_flow.customer_id',
                'count' => new Expression('count(t_open_project.id)')
            ];
            $where = [
                'and',
                ['t_open_flow.customer_id' => $customer_ids],
                ['!=', 't_open_project.gym_status', OpenProject::CLOSE]
            ];
            $open_project = OpenFlow::find()
                ->select($select)
                ->where($where)
                ->innerJoin('t_open_project', 't_open_project.series_id = t_open_flow.series_id')
                ->indexBy('t_open_flow.customer_id')
                ->groupBy('t_open_flow.customer_id')
                ->asArray()
                ->all();

            foreach ($result['rows'] as &$row) {
                $row['event_count'] = isset($customer_event[$row['customer_id']]) ? $customer_event[$row['customer_id']]['count(*)'] : 0;
                $row['source'] = Customer::filterSource($row['source']);
                $row['docking_status'] = Customer::filterDocking($row['docking_status']);
                $row['deadline_time'] = Customer::filterDeadLineTime($row['deadline_time']);
                $row['label_name'] = Customer::filterIntention($row['label_name']);
                $row['city_name'] = $row['city_name'] . '/' . $row['district_name'];

                $remark_list = self::remarkList($row['customer_id']);
                $row['remark_count'] = $remark_list['totle'];
                $row['remark_list'] = $remark_list['rows'];

                $row['count'] = isset($open_project[$row['customer_id']]) ? $open_project[$row['customer_id']]['count']: 0;
            }
        }
        return $result;
    }

    /**
     * 备注列表
     * @param $customer_id
     * @return array
     * @CreateTime 2018/3/5 17:45:59
     * @Author: heyafei@likingfit.com
     */
    public static function remarkList($customer_id)
    {
        $select = [
            'staff_name' => 't_staff.name',
            'item_name' => 't_auth_assignment.item_name',
            'remark' => 't_customer_remark.remark',
            'create_time' => 't_customer_remark.create_time',
        ];
        $query = CustomerRemark::find()
            ->select($select)
            ->filterWhere(['t_customer_remark.customer_id' => $customer_id])
            ->innerJoin('t_staff', "t_staff.id = t_customer_remark.operator_id")
            ->innerJoin('t_auth_assignment', "t_auth_assignment.user_id = t_staff.id")
            ->orderBy('t_customer_remark.create_time DESC')
            ->asArray()
            ->all();
        $role_list = Roles::find()->select(['role_name', 'display_name'])->indexBy('role_name')->asArray()->all();
        $week_arr = array("日", "一", "二", "三", "四", "五", "六");
        foreach ($query as &$_val) {
            $time = $_val['create_time'];
            $_val['day'] = date('m/d', $time);
            $_val['week'] = '周' . $week_arr[date('w', $time)];
            $_val['time'] = date('H:i', $time);
            $_val['item_name'] = isset($role_list[$_val['item_name']]) ? $role_list[$_val['item_name']]['display_name']: '暂无角色';
        }
        $result = ['totle' => count($query), 'rows' => $query];
        return $result;
    }

    /**
     * 属性/来源/加盟意向/对接状态
     * @CreateTime 2018/3/4 11:57:02
     * @Author: heyafei@likingfit.com
     */
    public static function attributeLabels()
    {
        return \Yii::$app->params['labels'];
    }

    /**
     * 新增客户
     * @param $params
     * @return bool
     * @throws CustomerException
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 2018/3/19 14:45:23
     * @Author: heyafei@likingfit.com
     */
    public static function saveCustomer($params)
    {
        $query = Customer::find()->where(['phone' => $params['phone']])->asArray()->one();
        if ($query) {
            throw new CustomerException(CustomerException::ERR_EXIST);
        }
        $customer = new Customer();
        $customer->setAttributes($params, false);
        return $customer->save();
    }

    /**
     * 客户详情
     * @param $customer_id
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/5 17:46:14
     * @Author: heyafei@likingfit.com
     */
    public static function getInfo($customer_id)
    {
        $select = [
            'customer_id' => 't_customer.id',
            'customer_name' => 't_customer.name',
            'phone' => 't_customer.phone',
            'source' => 't_customer.source',
            'intention' => 't_customer.intention',
            'province_name' => 't_customer.province_name',
            'province_id' => 't_customer.province_id',
            'city_name' => 't_customer.city_name',
            'city_id' => 't_customer.city_id',
            'district_name' => 't_customer.district_name',
            'district_id' => 't_customer.district_id',
            'address' => 't_customer.address',
            'wechat' => 't_customer.wechat',
            'qq' => 't_customer.qq',
            'email' => 't_customer.email',
            'remark' => 't_customer.remark',
            'docking_status' => 't_customer.docking_status',
            'audition' => 't_customer.audition',
            'dock_staff_id' => 't_customer.dock_staff_id',
            'is_event' => 't_customer.is_event',
            'is_available' => 't_customer.is_available',
            'invalid_reason' => 't_customer.invalid_reason',
        ];
        $query = Customer::find()
            ->select($select)
            ->filterWhere(['t_customer.id' => $customer_id])
            ->asArray()
            ->one();

        if ($query['docking_status'] == Customer::SIGNING_SUCCESS) {
            $open_processing = [
                ['label' => '商务洽谈', 'val' => true],
                ['label' => '面试通过', 'val' => true],
                ['label' => '签约加盟', 'val' => true]
            ];
        } elseif($query['audition'] == Customer::INTERVIEW_PASS) {
            $open_processing = [
                ['label' => '商务洽谈', 'val' => true],
                ['label' => '面试通过', 'val' => true],
                ['label' => '签约加盟', 'val' => false]
            ];
        } elseif ($query['docking_status'] == Customer::NEGOTIATIONING) {
            $open_processing = [
                ['label' => '商务洽谈', 'val' => true],
                ['label' => '面试通过', 'val' => false],
                ['label' => '签约加盟', 'val' => false]
            ];
        } else {
            $open_processing = [
                ['label' => '商务洽谈', 'val' => false],
                ['label' => '面试通过', 'val' => false],
                ['label' => '签约加盟', 'val' => false]
            ];
        }

        if ($query) {
            $query['address_list'] = [
                $query['province_id'],
                $query['city_id'],
                $query['district_id']
            ];
        }
        $query['source'] = Customer::filterSource($query['source']);
        $query['intention'] = Customer::filterIntention($query['intention']);
        $query['customer_status'] = Customer::filterDocking($query['docking_status']);
        $query['gym_name'] = $query['customer_name'].'的健身房';
        return ['info' => $query, 'open_processing' => $open_processing];
    }

    /**
     * 员工列表
     * @param $type
     * @return array|\yii\db\ActiveRecord[]
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/4/16 14:57:00
     * @Author: heyafei@likingfit.com
     */
    public static function staffList($type)
    {
        $department_id = \Yii::$app->user->getIdentity()->department_id;
        if ($type) {
            $select = [
                'id' => 't_department.id',
                'name' => 't_department.name',
                'count' => new Expression('count(t_department.id)'),
            ];
            $role_name = 'merchants-leader';
            $customer_follow = CustomerFollow::find()->where(['role_name' => $role_name])->one();
            $query = Department::find()
                ->select($select)
                ->where(['t_department.parent_id' => $department_id])
                ->leftJoin('t_customer', 't_customer.dock_group_id = t_department.id AND t_customer.docking_status in (1,2)')
                ->groupBy('t_department.id')
                ->orderBy('count')
                ->asArray()
                ->all();
            foreach ($query as $k => &$item) {
                if($customer_follow) $item['count'] = $customer_follow->dock_ceiling - $item['count'];
                if($item['count'] <= 0) $item['disabled'] = true;
            }
        } else {
            $select = [
                'id' => 't_staff.id',
                'name' => 't_staff.name',
                'count' => new Expression('count(t_staff.id)'),
            ];
            $department_ids = StaffService::getDepartment($department_id);
            $department_ids[] = $department_id;
            $where = [
                'and',
                ['t_staff.is_leader' => Staff::$NoLeaderType],
                ['in', 't_staff.department_id', $department_ids]
            ];

            $role_name = 'merchants-specialist';
            $customer_follow = CustomerFollow::find()->where(['role_name' => $role_name])->one();
            $query = Staff::find()
                ->select($select)
                ->where($where)
                ->leftJoin('t_customer', 't_customer.dock_staff_id = t_staff.id AND t_customer.docking_status in (1,2)')
                ->groupBy('t_staff.id')
                ->orderBy('count')
                ->asArray()
                ->all();
            foreach ($query as $k => &$item) {
                if($customer_follow) $item['count'] = $customer_follow->dock_ceiling - $item['count'];
                if($item['count'] <= 0) $item['disabled'] = true;
            }
        }
        return $query;
    }

    /**
     * 更新客户
     * @param $pk
     * @param $params
     * @return int
     * @throws CustomerException
     * @CreateTime 2018/3/19 15:07:09
     * @Author: heyafei@likingfit.com
     */
    public static function updateCustomer($pk, $params)
    {
        if (is_array($pk)) {
            foreach ($pk as $customerId) {
                $customer = Customer::find()->where(['id' => $customerId])->one();
                if ($customer->audition == Customer::$applyInterview || $customer->audition == Customer::INTERVIEW_PASS) {
                    throw new CustomerException(CustomerException::APPLY_INTERVIEW);
                }
            }
        }
        return Customer::updateAll($params, ['id' => $pk]);
    }

    /**
     * 对接记录
     * @param $customer_id
     * @return array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/5 17:46:29
     * @Author: heyafei@likingfit.com
     */
    public static function dockingRecord($customer_id)
    {
        $select = [
            'staff_name' => 't_staff.name',
            'source' => 't_customer.source',
            'start_time' => 't_project_director.create_time',
            'end_time' => new Expression('MAX(t_customer_remark.create_time)'),
            'count' => new Expression('count(t_customer_remark.operator_id)')
        ];
        $query = Customer::find()
            ->select($select)
            ->filterWhere(['t_customer.id' => $customer_id, 't_project_director.customer_id' => $customer_id])
            ->leftJoin('t_customer_remark', "t_customer_remark.customer_id = t_customer.id")
            ->leftJoin('t_staff', "t_staff.id = t_customer_remark.operator_id")
            ->leftJoin('t_project_director', "t_project_director.staff_id = t_customer_remark.operator_id")
            ->groupBy('t_customer_remark.operator_id')
            ->orderBy('t_customer_remark.create_time DESC')
            ->asArray()
            ->all();
        foreach ($query as &$_val) {
            $_val['staff_name'] = $_val['staff_name'] ? $_val['staff_name']: "暂无对接人";
            $_val['start_time'] = $_val['start_time'] ? date('Y-m-d', $_val['start_time']): 0;
            $_val['end_time'] = $_val['end_time'] ? date('Y-m-d', $_val['end_time']) : 0;
            $_val['source'] = Customer::filterSource($_val['source']);
        }
        return $query;
    }

    /**
     * 获取客户
     * @param $condition
     *
     * @return null|static
     * @CreateTime 2018/3/6 15:57:18
     * @Author     : screamwolf@likingfit.com
     */
    public static function getOne($condition)
    {
        return Customer::find()
            ->andWhere($condition)
            ->one();
    }

    /**
     * @param $customer_id
     * @return array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/6 12:47:35
     * @Author: heyafei@likingfit.com
     */
    public static function customerLiking($customer_id)
    {
        $select = [
            'gym_name' => 't_open_project.gym_name',
            'type' => 't_open_project.open_type',
            'gym_status' => 't_open_project.gym_status',
        ];

        $query = OpenFlow::find()
            ->select($select)
            ->where(['customer_id' => $customer_id])
            ->leftJoin('t_open_project', 't_open_project.series_id = t_open_flow.series_id')
            ->groupBy('t_open_project.id')
            ->asArray()
            ->all();
        $in_operation = $project_opening = [];
        foreach ($query as $val) {
            if ($val['gym_status'] > 1) {
                $in_operation[] = [
                    'type' => $val['type'],
                    'gym_name' => $val['gym_name'],
                    'gym_status' => $val['gym_status']
                ];
            } else {
                $project_opening[] = [
                    'type' => $val['type'],
                    'gym_name' => $val['gym_name'],
                    'gym_status' => $val['gym_status']
                ];
            }
        }
        $project_arr = [
            'open_list' => [
                'in_operation' => $in_operation,
                'count' => count($in_operation)
            ],
            'opening_list' => [
                'project_opening' => $project_opening,
                'count' => count($project_opening)
            ],
        ];
        return ['project_arr' => $project_arr];
    }

    /**
     * 客户移入公海
     */
    public static function moveSea($customer_id)
    {
        $query = Customer::find()
            ->where(['id' => $customer_id])
            ->asArray()
            ->one();
        if(in_array($query['audition'], [Customer::$applyInterview, Customer::INTERVIEW_PASS])) {
            throw new CustomerException(CustomerException::APPLY_INTERVIEW);
        }
        $dock_staff_id = $query['dock_staff_id'];
        $params['dock_staff_id'] = 0;
        $params['is_event'] = 1;
        Customer::updateAll($params, ['id' => $customer_id]);


        $query = CustomerEvent::find()
            ->where(['customer_id' => $customer_id, 'dock_staff_id' => $dock_staff_id])
            ->asArray()
            ->one();
        if (!$dock_staff_id || !$query) return;
        $data = [
            'customer_id' => $customer_id,
            'event_type' => 1,
            'event_time' => time(),
            'dock_staff_id' => $dock_staff_id,
            'create_time' => time()
        ];
        $customer_event = new CustomerEvent();
        $customer_event->setAttributes($data, false);
        return $customer_event->save();
    }

    /**
     * 统计每天客户数
     *
     * @param $start
     * @param $end
     * @return int
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 18/4/4 17:14:20
     * @Author: fangxing@likingfit.com
     * @Author: chenxuxu@likingfit.com
     */
    public static function statisticsCustomer($start, $end)
    {
        //新客户
        $newCusotner = Customer::getCustomerNum($start, $end, self::NEWCUSTOMER);

        //成功签约客户
        $successCusotner = Customer::getCustomerNum($start, $end, self::SUCCESSCUSTOMER);

        $time = time();
        $model = new CustomerStatistics;
        $date = date("Y-m-d", $start);
        $data = [
            [
                'num' => $newCusotner,
                'type' => CustomerStatistics::NEW_CUSTOMER,
                'date' => $date,
                'create_time' => $time
            ],
            [
                'num' => $successCusotner,
                'type' => CustomerStatistics::SUCCESS,
                'date' => $date,
                'create_time' => $time
            ]
        ];
        return $model->batchInsert($data);
    }

    /**
     * 获取客户统计数据
     *
     * @param $start
     * @param $end
     * @return array
     * @throws \Throwable
     * @CreateTime 18/4/7 21:11:51
     * @Author: fangxing@likingfit.com
     */
    public static function isLeader($start, $end)
    {
        $staffIds = array_map(function (Staff $row) {
            return $row->getAttribute("id");
        }, StaffService::getSubordinate());

        $legend = [1 => "新客户", 3 => "成功签约"];
        $dock_status = array_keys($legend);

        $statistics = Customer::find()
            ->filterWhere([
                "and",
                [
                    "docking_status" => $dock_status,
                    "dock_staff_id" => $staffIds
                ],
                [">=", "create_time", $start],
                ["<", "create_time", $end]
            ])
            ->groupBy(["docking_status", "date_group"])
            ->select([
                "num" => "COUNT(*)",
                "docking_status",
                "date_group" => "FROM_UNIXTIME(create_time, \"%Y/%m/%d\")"
            ])
            ->asArray()
            ->all();
        $statistics = array_group($statistics, "date_group");

        $originX = getX($start, $end);
        $x = array_map(function ($val){
            return substr($val, 6);
        }, $originX);

        $series = [];
        foreach ($dock_status as $i => $type) {
            $series[$i] = [
                "name" => $legend[$type],
                "data" => []
            ];
            foreach ($originX as $day) {
                if (!array_key_exists($day, $statistics)) {
                    array_push($series[$i]["data"], 0);
                    continue;
                }
                $flag = false;
                foreach ($statistics[$day] as $v) {
                    if ($type == $v["docking_status"]) {
                        array_push($series[$i]["data"], (int)$v["num"]);
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    array_push($series[$i]["data"], 0);
                }
            }
        }
        return compact("x", "series", "legend");
    }

    /**
     * 获取招商部下面的组部门
     * 陈旭旭
     */
    public static function getDepartment()
    {
        $params = "招商部";
        $parentId = Department::getDepartment($params);
        foreach ($parentId as $v) {
            $parents[] = $v['id'];
        }
        return $parents;
    }

    /**
     * 拼接日期和用户函数
     * 陈旭旭
     */
    public static function dateCustomer($data)
    {
        $newdata = [];
        foreach ($data as $v) {
            $time = substr($v['create_time'], 0, 10);
            if (array_key_exists($time, $newdata)) {
                $newdata[$time]++;
            } else {
                $newdata[$time] = 1;
            }
        }
        return $newdata;
    }

    /**
     * @return mixed
     * @CreateTime 18/4/4 16:32:47
     * @Author: fangxing@likingfit.com
     * @Author: chenxuxu@likingfit.com
     */
    public static function getTypeCustomer()
    {
        $customer[] = [
            'label' => "新客户",
            "num" => Customer::getCustomerType(self::NEWCUSTOMER)
        ];
        $customer[] = [
            'label' => "对接中",
            "num" => Customer::getCustomerType(self::MIDDLECUSTOMER)
        ];
        $customer[] = [
            'label' => "成功签约",
            "num" => Customer::getCustomerType(self::SUCCESSCUSTOMER)
        ];
        $role_name = key(\Yii::$app->getAuthManager()->getRolesByUser(\Yii::$app->user->id));
        if ($role_name === "boss") {
            $customer[] = [
                'label' => "公海",
                "num" => Customer::getSeasNum()
            ];
            $customer[] = [
                'label' => "总客户",
                "num" => Customer::find()->count()
            ];
        }
        return $customer;

    }

}