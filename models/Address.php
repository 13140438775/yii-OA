<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * This is the model class for table "t_address".
 *
 * @property int    $id
 * @property int    $province_id       省份ID
 * @property string $province_name     省份名字
 * @property int    $city_id           城市ID
 * @property string $city_name         城市名字
 * @property int    $district_id       地区ID
 * @property string $district_name     地区名称
 * @property string $address           收件人地址
 * @property string $business_circle   商圈
 * @property int    $build_area        建筑面积
 * @property int    $use_area          实用面积
 * @property string $contact_name      联系人
 * @property int    $contact_phone     联系号码
 * @property int    $is_presale        是否有预售处 0 没有 1有
 * @property int    $is_contract       是否签约 0 没有 1有
 * @property string $property_company  物业公司
 * @property int    $property_costs    物业费
 * @property string $property_name     物业联系人
 * @property string $property_contract 物业联系方式
 * @property int    $address_type      签约类型（1线下租赁 2商务合作）
 * @property int    $rent              房租
 * @property int    $deposit           押金
 * @property int    $billing_cycle     支付周期
 * @property string $growth_rate       增长幅度
 * @property int    $growth_remark     增长说明
 * @property int    $staff_id          选址专员
 * @property string $report            分析报告地址
 * @property int    $stage             1 待申请  2 待评审 3待签约 4已签约 5无效地址
 * @property int    $audit_status      评审结果（0:未通过 1:通过）
 * @property int    $audit_type        评审类型（1:直营 2:合营）
 * @property int    $audit_remark      评审备注
 * @property int    $occupancy         0 未预留 1已预留
 * @property int    $occupancy_phone   预留客户手机号
 * @property int    $operator          操作人
 * @property int    $create_time       创建时间
 * @property int    $update_time       更新时间
 */
class Address extends ActiveRecord
{
    /**
     * 侧边栏限制搜索数目
     * @var int
     */
    public static $workFlowLimitNum = 6;

    /**
     * 待申请评审
     * @var int
     */
    public static $stageReviewAlive = 1;
    /**
     * 待评审
     * @var int
     */
    public static $stageReviewWait = 2;
    /**
     * 待签约
     * @var int
     */
    public static $waitStageContract = 3;
    /**
     * 已签约
     * @var int
     */
    public static $stageContract = 4;
    /**
     * 无效
     * @var int
     */
    public static $stageInvalid = 5;

    /**
     * 预约可用
     * @var int
     */
    public static $occupancyAlive = 1;
    /**
     * 已预留
     * @var int
     */
    public static $occupancyReserved = 2;
    /**
     * 已占用
     * @var int
     */
    public static $occupancyUsed = 3;
    /**
     * 无效
     * @var int
     */
    public static $occupancyInvalid = 4;

    /**
     * 合营
     * @var int
     */
    public static $auditConsortium = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_address';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id'                => 'ID',
            'province_id'       => 'Province ID',
            'province_name'     => 'Province Name',
            'city_id'           => 'City ID',
            'city_name'         => 'City Name',
            'district_id'       => 'District ID',
            'district_name'     => 'District Name',
            'address'           => 'Address',
            'business_circle'   => 'Business Circle',
            'build_area'        => 'Build Area',
            'use_area'          => 'Use Area',
            'contact_name'      => 'Contact Name',
            'contact_phone'     => 'Contact Phone',
            'is_presale'        => 'Is Presale',
            'property_company'  => 'Property Company',
            'property_costs'    => 'Property Costs',
            'property_name'     => 'Property Name',
            'property_contract' => 'Property Contract',
            'address_type'      => 'Address Type',
            'rent'              => 'Rent',
            'deposit'           => 'Deposit',
            'billing_cycle'     => 'Billing Cycle',
            'growth_rate'       => 'Growth Rate',
            'growth_remark'     => 'Growth Remark',
            'staff_id'          => 'Staff ID',
            'report'            => 'Report',
            'stage'             => 'Stage',
            'audit_type'        => 'Audit Type',
            'audit_remark'      => 'Audit Remark',
            'occupancy'         => 'Occupancy',
            'occupancy_phone'   => 'Occupancy Phone',
            'operator'          => 'Operator',
            'create_time'       => 'Create Time',
            'update_time'       => 'Update Time',
        ];
    }

    /**
     * 预售处信息
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/14 15:29:04
     * @Author     : pb@likingfit.com
     */
    public function getPresale()
    {
        return $this->hasOne(AddressPresale::class, ['address_id' => 'id']);
    }

    /**
     * 签约信息
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/14 15:29:19
     * @Author     : pb@likingfit.com
     */
    public function getContract()
    {
        return $this->hasOne(AddressContract::class, ['address_id' => 'id']);
    }

    public static $addressTypeText = [
        1 => '线下租赁',
        2 => '商务合作',
    ];

    public static $stageText = [
        1 =>'待申请',
        2 =>'待评审',
        3 =>'待签约',
        4 =>'已签约',
        5 =>'无效地址',
    ];

    public static $auditTypeText = [
        1 => '合营',
        2 => '直营',
    ];

    public static $occupancyText = [
        1 => '未预留',
        2 => '已预留',
        3 => '已占用',
    ];

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
