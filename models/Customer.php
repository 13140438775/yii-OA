<?php

namespace app\models;

use Yii;

/**
 * Class Customer
 * @package app\models
 * @CreateTime 18/4/20 12:45:02
 * @Author: fangxing@likingfit.com
 *
 * @property $audition
 * @property $docking_status
 */
class Customer extends Base
{
    const NEGOTIATIONING = 2;
    const SIGNING_SUCCESS = 3;
    const NO_AVAILABLE = 4;
    const INTERVIEW_PASS = 2;
    const INTERVIEW_NO_PASS = 3;
    const GYM_OPENING = 1;
    const GYM_OPENED = 2;
    const GYM_CLOSED = 3;

    /**
     * 已申请面试
     * @var int
     */
    public static $applyInterview = 1;


    /**
     * 客户意向枚举
     * @var array
     */
    public static $intentionText = [
        1 => '高意向',
        2 => '看店有资金',
        3 => '有资金',
        4 => '待定',
        5 => '无资金',
    ];

    /**
     * 客户来源枚举
     * @var array
     */
    public static $sourceArr = [
        '1' => '官网',
        '2' => 'app',
        '3' => '微信',
        '4' => '电话',
        '5' => '其他'
    ];

    /**
     * 对接状态枚举
     * @var array
     */
    public static $dockingStatus = [
        '1' => '新客户',
        '2' => '对接中',
        '3' => '签约成功',
        '4' => '无效客户'
    ];

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_customer';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'name'              => 'Name',
            'phone'             => 'Phone',
            'email'             => 'Email',
            'wechat'            => 'Wechat',
            'qq'                => 'Qq',
            'province_id'       => 'Province ID',
            'province_name'     => 'Province Name',
            'city_id'           => 'City ID',
            'city_name'         => 'City Name',
            'district_id'       => 'District ID',
            'district_name'     => 'District Name',
            'address'           => 'Address',
            'background'        => 'Background',
            'label_id'          => 'Label ID',
            'source'            => 'Source',
            'operator_id'       => 'Operator ID',
            'docking_status'    => 'Docking Status',
            'dock_staff_id'     => 'Dock Staff ID',
            'is_event'          => 'IS EVENT',
            'is_available'      => 'IS AVAILABLE',
            'is_visit'          => 'IS VISIT',
            'sign_date'         => 'Sign Date',
            'deadline_time'     => 'Deadline Time',
            'next_docking_time' => 'Next Docking Time',
            'create_time'       => 'Create Time',
            'update_time'       => 'Update Time',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/1 17:04:07
     * @Author     : heyafei@likingfit.com
     */
    public function getStaff()
    {
        return $this->hasOne(Staff::className(), ['id' => 'dock_staff_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/1 17:04:21
     * @Author     : heyafei@likingfit.com
     */
    public function getLabel()
    {
        return $this->hasOne(CustomerLabel::className(), ['label_id' => 'label_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/1 17:04:27
     * @Author     : heyafei@likingfit.com
     */
    public function getContract()
    {
        return $this->hasMany(OpenContract::className(), ['franchisee_phone' => 'phone']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/2 14:27:14
     * @Author     : heyafei@likingfit.com
     */
    public function getRemark()
    {
        return $this->hasMany(CustomerRemark::className(), ['customer_id' => 'id']);
    }

    /**
     * 剩余对接时间格式化
     * @param $time
     * @return float|int
     * @CreateTime 2018/3/6 14:28:54
     * @Author     : pb@likingfit.com
     */
    public static function filterDeadLineTime($time)
    {
        $diff = $time - time();
        if ($diff < 0) {
            return 0;
        }

        return ceil($diff / 24 / 3600);
    }

    /**
     * 客户意向格式化
     * @param $val
     * @return mixed|string
     * @CreateTime 2018/3/6 14:41:16
     * @Author     : pb@likingfit.com
     */
    public static function filterIntention($val)
    {
        return isset(static::$intentionText[$val]) ? static::$intentionText[$val] : '';
    }

    /**
     * 客户来源格式化
     * @param $val
     * @return mixed|string
     * @CreateTime 2018/3/6 16:21:55
     * @Author: heyafei@likingfit.com
     */
    public static function filterSource($val)
    {
        return isset(static::$sourceArr[$val]) ? static::$sourceArr[$val] : '';
    }

    /**
     * 对接状态格式化
     * @param $val
     * @return mixed|string
     * @CreateTime 2018/3/6 16:21:55
     * @Author: heyafei@likingfit.com
     */
    public static function filterDocking($val)
    {
        return isset(static::$dockingStatus[$val]) ? static::$dockingStatus[$val] : '';
    }
    /**
     * 检验客户是否存在
     * @param $val
     * @return mixed|string
     * @CreateTime 2018/3/9 16:21:55
     * @Author: chenxuxu@likingfit.com
     */
    public static function checkCustomerByPhone($params)
    {
        return self::find()
            ->where(['phone' => $params])
            ->orWhere(['name' => $params])
            ->one();
    }

    /**
     * 统计每天客户数量
     * 陈旭旭
     */
    public static function getCustomerNum($start,$end,$status)
    {
        return self::find()
            ->where(['>=',"create_time",$start])
            ->andwhere(['<','create_time',$end])
            ->andWhere(['docking_status' => $status])
            ->andWhere(['is_available' => AVAILABLE])
            ->count();
    }

    /**
     * 获取小组组员的客户数量
     * 陈旭旭
     */
     public static function getDepartmentUserId($params)
     {
         return self::find()
             ->where(['in','dock_staff_id', $params['staffs']])
             ->andWhere(['is_available' => AVAILABLE])
             ->andWhere(['docking_status' => $params['status']])
             ->andWhere(['>=',"create_time",$params['start']])
             ->andwhere(['<=','create_time',$params['end']])
             ->groupBy("create_time")
             ->count();
     }

     /**
      * 招商专员获取自己客户数量
      * 陈旭旭
      */
     public static function getCustomerNumByStaffId($staffId,$type,$start,$end)
     {
         return self::find()
             ->select("create_time")
             ->where(['dock_staff_id' => $staffId])
             ->andWhere(['docking_status' => $type])
             ->andWhere(['is_available' => AVAILABLE])
             ->andWhere(['>=',"create_time",$start])
             ->andwhere(['<=','create_time',$end])
             //->groupBy("create_time")
             ->asArray()
             ->all();
     }
     /**
      * 获取每个状态下客户的数量
      * 陈旭旭
      */
     public static function getCustomerType($type)
     {
         return self::find()
             ->where(['docking_status' => $type])
             ->count();
     }

     /**
      * 公海客户数量
      * 陈旭旭
      */
     public static function getSeasNum()
     {
         return self::find()
             ->where(['is_event' => AVAILABLE, 'dock_staff_id' => 0])
             ->count();

     }

}
