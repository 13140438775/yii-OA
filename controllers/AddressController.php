<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/15 10:41:04
 */

namespace app\controllers;

use Yii;
use app\amap\Amap;
use app\models\OpenProject;
use app\services\RoleService;
use app\services\StaffService;
use app\data\ActiveDataProvider;
use app\exceptions\CustomerException;
use app\models\Address;
use app\models\AddressContract;
use app\models\Base;
use app\models\Customer;
use app\models\Staff;
use app\services\AddressService;
use yii\helpers\ArrayHelper;
use app\exceptions\Address as AddressException;

class AddressController extends BaseController
{
    /**
     * 地址范围
     * 1公里约等于0.009009经度／纬度
     */
    const RANGE_NUM = 3 * 0.009009;

    /**
     * 取消预留
     */
    const OCCUPANCY_CANCEL = 2;

    /**
     * 侧边栏地址搜索
     *
     * @return \app\models\Address|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/15 10:43:29
     * @Author     : pb@likingfit.com
     */
    public function actionSearch()
    {
        $provinceId = Yii::$app->request->post("province_id", '');
        $cityId     = Yii::$app->request->post("city_id", '');
        $districtId = Yii::$app->request->post("district_id", '');

        $address = Yii::$app->request->post("address", '');
        $addressType = Yii::$app->request->post("address_type", '');

        return Address::find()
            ->select(['id', 'province_name', 'city_name', 'district_name', 'address', 'address as value'])
            ->where([
                'audit_status' => AVAILABLE,
            ])
            ->andWhere([
                'in','occupancy',[0,1]
            ])
            ->andFilterWhere([
                'province_id' => $provinceId,
                'city_id'     => $cityId,
                'district_id' => $districtId,
                'audit_type'  => $addressType
            ])
            ->andFilterWhere(['like', 'address', $address])
            ->limit(Address::$workFlowLimitNum)
            ->orderBy(['create_time' => SORT_DESC])
            ->asArray()
            ->all();
    }

    /**
     * 地址详情
     *
     * @return Address|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/15 11:46:32
     * @Author     : pb@likingfit.com
     */
    public function actionInfo()
    {
        $addressId = Yii::$app->request->post("address_id");
        $address   = AddressService::get(['id' => $addressId]);

        if (!is_null($address)) {
            $address['gym_name'] = '';
            $openProject         = OpenProject::find()->where(['address_id' => $addressId])->one();
            if (!is_null($openProject)) {
                $address['gym_name'] = $openProject->gym_name;
            }
        }

        return $address;
    }

    /**
     * 地址新增&编辑
     *
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/20 15:40:32
     * @Author     : pb@likingfit.com
     */
    public function actionSave()
    {
        $post = Yii::$app->request->post();

        Yii::$app->db->transaction(
            function () use ($post) {
                $addressId = ArrayHelper::getValue($post, 'address_id', 0);
                $address   = Address::find()->where(['id' => $addressId])->one();
                if (is_null($address)) {
                    $address = new Address();
                }

                // 如果为选址专员则默认指定自己
                $role = RoleService::getInstance();
                if ($role->checkRole(RoleService::SELECTION_STAFF)) {
                    $staff               = Staff::find()
                        ->where(['id' => $role->userId])
                        ->one();
                    $post['staff_id']    = $staff->id;
                    $post['staff_name']  = $staff->name;
                    $post['staff_phone'] = $staff->phone;
                }

                $post['property_costs'] *= 100;
                $post['rent']           *= 100;
                $post['deposit']        *= 100;
                $post['operator']       = Yii::$app->user->getIdentity()->id;
                $address->setAttributes($post, false);
                $address->save();

                if ($post['is_presale']) {
                    $post['presale']['address_id'] = $address->id;
                    AddressService::savePresale($post['presale']);
                }

                if ($post['is_contract']) {
                    $post['contract']['address_id'] = $address->id;
                    AddressService::saveContract($post['contract']);
                }
            }
        );
    }

    /**
     * 地址池列表
     *
     * @return ActiveDataProvider
     * @CreateTime 2018/3/20 10:39:22
     * @Author     : pb@likingfit.com
     */
    public function actionList()
    {
        $request   = Yii::$app->request;
        $address   = $request->post('address', '');
        $auditType = $request->post('audit_type', '');
        $stage     = $request->post('stage', '');
        $staffName = $request->post('staff_name', '');
        $occupancy = $request->post('occupancy', '');
        $useArea   = $request->post('use_area', []);
        $rent      = $request->post('rent', []);
        $page      = $request->post('page', 0);
        $pageSize  = $request->post('page_size', 15);

        // 设置角色条件
        $role        = RoleService::getInstance();
        $roleCityIds = [];
        if ($role->checkRole(RoleService::SELECTION_GROUPER)) {
            $roleCityIds = StaffService::getGroupCity($role->userId);
        } else if ($role->checkRole(RoleService::SELECTION_STAFF)) {
            $roleCityIds = StaffService::getCity($role->userId);
        }
        $roleCityIds   = ArrayHelper::getColumn($roleCityIds, 'city_id');
        $roleOccupancy = [];
        $roleStage     = [];
        if ($role->checkRole(RoleService::MERCHANTS_MANAGER) || $role->checkRole(RoleService::MERCHANTS_STAFF)) {
            $roleOccupancy = [
                Address::$occupancyAlive,
                Address::$occupancyReserved
            ];
            $roleStage     = [
                Address::$waitStageContract,
                Address::$stageContract
            ];
            $auditType     = Address::$auditConsortium;
        }

        $query = Address::find()
            ->filterWhere(['or',
                           ['like', 'province_name', $address],
                           ['like', 'city_name', $address],
                           ['like', 'district_name', $address],
                           ['like', 'address', $address],
                           ['like', 'staff_name', $staffName],
                           ['like', 'business_circle', $address],
                           ['like', 'occupancy_name', $occupancy],
                           ['like', 'occupancy_phone', $occupancy],])
            ->andFilterWhere([
                'audit_type' => $auditType,
                'stage'      => $stage])
            ->andFilterWhere(['between', 'use_area', $useArea[0], $useArea[1]])
            ->andFilterWhere(['between', 'rent', $rent[0] * 100, $rent[1] * 100])
            ->andFilterWhere(['in', 'city_id', $roleCityIds])
            ->andFilterWhere(['in', 'occupancy', $roleOccupancy])
            ->andFilterWhere(['in', 'stage', $roleStage])
            ->orderBy(['create_time' => SORT_DESC]);

        return new ActiveDataProvider(['query'      => $query,
                                       'attributes' => [
                                           'address_type' => function ($row) {
                                               return Base::getTypeText(Address::$addressTypeText, $row['address_type']);
                                           },
                                           'audit_type'   => function ($row) {
                                               return Base::getTypeText(Address::$auditTypeText, $row['audit_type']);
                                           }],
                                       'pagination' => [
                                           'page'     => $page - 1,
                                           'pageSize' => $pageSize,
                                       ]]);
    }

    /**
     * 申请评审
     * @return mixed
     * @throws \Throwable
     * @CreateTime 2018/4/9 12:02:55
     * @Author     : pb@likingfit.com
     */
    public function actionRecordReview()
    {
        $addressIds = Yii::$app->request->post('address_ids');

        Yii::$app->db->transaction(function () use ($addressIds) {
            foreach ($addressIds as $addressId) {
                $address        = Address::find()
                    ->where([
                        'id'    => $addressId,
                        'stage' => Address::$stageReviewAlive
                    ])
                    ->one();
                $address->stage = Address::$stageReviewWait;
                $address->save();
            }
        });
    }

    /**
     * 地址评审
     * @return bool
     * @CreateTime 2018/3/21 10:45:13
     * @Author     : pb@likingfit.com
     */
    public function actionAudit()
    {
        $addressId   = Yii::$app->request->post('address_id');
        $auditStatus = Yii::$app->request->post('audit_status');

        $address = Address::find()->where(['id' => $addressId])->one();
        $address->setAttributes(Yii::$app->request->post(), false);

        if ($auditStatus) {
            $address->stage = Address::$waitStageContract;
            $address->occupancy = Address::$occupancyAlive;
        } else {
            $address->stage     = Address::$stageInvalid;
            $address->occupancy = Address::$occupancyInvalid;
        }

        return $address->save();
    }

    /**
     * 指定专员
     * @return mixed
     * @throws \Throwable
     * @CreateTime 2018/4/10 16:52:00
     * @Author     : pb@likingfit.com
     */
    public function actionRecordStaff()
    {
        $post = Yii::$app->request->post();

        Yii::$app->db->transaction(function () use ($post) {
            $staff = Staff::find()->where(['id' => $post['staff_id']])->one();

            foreach ($post['address_ids'] as $addressId) {
                $address = Address::find()
                    ->where([
                        'id' => $addressId
                    ])
                    ->one();

                $address->staff_id    = $staff->id;
                $address->staff_name  = $staff->name;
                $address->staff_phone = $staff->phone;
                $address->save();
            }
        });
    }

    /**
     * 地址合同签约
     * @return mixed
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/4/4 16:44:36
     * @Author     : pb@likingfit.com
     */
    public function actionRecordContract()
    {
        $post                       = Yii::$app->request->post();
        $post['receive_start_time'] = strtotime($post['receive_start_time']);
        $post['receive_end_time']   = strtotime($post['receive_end_time']);

        Yii::$app->db->transaction(function () use ($post) {
            $addressContract = new AddressContract();
            $addressContract->setAttributes($post, false);
            $addressContract->save();

            $address              = Address::find()
                ->where(['id' => $post['address_id']])
                ->one();
            $address->is_contract = AVAILABLE;
            $address->stage       = Address::$stageContract;
            $address->occupancy   = Address::$occupancyAlive;
            $address->save();
        });
    }

    /**
     * 预留客户
     * @return bool
     * @throws CustomerException
     * @CreateTime 2018/3/21 11:49:29
     * @Author     : pb@likingfit.com
     */
    public function actionOccupancy()
    {
        $type           = Yii::$app->request->post('type');
        $addressId      = Yii::$app->request->post('address_id');
        $occupancyPhone = Yii::$app->request->post('occupancy_phone');

        if ($type == self::OCCUPANCY_CANCEL) {
            $address                  = Address::find()->where(['id' => $addressId])->one();
            $address->occupancy       = Address::$occupancyAlive;
            $address->occupancy_name  = '';
            $address->occupancy_phone = '';
            return $address->save();
        }

        $customer = Customer::find()->where(['phone' => $occupancyPhone])->one();
        if (empty($customer) || $customer->audition !== Customer::INTERVIEW_PASS) {
            throw new CustomerException(CustomerException::NO_AUDITION);
        }

        $address = Address::find()->where(['occupancy_phone' => $occupancyPhone])->one();
        if (!is_null($address)) {
            throw new CustomerException(CustomerException::OCCUPANCY_ADDRESS_OVER);
        }

        $address                  = Address::find()->where(['id' => $addressId])->one();
        $address->occupancy       = Address::$occupancyReserved;
        $address->occupancy_phone = $occupancyPhone;
        $address->occupancy_name  = $customer->name;

        return $address->save();
    }

    /**
     * 获取范围内的所有健身房
     * @return array
     * @throws AddressException
     * @CreateTime 2018/4/13 11:55:15
     * @Author     : pb@likingfit.com
     */
    public function actionGetRange()
    {
        $cityName  = Yii::$app->request->post('city_name');
        $address   = Yii::$app->request->post('address');
        $addressId = Yii::$app->request->post("address_id", '');

        $location = Amap::search($cityName, $address);
        if (!$location['longitude'] && !$location['latitude']) {
            throw new AddressException(AddressException::INVALID);
        }

        $range = AddressService::getRoundRange($location['longitude'], $location['latitude'], self::RANGE_NUM, $addressId);
        return ['location' => $location, 'range' => $range];
    }
}