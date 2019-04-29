<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_oa_order".
 *
 * @property int $id
 * @property int $group_order_id
 * @property int $order_id 订单ID
 * @property int $flow_id 流程ID
 * @property int $series_id 流程组标识,顶级流程id
 * @property int $pay_list_id 支付ID
 * @property int $total_amount 总金额
 * @property int $coupon_amount 优惠金额
 * @property int $actual_amount 实付金额
 * @property int $order_status 1.通知订单、2.通知已付款、3.确认款项、4确认已备货、5.确认发货时间、6确认发货、7确认到货, 8 审核订单信息
 * @property int $ship_status 发货状态: 2待备货,  3, 已备货,  4, 确认发货时间,   5,  已发货,  6, 已收货
 * @property int $pay_status 支付状态  0 未支付   1 已支付
 * @property string $pre_delivery_date 预计发货时间
 * @property string $delivery_date 发货时间
 * @property string $pre_arrive_date 预计到货时间
 * @property string $arrive_date 到货时间
 * @property int $order_type 订单状态: 1, 物料订单,  2, 大器械订单, 3, 小器械订单, 4, 智能器械订单,  5, 道具订单  6. 摄像头订单,7 赠品订单
 * @property int $is_presale 是否是预售处订单: 0 不是,  1 是
 * @property string $order_time 订单时间
 * @property int $project_id 项目ID: t_open_project.id
 * @property string $receiver_name 收件人姓名
 * @property string $receiver_address 收件人地址
 * @property string $receiver_phone 收件人电话
 * @property string $ship_company_name 物流名称
 * @property string $ship_number
 * @property int $opeator 操作人
 * @property string $update_time 操作时间
 * @property string $create_time 创建时间
 * @property int $province_id 省份ID
 * @property string $province_name 省份名字
 * @property int $is_replenishment
 * @property int $city_id 城市ID
 * @property string $city_name 城市名字
 * @property int $district_id 地区ID
 * @property string $district_name 地区名称
 * @property int $confirm_order 新流程的订单确认 1 通过 2驳回
 * @property int $purchase_order_id 一站式采购订单id
 * @property int $work_item_id
 * @property int $sub_order_type
 * @property int $total_num
 */
class OrderEntry extends Base
{
    // confirm_order
    const AGREE = 1;
    const REJECT = 2;

    // order_status
    const SUBMIT_ORDER = 1;
    const CONFIRM_ORDER = 2;
    const SUBMIT_PAYMENT = 3;
    const CONFIRM_PAYMENT = 4;
    const CONFIRM_DELIVERY = 5; //提交期望到货时间
    const ALREADY_DELIVERY = 6; //确认已发货
    const CONFIRM_RECEIVER = 7; //确认已收货

    const REPLENISHMENT = 2;

    //sub_order_type
    const OPEN = 1;
    const DECORATION = 2;

    //special order type
    const SMART_ORDER = 3;
    const PRESALE_ORDER = 9;
    const DECORATION_ORDER = 6;
    const DEMOLITION = 10;

    public static function primaryKey()
    {
        return ["order_id"];
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_order_entry';
    }


    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'group_order_id' => 'Group Order ID',
            'order_id' => 'Order ID',
            'flow_id' => 'Flow ID',
            'series_id' => 'Series ID',
            'pay_list_id' => 'Pay List ID',
            'total_amount' => 'Total Amount',
            'coupon_amount' => 'Coupon Amount',
            'actual_amount' => 'Actual Amount',
            'order_status' => 'Order Status',
            'ship_status' => 'Ship Status',
            'pay_status' => 'Pay Status',
            'pre_delivery_date' => 'Pre Delivery Date',
            'delivery_date' => 'Delivery Date',
            'pre_arrive_date' => 'Pre Arrive Date',
            'arrive_date' => 'Arrive Date',
            'order_type' => 'Order Type',
            'is_presale' => 'Is Presale',
            'order_time' => 'Order Time',
            'project_id' => 'Project ID',
            'receiver_name' => 'Receiver Name',
            'receiver_address' => 'Receiver Address',
            'receiver_phone' => 'Receiver Phone',
            'ship_company_name' => 'Ship Company Name',
            'ship_number' => 'Ship Number',
            'opeator' => 'Opeator',
            'update_time' => 'Update Time',
            'create_time' => 'Create Time',
            'province_id' => 'Province ID',
            'province_name' => 'Province Name',
            'city_id' => 'City ID',
            'city_name' => 'City Name',
            'district_id' => 'District ID',
            'district_name' => 'District Name',
            'confirm_order' => 'Confirm Order',
            'purchase_order_id' => 'Purchase Order ID',
        ];
    }

    public function beforeSave($insert)
    {
        $time = time();
        if ($insert) {
            $this->create_time = $time;
        }
        $this->update_time = $time;
        $this->opeator = Yii::$app->user->getId();
        return parent::beforeSave($insert);
    }

    public function getGym()
    {
        return $this->hasOne(OpenProject::class, ['id' => 'project_id']);
    }

    public function getPurchaseOrder()
    {
        return $this->hasOne(Order::class, ["order_id" => "purchase_order_id"]);
    }

    public function getStaff()
    {
        return $this->hasOne(Staff::class, ["id" => "purchase_staff"]);
    }

    public function getOrderGood()
    {
        return $this->hasMany(OrderGood::class, ["order_id" => "order_id"])
            ->andWhere([OrderGood::tableName() . ".is_valid" => AVAILABLE]);
    }

    public function getOrderAppendant()
    {
        return $this->hasMany(OrderAppendant::class, ["order_id" => "order_id"]);
    }

    public static function convert2string(&$results, $labels = [])
    {
        $labels["order_status"] = "order_entry.order_status";
        $labels["order_type"] = "order_entry.order_type.name";
        $labels["create_time"] = function ($val) {
            return date("Y-m-d H:i:s", $val);
        };
        parent::convert2string($results, $labels);
    }
}
