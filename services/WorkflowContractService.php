<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/6 15:26:35
 */

namespace app\services;

use app\models\Customer;
use app\models\PayCertificate;
use app\models\PayTask;
use Yii;
use app\models\OpenContract;
use app\models\PayList;

class WorkflowContractService
{
    /**
     * 合同费用 全部到账
     * @var int
     */
    public static $contractFeeAll = 1;

    /**
     * 侧边栏-合同录入
     * @param $request
     * @return array
     * @CreateTime 2018/3/29 16:44:06
     * @Author     : pb@likingfit.com
     */
    public static function contractRecordInit($request)
    {
        $openContract = OpenContract::find()
            ->select(['franchisee_name', 'franchisee_phone', 'start_date', 'end_date', 'total_fee', 'gym_name'])
            ->where(['series_id' => $request['flow']['series_id']])
            ->asArray()
            ->one();

        return $openContract;
    }

    /**
     * 侧边栏-合同录入
     * @param $request
     * @return mixed
     * @throws \Throwable
     * @CreateTime 2018/4/21 10:54:04
     * @Author     : pb@likingfit.com
     */
    public static function contractRecordSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $openContract = OpenContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();

            if (is_null($openContract)) {
                $openContract = new OpenContract();
            }

            $request['series_id'] = $request['flow']['series_id'];
            $request['total_fee'] *= 100;

            unset($request['remark']);
            unset($request['command']);
            unset($request['flow']);
            $openContract->setAttributes($request, false);
            $openContract->save();

            $customer                 = Customer::find()
                ->where(['phone' => $openContract->franchisee_phone])
                ->one();
            $customer->docking_status = Customer::SIGNING_SUCCESS;
            return $customer->save();
        });
    }

    /**
     * 侧边栏-合同费用录入
     * @param $request
     * @return OpenContract|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/6 15:57:41
     * @Author     : pb@likingfit.com
     */
    public static function contractRecordFeeInit($request)
    {
        $open          = OpenContract::find()
            ->select(['franchisee_name', 'franchisee_phone', 'start_date', 'end_date', 'total_fee'])
            ->where(['series_id' => $request['flow']['series_id']])
            ->asArray()
            ->one();
        $open['tasks'] = WorkflowService::getOpenContract($request['flow']['series_id']);;
        return $open;
    }

    /**
     * 侧边栏-合同费用录入
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/8 19:37:33
     * @Author     : pb@likingfit.com
     */
    public static function contractRecordFeeSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            // payTask、payCertificate
            if (isset($request['tasks'][0]['pay_list_id'])) {
                $payTasks = PayTask::find()
                    ->where(['pay_list_id' => $request['tasks'][0]['pay_list_id']])
                    ->all();

                $payTaskIds = [];
                foreach ($payTasks as $payTask) {
                    $payTaskIds[]      = $payTask->id;
                    $payTask->is_valid = UNAVAILABLE;
                    $payTask->save();
                }

                $payCertificates = PayCertificate::find()
                    ->where(['in', 'pay_task_id', $payTaskIds])
                    ->all();
                foreach ($payCertificates as $payCertificate) {
                    $payCertificate->is_valid = UNAVAILABLE;
                    $payCertificate->save();
                }
            }

            // -----------
            if (isset($request['tasks'][0]['pay_list_id'])) {
                $payList = PayList::find()->where(['id' => $request['tasks'][0]['pay_list_id']])->one();
            } else {
                $payList = new PayList();
            }
            $payList->series_id = $request['flow']['series_id'];
            $payList->save();

            $totalAmount = 0;
            foreach ($request['tasks'] as $item) {
                $item['pay_amount']  *= 100;
                $totalAmount         += $item['pay_amount'];
                $payTask             = new PayTask();
                $item['pay_list_id'] = $payList->id;
                $item['pay_time']    = strtotime($item['pay_time']);
                unset($item['id']);
                $payTask->setAttributes($item, false);
                $payTask->save();

                if (!empty($item['certificate'])) {
                    $rows = [];
                    foreach ($item['certificate'] as $v) {
                        $rows[] = [
                            $payTask->id,
                            str_replace(IMAGE_DOMAIN . '/', '', $v['url']),
                            $v['name'],
                            time()
                        ];
                    }

                    Yii::$app->db->createCommand()
                        ->batchInsert(PayCertificate::tableName()
                            , ['pay_task_id', 'certificate', 'file_name', 'create_time'],
                            $rows)->execute();
                }
            }

            $payList->total_amount  = $totalAmount;
            $payList->actual_amount = $totalAmount;
            $payList->save();

            $openContract              = OpenContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $openContract->pay_list_id = $payList->id;
            $openContract->save();

            return true;
        });
    }

    /**
     * 侧边栏-确认签约合同
     * @param $request
     * @return OpenContract|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/7 09:12:22
     * @Author     : pb@likingfit.com
     */
    public static function contractSureInit($request)
    {
        return OpenContract::find()
            ->select(['franchisee_name', 'franchisee_phone', 'total_fee', 'start_date', 'end_date'])
            ->where(['series_id' => $request['flow']['series_id']])
            ->one();
    }

    /**
     * 侧边栏-确认签约合同
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/14 11:16:54
     * @Author     : pb@likingfit.com
     */
    public static function contractSureSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $openContract                 = OpenContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $openContract->contract_sn    = $request['contract_sn'];
            $openContract->confirm_status = $request['status'];
            $openContract->save();

            FlowService::setVariable($request['flow']['work_item_id'], ['IsContractConfirm' => $request['status']]);

            return true;
        });
    }

    /**
     * 侧边栏-确认签约费用
     * @param $request
     * @return array
     * @CreateTime 2018/3/7 09:51:43
     * @Author     : pb@likingfit.com
     */
    public static function contractFeeSureInit($request)
    {
        return WorkflowService::getOpenContract($request['flow']['series_id']);
    }

    /**
     * 侧边栏-确认签约费用
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/14 11:15:43
     * @Author     : pb@likingfit.com
     */
    public static function contractFeeSureSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $payList             = PayList::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $payList->pay_status = $request['status'];
            $payList->save();

            if ($request['status'] != self::$contractFeeAll) {
                $request['status'] = 0;

                foreach ($request['pay_task_ids'] as $id) {
                    $payTask = PayTask::find()
                        ->where(['id' => $id])
                        ->one();
                    if (!is_null($payTask)) {
                        $payTask->payfee_status = PayTask::$payFeeStatusType;
                        $payTask->save();
                    }
                }
            }

            FlowService::setVariable($request['flow']['work_item_id'], ['IsContractFee' => $request['status']]);

            return true;
        });
    }
}