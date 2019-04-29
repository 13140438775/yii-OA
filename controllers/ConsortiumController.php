<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/2 15:23:32
 */

namespace app\controllers;

use app\exceptions\CustomerException;
use app\models\Base;
use app\models\GymSeries;
use app\services\GymService;
use app\services\RoleService;
use Yii;
use app\models\Customer;
use app\models\ProjectDirector;
use app\models\WorkItem;
use app\models\Staff;
use app\services\FlowService;

class ConsortiumController extends BaseController
{
    /**
     * 合营流程标识
     * @var string
     */
    private static $_defld = 'Main';

    /**
     * 指定招商专员 - 开始流程
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/8 11:51:35
     * @Author     : pb@likingfit.com
     */
    public function actionAssignPeople()
    {
        $post = Yii::$app->request->post();

        Yii::$app->db->transaction(function () use ($post) {
            foreach ($post['customer_ids'] as $customerId) {
                $staff    = Staff::find()
                    ->where([
                        'id'          => $post['staff_id'],
                        'lock_status' => Staff::UNLOCK])
                    ->one();
                $customer = Customer::find()
                    ->where(['id' => $customerId])
                    ->one();

                if ($customer->audition == Customer::$applyInterview || $customer->audition == Customer::INTERVIEW_PASS) {
                    throw new CustomerException(CustomerException::APPLY_INTERVIEW);
                }

                $customer->is_event       = Base::$available;
                $customer->docking_status = Customer::NEGOTIATIONING;
                $customer->deadline_time  = strtotime("+2 day");
                $customer->dock_staff_id  = $staff->id;
                $customer->audition       = Customer::$applyInterview;
                $customer->save();

                list($flow, $process) = FlowService::startProcess(self::$_defld, 'test');

                $projectDirector = new ProjectDirector();
                $projectDirector->setAttributes([
                    'flow_id'         => $flow->id,
                    'series_id'       => $flow->series_id,
                    'department_id'   => $staff->department_id,
                    'department_name' => $staff->getDepartment()->one()->name,
                    'staff_name'      => $staff->name,
                    'staff_id'        => $staff->id,
                    'customer_id'     => $customerId,
                    'role_name'       => RoleService::getInstance()->currentRoleName
                ], false);
                $projectDirector->save();
                $process->start();

                $gymSeries              = new GymSeries();
                $gymSeries->customer_id = $customerId;
                $gymSeries->series_id   = $flow->series_id;
                $gymSeries->save();

                GymService::recordOpenFlow($flow->series_id);
            }
        });
    }

    /**
     * 新增合营健身房
     */
    public function actionAddHGym()
    {
        $data        = Yii::$app->request->post();
        $customer_id = $data['customer_id'];
        $customer    = Customer::find()
            ->where(['id' => $customer_id])
            ->asArray()
            ->one();
        if ($customer['docking_status'] == Customer::SIGNING_SUCCESS) {
            throw new CustomerException(CustomerException::NO_SIGNING);
        }
        list($flow, $process) = FlowService::startProcess(self::$_defld, 'test', ['IsMainRejoin' => 1]);
        $process->start();
        $item = WorkItem::find()
            ->where(['series_id' => $flow->series_id, 'state' => WorkItem::CLAIMED])
            ->one();

        $gymSeries              = new GymSeries();
        $gymSeries->customer_id = $customer_id;
        $gymSeries->series_id   = $flow->series_id;
        $gymSeries->save();
        return $item;
    }
}