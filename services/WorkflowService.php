<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/6 18:39:58
 */

namespace app\services;

use app\models\Base;
use app\models\Department;
use app\models\OpenContract;
use app\models\OpenPresell;
use app\models\OpenProject;
use app\models\PayCertificate;
use app\models\PayList;
use app\models\PayTask;
use app\models\OpenRentContract;
use app\models\ProjectDirector;
use app\models\Staff;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\db\Expression;
use yii\helpers\ArrayHelper;

/**
 * 侧边栏 - 公共服务
 * Class WorkflowService
 * @package    app\services
 * @CreateTime 2018/3/7 14:41:00
 * @Author     : pb@likingfit.com
 */
class WorkflowService
{
    // 合营
    public static $consortiumType = 1;

    // 直营
    public static $directType = 1;

    /**
     * 获取开店合同信息
     * @param $seriesId
     * @return OpenContract|array|null|ActiveRecord
     * @CreateTime 2018/3/14 11:01:25
     * @Author     : pb@likingfit.com
     */
    public static function getOpenContract($seriesId)
    {
        return OpenContract::find()
            ->select([
                OpenContract::tableName() . '.pay_list_id',
                'franchisee_name',
                'franchisee_phone',
                'start_date',
                'end_date',
                'total_fee',])
            ->with(['payList' => function (ActiveQuery $query) {
                $query->select([
                    PayList::tableName() . '.id',
                    'total_amount','actual_amount',])
                    ->with(['payTask' => function (ActiveQuery $query) {
                        $query->select([
                            PayTask::tableName() . '.id',
                            PayTask::tableName() . '.pay_list_id',
                            'payfee_status',
                            'pay_person',
                            'pay_account',
                            'pay_amount',
                            'receive_person',
                            'receive_account',
                            'pay_time' => new Expression("from_unixtime(pay_time,'%Y-%m-%d')"),])
                            ->with(['certificate' => function (ActiveQuery $query) {
                                $query->select([
                                    'id',
                                    'file_name name',
                                    "concat('" . IMAGE_DOMAIN . "/',certificate) as url",
                                    'pay_task_id'])
                                    ->where([PayCertificate::tableName().'.is_valid'=>AVAILABLE]);
                            }])
                            ->where([PayTask::tableName(). '.is_valid'=>AVAILABLE]);
                        ;
                    }])
                    ->where([PayList::tableName() . '.is_valid'=>AVAILABLE]);
            }])
            ->where([OpenContract::tableName() . '.series_id' => $seriesId])
            ->asArray()
            ->one();
    }

    /**
     * 获取租房合同预售处信息
     * @param $seriesId
     * @return RentContract|array|null|\yii\db\ActiveRecord
     * @CreateTime 2018/3/7 16:03:49
     * @Author     : pb@likingfit.com
     */
    public static function getRentContract($seriesId)
    {
        $rentContract = OpenRentContract::find()
            ->select(['series_id', 'province_name', 'city_name', 'district_name',
                      'address', 'area', 'contract_start', 'contract_end',
                      'deposit', 'rent', 'billing_cycle',
            ])
            ->where(['series_id' => $seriesId])
            ->with(['openPresell' => function (ActiveQuery $query) {
                $query->select([
                    'province_name', 'city_name', 'district_name',
                    'address', 'area'])
                    ->asArray()
                    ->one();
            }])
            ->asArray()
            ->one();

        if (is_null($rentContract['openPresell'])) {
            $rentContract['openPresell'] = [];
        }

        return $rentContract;
    }

    /**
     * 获取部门所有人员
     * @param        $id
     * @param string $leaderType
     * @return \app\models\CustomerFollow[]|Department[]|\app\models\DepartmentCity[]|OpenRentContract[]|\app\models\Region[]|Staff[]|array|ActiveRecord[]
     * @CreateTime 2018/4/28 16:49:53
     * @Author     : pb@likingfit.com
     */
    public static function getDepartmentStaffs($id, $leaderType = '')
    {
        $department =  Department::find()
            ->select('id')
            ->where(['parent_id' => $id])
            ->asArray()
            ->all();
        if(empty($department)){
            $department = [];
        }

        $ids = ArrayHelper::getColumn($department, 'id');
        $ids[] = $id;

        $select = [
            'staff_id' => 't_staff.id',
            'name' => 't_staff.name'
        ];
        $where = [
            'and',
            ['t_staff.staff_status' => Base::$available],
            ['in', 't_staff.department_id', $ids],
        ];
        $staff_project = Staff::find()
            ->select($select)
            ->where($where)
            ->andFilterWhere(['t_staff.is_leader' => $leaderType])
            ->asArray()
            ->all();
        $staff_ids = [];
        foreach($staff_project as $val) {
            $staff_ids[] = $val['staff_id'];
        }

        $select = [
            'staff_id' => 't_project_director.staff_id',
            'count' => new Expression("count(t_open_project.id)")
        ];
        $where = [
            't_open_project.gym_status' => OpenProject::WILL_OPEN,
            't_project_director.staff_id' => $staff_ids
        ];

        $project_count_list = [];
        if(!empty($staff_ids)) {
            $project_count_list = ProjectDirector::find()
                ->select($select)
                ->leftJoin('t_open_project', 't_open_project.series_id = t_project_director.series_id')
                ->where($where)
                ->groupBy('t_project_director.staff_id')
                ->indexBy('staff_id')
                ->asArray()
                ->all();
        }
        foreach($staff_project as &$v) {
            $v['count'] = isset($project_count_list[$v['staff_id']]) ? $project_count_list[$v['staff_id']]['count']: 0;
        }
        return $staff_project;
    }

    /**
     * 获取房屋合同付款信息
     * @param $seriesId
     * @return Department[]|\app\models\OpenContract[]|\app\models\OpenProject[]|\app\models\PayTask[]|RentContract[]|array|ActiveRecord[]
     * @CreateTime 2018/3/12 15:42:45
     * @Author     : pb@likingfit.com
     */
    public static function getRentContractFee($seriesId)
    {
        return OpenRentContract::find()
            ->select([
                OpenRentContract::tableName() . '.pay_list_id',
                'contract_start',
                'contract_end',
                'rent'])
            ->with(['payList' => function (ActiveQuery $query) {
                $query->select([PayList::tableName() . '.id'])
                    ->with(['payTask' => function (ActiveQuery $query) {
                        $query->select([PayTask::tableName() . '.id', 'pay_list_id', 'pay_amount'])
                            ->with(['payCertificate' => function (ActiveQuery $query) {
                                $query->select(["concat('" . IMAGE_DOMAIN . "',certificate) as certificate", 'pay_task_id']);
                            }]);
                    }]);
            }])
            ->where([OpenRentContract::tableName() . '.series_id' => $seriesId])
            ->asArray()
            ->all();
    }


    /**
     * 获取开店合同房租以及凭证信息
     * @param $seriesId
     * @return array|ActiveRecord[]
     * @CreateTime 2018/3/31 14:50:10
     * @Author     : pb@likingfit.com
     */
    public static function getOpenRentContract($seriesId){
        return OpenRentContract::find()
            ->select(['id','pay_money','pay_time','contract_type'])
            ->with(['certificates'=>function(ActiveQuery $query){
                $query->select([
                        'object_id',
                        'file_name',
                        "concat('" . IMAGE_DOMAIN . "/',certificate) as certificate"
                ])
                ->where(['is_valid'=>AVAILABLE]);
            }])
            ->where(['series_id'=>$seriesId])
            ->asArray()
            ->one();
    }
}