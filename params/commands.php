<?php
/**
 * Created by PhpStorm.
 * @Author     : fangxing@likingfit.com
 * @CreateTime 18/3/3 13:29:05
 */
return [
    'confirm-plan' => [
        'class' => app\services\OrderFlowService::class,
        'save'  => 'confirmPlanTime'
    ],
    //确认开店订单
    'pick-order'       => [
        'class' => app\services\OrderFlowService::class,
        'init'  => 'pickOrderInit',
        'save'  => 'pickOrderSave'
    ],
    'common-order'     => [
        'class' => app\services\OrderFlowService::class,
        'init'  => 'commonOrderInit',
        'save'  => 'saveCommonOrder'
    ],
    'decoration-order' => [
        'class' => app\services\OrderFlowService::class,
        'init' => 'specialOrderInit',
        'save' => 'saveDecorationOrder'
    ],
    'demolition-order' => [
        'class' => app\services\OrderFlowService::class,
        'init' => 'specialOrderInit',
        'save'  => 'saveDemolitionOrder'
    ],
    'confirm-order' => [
        'class' => app\services\OrderFlowService::class,
        'init' => 'getOrderInfoByOrderType',
        'save' => 'confirmOrderSave'
    ],
    'entry-pay' => [
        'class' => app\services\OrderFlowService::class,
        'init' => 'getOrderSummary',
        'save' => 'entryPaySave'
    ],
    'confirm-pay' => [
        'class' => app\services\OrderFlowService::class,
        'init' => 'confirmPayInit',
        'save' => 'confirmPaySave'
    ],
    //------------加盟签约---------------
    // 商务洽谈
    'negotiate'            => [
        'class' => app\services\WorkflowCustomerService::class,
        'init'  => 'negotiateInit',
        'save'  => 'negotiateSave'
    ],
    // 登记面试结果
    'interview'            => [
        'class' => app\services\WorkflowCustomerService::class,
        'init'  => 'interviewInit',
        'save'  => 'interviewSave'
    ],
    // 录入开店合同
    'contract-record'      => [
        'class' => app\services\WorkflowContractService::class,
        'init'  => 'contractRecordInit',
        'save'  => 'contractRecordSave'
    ],
    // 录入签约费用
    'contract-record-fee'  => [
        'class' => app\services\WorkflowContractService::class,
        'init'  => 'contractRecordFeeInit',
        'save'  => 'contractRecordFeeSave'
    ],
    //指定流程专员
    'record-flow-staff'         => [
        'class' => app\services\WorkflowStaffService::class,
        'init'  => 'recordFlowStaffInit',
    ],
    // 指定流程专员/财务专员/项目专员
    'record-staff'         => [
        'class' => app\services\WorkflowStaffService::class,
        'save'  => 'recordStaffSave',
    ],
    // 确认签约合同
    'contract-sure'        => [
        'class' => app\services\WorkflowContractService::class,
        'init'  => 'contractSureInit',
        'save'  => 'contractSureSave'
    ],
    //指定财务专员
    'record-finance-staff'         => [
        'class' => app\services\WorkflowStaffService::class,
        'init'  => 'recordFinanceStaffInit',
    ],
    // 确认签约费用
    'contract-fee-sure'    => [
        'class' => app\services\WorkflowContractService::class,
        'init'  => 'contractFeeSureInit',
        'save'  => 'contractFeeSureSave'
    ],
    //------------选址签约---------------
    //指定项目专员（合营）
    'record-project-staff'         => [
        'class' => app\services\WorkflowStaffService::class,
        'init'  => 'recordProjectStaffInit',
    ],
    // 记录租房合同（合营）
    'rent-contract-record' => [
        'class' => app\services\WorkflowRentService::class,
        'init'  => 'rentContractRecordInit',
        'save'  => 'rentContractRecordSave'
    ],
    // 记录租房费用（合营）
    'rent-fee-record'      => [
        'class' => app\services\WorkflowRentService::class,
        'init'  => 'rentFeeRecordInit',
        'save'  => 'rentFeeRecordSave'
    ],
    // 确认租房合同（合营，直营）
    'rent-contract-sure'   => [
        'class' => app\services\WorkflowRentService::class,
        'init'  => 'rentContractSureInit',
        'save'  => 'rentContractSureSave'
    ],
    // 确认租房费用（合营）
    'rent-fee-sure'        => [
        'class' => app\services\WorkflowRentService::class,
        'init'  => 'rentFeeSureInit',
        'save'  => 'rentFeeSureSave'
    ],
    // 确认租房费用（直营）
    'rent-fee-time-sure'        => [
        'class' => app\services\WorkflowRentService::class,
        'init'  => 'rentFeeTimeSureInit',
        'save'  => 'rentFeeTimeSureSave'
    ],
    //---------------------------

    /*装修施工*/
    // 确认施工队入场
    'constration-team-enter'     => [
        'class' => app\services\WorkflowConstrationService::class,
        'save'  => 'constrationTeamEnterSave'
    ],
    // 确认骏工报告通过
    'project-report-pass'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'save'  => 'projectReportPassSave'
    ],
    // 录入订单期望到货日期
    'input-order-arrive'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'init'  => 'inputOrderArriveInit',
        'save'  => 'inputOrderArriveSave'
    ],
    // 录入订单发货信息
    'input-order-deliver'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'init'  => 'inputOrderDeliverInit',
        'save'  => 'inputOrderDeliverSave'
    ],
    // 确认订单到货
    'order-arrive-sure'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'init'  => 'orderArriveSureInit',
        'save'  => 'orderArriveSureSave'
    ],
    // 确认智能设备调试完成
    'brow-device-finish'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'init'  => 'browDeviceFinishInit',
        'save'  => 'browDeviceFinishSave'
    ],
    // 提交预售时间及成本
    'presell-time-sure'        => [
        'class' => app\services\WorkflowConstrationService::class,
        'init'  => 'presellTimeSureInit',
        'save'  => 'presellTimeSureSave'
    ],

    /*营业准备*/
    // 录入开业批准日期
    'entry-opening-approval'        => [
        'class' => app\services\WorkflowBusinessService::class,
        'save'  => 'entryOpeningApprovalSave'
    ],
    // 录入开店成本
    'entry-shop-cost'        => [
        'class' => app\services\WorkflowBusinessService::class,
        'init'  => 'entryShopCostInit',
        'save'  => 'entryShopCostSave'
    ],
    // 录入正式营业日期
    'entry-business-date'        => [
        'class' => app\services\WorkflowBusinessService::class,
        'save'  => 'entryBusinessDateSave'
    ],

];