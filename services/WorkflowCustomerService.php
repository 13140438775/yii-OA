<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/7 14:38:08
 */

namespace app\services;

use app\models\Customer;
use app\models\CustomerRemark;
use app\models\Staff;
use app\data\ActiveDataProvider;
use yii\db\Query;
use app\models\ProjectDirector;

class WorkflowCustomerService
{
    /**
     * 侧边栏-商务洽谈（合营）
     * @param $request
     * @return array
     * @CreateTime 2018/3/6 10:49:41
     * @Author     : pb@likingfit.com
     */
    public static function negotiateInit($request)
    {
        $customerId = $request['flow']['customer_id'];

        $negotiate = (new Query())
            ->from(['cr' => CustomerRemark::tableName()])
            ->select(['s.name', 'cr.remark', 'cr.create_time'])
            ->innerJoin(Staff::tableName() . ' s', 'cr.operator_id=s.id')
            ->andWhere(['cr.customer_id' => $customerId, 'cr.series_id'=>$request['flow']['series_id']])
            ->orderBy(['cr.create_time' => SORT_DESC]);
        $provider  = new ActiveDataProvider([
            'query'      => $negotiate,
            'attributes' => [
                'create_time' => function ($row) {
                    return CustomerRemark::filterCreateTime($row['create_time']);
                }
            ]
        ]);

        $customer = Customer::find()
            ->select([
                'name', 'phone', 'qq', 'wechat',
                'email', 'province_name', 'city_name', 'district_name',
                'address', 'intention', 'remark', 'deadline_time'])
            ->where(['id' => $customerId])
            ->asArray()
            ->one();

        $customer['intention']     = Customer::filterIntention($customer['intention']);
        $customer['deadline_time'] = Customer::filterDeadLineTime($customer['deadline_time']);

        return ['negotiate' => $provider->getModels(), 'customer' => $customer];
    }

    /**
     * 侧边栏-商务洽谈（合营）
     * @param $request
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 2018/3/29 16:25:59
     * @Author     : pb@likingfit.com
     */
    public static function negotiateSave($request)
    {
        FlowService::setVariable($request['flow']['work_item_id'], ['IsMainInterview' => $request['status']]);
        return true;
    }

    /**
     * 侧边栏-记录面试结果（合营）
     * @param $request
     * @return array
     * @CreateTime 2018/3/29 16:30:34
     * @Author     : pb@likingfit.com
     */
    public static function interviewInit($request)
    {
        return [];
    }

    /**
     * 侧边栏-记录面试结果（合营流程）
     * @param $request
     * @return bool
     * @throws \ReflectionException
     * @CreateTime 2018/3/29 16:30:45
     * @Author     : pb@likingfit.com
     */
    public static function interviewSave($request)
    {
        $customer = Customer::find()
            ->where(['id' => $request['flow']['customer_id']])
            ->one();

        if ($request['status']){
            $customer->audition = Customer::INTERVIEW_PASS;
        }else{
            $customer->audition = Customer::INTERVIEW_NO_PASS;
        }

        $customer->save();

        FlowService::setVariable($request['flow']['work_item_id'], ['IsMainInterview' => $request['status']]);
        return true;
    }
}
