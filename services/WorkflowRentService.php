<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/7 14:22:16
 */

namespace app\services;

use app\models\Address;
use app\models\GymSeries;
use app\models\OpenContract;
use app\models\OpenProject;
use Yii;
use app\models\Customer;
use app\models\OpenRentContract;
use app\models\Certificate;
use app\models\RentContract;

class WorkflowRentService
{
    /**
     * 侧边栏 - 记录租房合同
     * @param $request
     * @return \app\models\Address|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/15 10:22:35
     * @Author     : pb@likingfit.com
     */
    public static function rentContractRecordInit($request)
    {
        $customer = Customer::find()
            ->where(['id' => $request['flow']['customer_id']])
            ->one();
        if (is_null($customer)) {
            // 直营
            $openRentContract = OpenRentContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $condition        = ['id' => $openRentContract->address_id];
        } else {
            $condition = ['occupancy_phone' => $customer->phone];
        }

        return AddressService::get($condition);
    }

    /**
     * 侧边栏 - 记录租房合同
     * @param $request
     * @return mixed
     * @throws \Throwable
     * @CreateTime 2018/4/11 16:48:49
     * @Author     : pb@likingfit.com
     */
    public static function rentContractRecordSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $address            = Address::find()
                ->where(['id' => $request['address_id']])
                ->one();
            $address->occupancy = Address::$occupancyUsed;
            $address->save();

            // 兼容合营、直营
            $openProject = OpenProject::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            if (is_null($openProject)) {
                $openContract          = OpenContract::find()
                    ->where(['series_id' => $request['flow']['series_id']])
                    ->one();
                $openProject           = new OpenProject();
                $openProject->gym_name = $openContract->gym_name;
            }

            $openProject->series_id     = $request['flow']['series_id'];
            $openProject->address_id    = $request['address_id'];
            $openProject->province_id   = $address->province_id;
            $openProject->province_name = $address->province_name;
            $openProject->city_id       = $address->city_id;
            $openProject->city_name     = $address->city_name;
            $openProject->district_id   = $address->district_id;
            $openProject->district_name = $address->district_name;
            $openProject->address       = $address->address;
            $openProject->gym_area      = $address->use_area;
            $openProject->longitude     = $address->longitude;
            $openProject->latitude      = $address->latitude;
            $openProject->save();

            $gymSeries             = GymSeries::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $gymSeries->project_id = $openProject->id;
            $gymSeries->save();

            $openRentContract = OpenRentContract::find()
                ->where(['series_id'=>$request['flow']['series_id']])
                ->one();
            if(is_null($openRentContract)){
                $openRentContract             = new OpenRentContract;
            }
            $openRentContract->address_id = $request['address_id'];
            $openRentContract->series_id  = $request['flow']['series_id'];
            $openRentContract->save();

            return true;
        });

    }

    /**
     * 侧边栏 - 记录租房费用
     * @param $request
     * @return RentContract|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/7 15:27:56
     * @Author     : pb@likingfit.com
     */
    public static function rentFeeRecordInit($request)
    {
        $orc = OpenRentContract::find()
            ->where(['series_id' => $request['flow']['series_id']])
            ->with(['certificates' => function ($query) {
                $query->select([
                    'object_id',
                    'file_name as name',
                    'certificate as url'
                ])->where(['is_valid' => AVAILABLE]);
            }])
            ->asArray()
            ->one();

        $orc['address'] = AddressService::get(['id' => $orc['address_id']]);
        return $orc;
    }

    /**
     * 侧边栏 - 记录租房费用
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/7 15:58:21
     * @Author     : pb@likingfit.com
     */
    public static function rentFeeRecordSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $openRentContract            = OpenRentContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $openRentContract->pay_money = $request['pay_money'] * 100;
            $openRentContract->save();

            // certificate
            $certs = Certificate::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->all();
            foreach ($certs as $cert) {
                $cert->is_valid = UNAVAILABLE;
                $cert->save();
            }

            $rows = [];
            foreach ($request['certificate'] as $v) {
                $rows[] = [
                    $request['flow']['series_id'],
                    $openRentContract->id,
                    $v['name'],
                    $v['url'],
                    Certificate::$RentFeeType
                ];
            }
            Yii::$app->db->createCommand()
                ->batchInsert(Certificate::tableName()
                    , ['series_id', 'object_id', 'file_name', 'certificate', 'type'],
                    $rows)->execute();

            return true;
        });
    }

    /**
     * 侧边栏 - 确认租房合同
     * @param $request
     * @return RentContract|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/7 16:05:26
     * @Author     : pb@likingfit.com
     */
    public static function rentContractSureInit($request)
    {
        $orc = OpenRentContract::find()
            ->where(['series_id' => $request['flow']['series_id']])
            ->one();

        return AddressService::get(['id' => $orc->address_id]);
    }

    /**
     * 侧边栏 - 确认租房合同
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/12 11:04:52
     * @Author     : pb@likingfit.com
     */
    public static function rentContractSureSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $openRentContract                 = OpenRentContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();
            $openRentContract->confirm_status = $request['status'];
            $openRentContract->save();

            if (!$request['status']) {
                $address            = Address::find()
                    ->where(['id' => $openRentContract->address_id])
                    ->one();
                $address->occupancy = Address::$occupancyAlive;
                $address->save();
            }

            FlowService::setVariable($request['flow']['work_item_id'], ['IsHouseContract' => $request['status']]);

            return true;
        });
    }

    /**
     * 侧边栏 - 确认租房费用（合营,直营）
     * @param $request
     * @return \app\models\Address|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/15 10:36:02
     * @Author     : pb@likingfit.com
     */
    public static function rentFeeSureInit($request)
    {
        $orc = OpenRentContract::find()
            ->where(['series_id' => $request['flow']['series_id']])
            ->one();

        return [
            'address'            => AddressService::get(['id' => $orc->address_id]),
            'open_rent_contract' => WorkflowService::getOpenRentContract($request['flow']['series_id'])
        ];
    }

    /**
     * 侧边栏 - 确认租房费用（合营，直营）
     * @param $request
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/12 14:51:59
     * @Author     : pb@likingfit.com
     */
    public static function rentFeeSureSave($request)
    {
        return Yii::$app->db->transaction(function () use ($request) {
            $openRentContract = OpenRentContract::find()
                ->where(['series_id' => $request['flow']['series_id']])
                ->one();

            if ($request['status'] === "") {
                $openRentContract->fee_confirm_status = AVAILABLE;
            } else {
                $openRentContract->fee_confirm_status = $request["status"];
            }
            if(!empty($request['pay_money'])){
                $openRentContract->pay_money = $request['pay_money'] * 100;
            }
            
            $openRentContract->pay_time  = empty($request['pay_time']) ? time() : strtotime($request['pay_time']);
            $openRentContract->save();

            FlowService::setVariable($request['flow']['work_item_id'], ['IsHouseFee' => $request['status']]);

            return true;
        });
    }
}