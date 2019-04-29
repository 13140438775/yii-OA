<?php

namespace app\models;

use Yii;
use Yii\db\ActiveRecord;

/**
 * This is the model class for table "t_open_contract".
 *
 * @property int $id
 * @property int $flow_id 流程ID
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $pay_list_id 多次支付列表ID
 * @property int $area_id
 * @property string $area_name
 * @property int $province_id 合同省份ID
 * @property string $province_name 合同省份名字
 * @property int $city_id 城市ID
 * @property string $city_name 城市名字
 * @property int $district_id 合同地区ID
 * @property string $district_name 合同地区名称
 * @property string $franchisee_name 加盟商名称
 * @property string $phone
 * @property string $total_fee 合同总价
 * @property string $start_date 合同开始日期
 * @property string $end_date 合同结束日期
 * @property int $has_card_pic 是否提供身份证复印件:  0 不提供,  1 提供
 * @property string $card_pic_path 图片路径
 * @property int $confirm_status 合同审核状态:  0 驳回,  1, 通过
 * @property string $contract_sn 确认线下合同单号
 * @property int $is_cancel 是否取消: 0 未取消,  1, 已取消
 * @property string $coefficient 加盟系数
 * @property string $create_time 创建时间
 * @property string $franchisee_phone 加盟商电话
 * @property string $franchisee_email 加盟商邮箱
 * @property int $disclaimer 免责申明
 * @property int $std_address 选址标准
 * @property string $gym_name
 */
class OpenContract extends Base
{
    const CONTRACT_LIMIT=15;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_open_contract';
    }

    public static function getFranChiseeBySeriesId($seriesId)
    {
        return self::find()
            ->where(["series_id" => $seriesId])
            ->one();
    }

    public static function getFranChiseeBySeriesIdName($seriesId,$namePhone)
    {
        return self::find()
            ->where(["series_id" => $seriesId])
            ->andWhere(['like','franchisee_name', $namePhone])
            ->orWhere(['franchisee_phone' => $namePhone])
            ->asArray()
            ->one();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flow_id' => 'Flow ID',
            'series_id' => 'Series ID',
            'pay_list_id' => 'Pay List ID',
            'area_id' => 'Area ID',
            'area_name' => 'Area Name',
            'province_id' => 'Province ID',
            'province_name' => 'Province Name',
            'city_id' => 'City ID',
            'city_name' => 'City Name',
            'district_id' => 'District ID',
            'district_name' => 'District Name',
            'franchisee_name' => 'Franchisee Name',
            'phone' => 'Phone',
            'total_fee' => 'Total Fee',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'has_card_pic' => 'Has Card Pic',
            'card_pic_path' => 'Card Pic Path',
            'confirm_status' => 'Confirm Status',
            'contract_sn' => 'Contract Sn',
            'is_cancel' => 'Is Cancel',
            'coefficient' => 'Coefficient',
            'create_time' => 'Create Time',
            'franchisee_phone' => 'Franchisee Phone',
            'franchisee_email' => 'Franchisee Email',
            'disclaimer' => 'Disclaimer',
            'std_address' => 'Std Address',
            'gym_name' => 'Gym Name',
        ];
    }

    /**
     * @param 通过加盟商姓名或手机查询
     * @throws LoginException
     * @CreateTime 18/3/7 2:42:59
     * @Author: chenxuxu@likingfit.com
     */
    public static function getContractInfoByNamePhone($namePhone)
    {
        return self::find()
            ->select("series_id,franchisee_name,franchisee_phone")
            ->where(["like",'franchisee_name' , $namePhone])
            ->orWhere(['franchisee_phone' => $namePhone])
            ->limit(self::CONTRACT_LIMIT)
            ->asArray()
            ->all();
    }

    public function getGym()
    {
        return $this->hasOne(OpenProject::class, ['series_id' => 'series_id']);
    }

    public function getPayList(){
        return $this->hasOne(PayList::class,['id'=>'pay_list_id']);
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ["phone" => "franchisee_phone"]);
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = time();
        }
        $this->update_time = time();
        return true;
    }
}
