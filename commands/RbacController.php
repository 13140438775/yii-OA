<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : fangxing@likingfit.com
 * @CreateTime 2018/3/20 09:59:33
 */

namespace app\commands;

use app\models\Roles;
use app\models\Staff;
use yii\console\Controller;
use yii\helpers\ArrayHelper;
use yii\helpers\Console;
use yii\rbac\Permission;
use yii\rbac\Role;

class RbacController extends Controller
{

    public $roleMapRules = [
        "boss" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "address-list-page",
            "gym-list-page",
            "boss-index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "index/fee-statistic",
            "index/fee-chart",
            "address/list",
            "address/info",
            "address/audit",
            "address/search",



            "customer-order-list-page",
            "customer-refund-list-page",
            "customer-order/get-list",
            "customer-order/order-status",
            "gym/get-gym",
            "customer-order/save-order",
            "customer-order/commit",
            "customer-order/order-detail",
            "customer-order/status",
            "customer-order/finish",
            "customer-order/all-finish",
            "chargeback/get-list",
            "chargeback/order-detail",
            "chargeback/add",
            "chargeback/commit",
            "chargeback/status",
            "chargeback/complete",
            "chargeback/update-refund",
            "customer-order/close",
            "chargeback/charge-status",
            "customer-order/get-remark",
            "chargeback/get-remark",
            "chargeback/update-status",


            "goods-check-list-page",
            "supplier-refund-list-page",
            "purchase-order-list-page",
            "warehouse-in-list-page",
            "warehouse-in-detail-list-page",
            "warehouse-out-detail-list-page",
            "warehouse-out-list-page",
            "give-goods-list-page",
            "goods-list-page",
            "refund-goods-list-page",
            "refund-goods-detail-list-page",
            "pur-check-list-page",
            "cancel-list-page",
            "Warehouse-list-page",
            "order-purchase-check-list-page",
            "refund-purchase-check-list-page",
            "goods/goods-list",
            "goods/type",
            "goods/type-sub",
            "goods/by-type-supplier",
            "goods/save",
            "goods/goods-detail",
            "customer-order/pur-order-detail",
            "chargeback/get-pur-list",
            "chargeback/get-detail",
            "chargeback/pur-check",
            "chargeback/pur-charge-detail",
            "warehouse/get-balance-account-list",
            "warehouse/get-balance-account-info",
            "warehouse/change-balance-account",
            "supplier/get-supplier",
            "supplier/get-search-data",
            "supplier/get-suppliers-list",
            "supplier/get-supplier-add-data",
            "supplier/add",
            "supplier/get-supplier-info",
            "supplier/edit",
            "purchase/get-add-purchase-data",
            "purchase/get-purchase-goods-list",
            "purchase/add-purchase",
            "purchase/get-purchase-list",
            "purchase/get-purchase-list-search-data",
            "purchase/edit-purchase",
            "purchase/confirm-goods",
            "purchase/adjust-goods",
            "purchase/get-purchase-info",
            "purchase/close-purchase",
            "purchase/add-godown-entry",
            "warehouse/get-warehouse",
            "warehouse/get-goods",
            "warehouse/get-warehouse-in-search-data",
            "warehouse/get-warehouse-in-list",
            "warehouse/get-warehouse-in-info",
            "warehouse/confirm-warehouse-in",
            "warehouse/adjusting-inventory",
            "warehouse/get-warehouse-in-detail-list",
            "warehouse/get-warehouse-out-search-data",
            "warehouse/get-warehouse-out-list",
            "warehouse/get-warehouse-out-info",
            "warehouse/get-goods-inventory-num",
            "warehouse/confirm-warehouse-out",
            "warehouse/get-warehouse-goods",
            "warehouse/adjusting-inventory-out",
            "warehouse/get-warehouse-out-detail-list",
            "warehouse/get-get-goods-search-data",
            "warehouse/get-get-goods-list",
            "warehouse/get-add-goods-data",
            "warehouse/add-get-goods",
            "warehouse/get-get-goods-info",
            "warehouse/get-warehouse-in-infos",
            "warehouse/add-return-goods",
            "warehouse/get-return-goods-info",
            "warehouse/get-return-goods-list",
            "warehouse/get-return-goods-detail-list",
            "warehouse/get-all-warehouse",
            "warehouse/add-warehouse-check",
            "warehouse/get-warehouse-check-info",
            "warehouse/get-warehouse-check-list",
            "warehouse/add-warehouse-scrap",
            "warehouse/get-warehouse-scrap-info",
            "warehouse/get-warehouse-scrap-list",
            "warehouse/get-warehouse-scrap-detail-list",
            "warehouse/add-warehouse",
            "warehouse/edit-warehouse",
            "warehouse/get-warehouse-list",
            "warehouse/get-goods-warehouse-num",
            "warehouse/get-warehouse-info",
            "warehouse/get-warehouse-one-list",
            "warehouse/add-balance-account",
            "warehouse/get-search-balance-account-list",
            "warehouse/close-balance-account",
            "purchase/pur-order-list"

        ],
        "operation-manager" => [
            "order-list-page",
            "gym-list-page",
            "boss-index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "index/fee-statistic",
            "index/fee-chart",
            "address/search",
            "address/info",
        ],//运营经理
        "operation-assistant" => [
            "order-list-page",
            "address-list-page",
            "gym-list-page",
            "index-page",
            "gym/save",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "address/list",
            "address/info",
            "address/audit",
            "address/search"
        ],//运营助理
        "selection-manager" => [
            "address-list-page",
            "add-address-list-page",
            "gym-list-page",
            "selection-list-page",
            "address/list",
            "address/save",
            "address/info",
            "address/record-review",
            "address/audit",
            "address/record-contract",
            "staff/selection-list",
            "staff/add-staff",
            "staff/edit-staff",
            "staff/staff-info",
            "staff/add-department",
            "staff/edit-department",
            "staff/group-list",
            "staff/get-roles",
            "staff/staff-list",
            "staff/group-staff",
            "staff/department-info",
            "staff/city-list",
            "staff/list",
            "staff/department-city",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "address/get-range",
            "address/record-staff"
        ], //选址经理
        "selection-leader" => [
            "address-list-page",
            "add-address-list-page",
            "gym-list-page",
            "selection-list-page",
            "index-page",
            "address/list",
            "address/save",
            "address/info",
            "address/record-review",
            "address/record-contract",
            "staff/selection-list",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "address/get-range",
            "address/record-staff",
            "staff/list"
        ], // 选址组长
        "selection-specialist" => [
            "address-list-page",
            "add-address-list-page",
            "gym-list-page",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "address/list",
            "address/save",
            "address/info",
            "address/record-contract",
            "address/get-range",
        ], //选址专员
        "merchants-manager" => [
            "customer-list-page",
            "high-seas-list-page",
            "merchants-list-page",
            "address-list-page",
            "index-page",
            "customer/list",
            "customer/remark-list",
            "customer/attribute-labels",
            "customer/add-customer",
            "customer/get-info",
            "customer/staff-list",
            "customer/update-customer",
            "customer/appoint-group",
            "customer/docking-record",
            "customer/customer-liking",
            "customer/move-sea",
            "customer/invalid-customer",
            "consortium/assign-people",
            "gym/record",
            "address/list",
            "address/occupancy",
            "customer/add-remark",
            "staff/investment-manager",
            "staff/add-staff",
            "staff/edit-staff",
            "staff/staff-info",
            "staff/add-department",
            "staff/edit-department",
            "staff/group-list",
            "staff/get-roles",
            "staff/staff-list",
            "staff/group-staff",
            "staff/department-info",
            "staff/customer-follow",
            "staff/save-follow",
        ], //招商经理
        "merchants-leader" => [
            "address-list-page",
            "customer-list-page",
            "high-seas-list-page",
            "merchants-list-page",
            "index-page",
            "customer/list",
            "customer/remark-list",
            "customer/attribute-labels",
            "customer/add-customer",
            "customer/get-info",
            "customer/staff-list",
            "customer/update-customer",
            "customer/docking-record",
            "customer/customer-liking",
            "customer/move-sea",
            "customer/invalid-customer",
            "consortium/assign-people",
            "gym/record",
            "address/list",
            "staff/investment-manager",
            "customer/add-remark"
        ], //招商组长
        "merchants-specialist" => [
            "customer-list-page",
            "high-seas-list-page",
            "index-page",
            "address-list-page",
            "customer/list",
            "customer/remark-list",
            "customer/attribute-labels",
            "customer/get-info",
            "customer/staff-list",
            "customer/update-customer",
            "customer/docking-record",
            "customer/customer-liking",
            "customer/move-sea",
            "customer/invalid-customer",
            "consortium/assign-people",
            "gym/record",
            "address/list",
            "customer/add-remark",
            "gym/get-open-log"
        ], //招商专员
        "project-manager" => [
            "order-list-page",
            "gym-list-page",
            "customer-order-list-page",
            "customer-refund-list-page",
            "address-list-page",
            "index-page",
            "gym/close",
            "gym/search",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "address/list",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "order-entry/close",
            "order-entry/replenishment-save",
            "address/search",
            "address/info",
            "gym/get-simple-list",
        ], //项目经理
        "project-specialist" => [
            "order-list-page",
            "gym-list-page",
            "customer-order-list-page",
            "customer-refund-list-page",
            "index-page",
            "gym/close",
            "gym/search",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "order-entry/replenishment-save",
            "address/list",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "order-entry/close",
            "address/info",
            "address/search",
            "goods/goods-list",
            "order-entry/save",
            "warehouse/get-warehouse-out-goods",
            "order-entry/order-list",
            "order-entry/list-sub",
            "gym/get-simple-list",
            "order-entry/get-detail-list",
            "gym/get-node-info"
        ],//项目专员
        "flow-manager" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
        ], //流程经理
        "flow-specialist" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
        ], //流程专员
        "financial-manager" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "finance-order-list-page",
            "finance-refund-list-page",
            "onestopComparisonAccounting",
            "finance-check-list-page",
            "index-page",
            "customer-order/get-fin-list",
            "customer-order/order-detail",
            "finance/order-save",
            "chargeback/get-fin-list",
            "chargeback/fin-check",
            "warehouse/get-search-balance-account-list",
            "warehouse/get-balance-account-list",
            "warehouse/get-balance-account-info",
            "warehouse/change-balance-account",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
        ], //财务经理
        "financial-specialist" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "finance-order-list-page",
            "finance-refund-list-page",
            "finance-check-list-page",
            "onestopComparisonAccounting",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "customer-order/get-fin-list",
            "customer-order/order-detail",
            "finance/order-save",
            "chargeback/get-fin-list",
            "chargeback/fin-check",
            "warehouse/get-search-balance-account-list",
            "warehouse/get-balance-account-list",
            "warehouse/get-balance-account-info",
            "warehouse/change-balance-account",
        ], //财务专员
        "purchase-manager" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "goods-check-list-page",
            "supplier-refund-list-page",
            "purchase-order-list-page",
            "warehouse-in-list-page",
            "warehouse-in-detail-list-page",
            "warehouse-out-detail-list-page",
            "warehouse-out-list-page",
            "give-goods-list-page",
            "goods-list-page",
            "refund-goods-list-page",
            "refund-goods-detail-list-page",
            "pur-check-list-page",
            "cancel-list-page",
            "Warehouse-list-page",
            "order-purchase-check-list-page",
            "refund-purchase-check-list-page",
            "goods/goods-list",
            "goods/type",
            "goods/type-sub",
            "goods/by-type-supplier",
            "goods/save",
            "goods/goods-detail",
            "customer-order/get-list",
            "customer-order/pur-order-detail",
            "chargeback/get-pur-list",
            "chargeback/get-detail",
            "chargeback/pur-check",
            "chargeback/pur-charge-detail",
            "warehouse/get-balance-account-list",
            "warehouse/get-balance-account-info",
            "warehouse/change-balance-account",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "supplier/get-supplier",
            "supplier/get-search-data",
            "supplier/get-suppliers-list",
            "supplier/get-supplier-add-data",
            "supplier/add",
            "supplier/get-supplier-info",
            "supplier/edit",
            "purchase/get-add-purchase-data",
            "purchase/get-purchase-goods-list",
            "purchase/add-purchase",
            "purchase/get-purchase-list",
            "purchase/get-purchase-list-search-data",
            "purchase/edit-purchase",
            "purchase/confirm-goods",
            "purchase/adjust-goods",
            "purchase/get-purchase-info",
            "purchase/close-purchase",
            "purchase/add-godown-entry",
            "warehouse/get-warehouse",
            "warehouse/get-goods",
            "warehouse/get-warehouse-in-search-data",
            "warehouse/get-warehouse-in-list",
            "warehouse/get-warehouse-in-info",
            "warehouse/confirm-warehouse-in",
            "warehouse/adjusting-inventory",
            "warehouse/get-warehouse-in-detail-list",
            "warehouse/get-warehouse-out-search-data",
            "warehouse/get-warehouse-out-list",
            "warehouse/get-warehouse-out-info",
            "warehouse/get-goods-inventory-num",
            "warehouse/confirm-warehouse-out",
            "warehouse/get-warehouse-goods",
            "warehouse/adjusting-inventory-out",
            "warehouse/get-warehouse-out-detail-list",
            "warehouse/get-get-goods-search-data",
            "warehouse/get-get-goods-list",
            "warehouse/get-add-goods-data",
            "warehouse/add-get-goods",
            "warehouse/get-get-goods-info",
            "warehouse/get-warehouse-in-infos",
            "warehouse/add-return-goods",
            "warehouse/get-return-goods-info",
            "warehouse/get-return-goods-list",
            "warehouse/get-return-goods-detail-list",
            "warehouse/get-all-warehouse",
            "warehouse/add-warehouse-check",
            "warehouse/get-warehouse-check-info",
            "warehouse/get-warehouse-check-list",
            "warehouse/add-warehouse-scrap",
            "warehouse/get-warehouse-scrap-info",
            "warehouse/get-warehouse-scrap-list",
            "warehouse/get-warehouse-scrap-detail-list",
            "warehouse/add-warehouse",
            "warehouse/edit-warehouse",
            "warehouse/get-warehouse-list",
            "warehouse/get-goods-warehouse-num",
            "warehouse/get-warehouse-info",
            "warehouse/get-warehouse-one-list",
            "warehouse/add-balance-account",
            "warehouse/get-search-balance-account-list",
            "warehouse/close-balance-account",
            "purchase/pur-order-list"
        ], //采购经理
        "purchase-specialist" => [
            "order-list-page",
            "order-entry/search",
            "order-entry/index",
            "order-entry/info",
            "order-entry/get-order-goods",
            "gym-list-page",
            "goods-check-list-page",
            "supplier-refund-list-page",
            "purchase-order-list-page",
            "warehouse-in-list-page",
            "warehouse-in-detail-list-page",
            "warehouse-out-detail-list-page",
            "warehouse-out-list-page",
            "give-goods-list-page",
            "goods-list-page",
            "refund-goods-list-page",
            "refund-goods-detail-list-page",
            "pur-check-list-page",
            "cancel-list-page",
            "Warehouse-list-page",
            "order-purchase-check-list-page",
            "refund-purchase-check-list-page",
            "index-page",
            "gym/detail",
            "gym/get-graph",
            "gym/get-open-log",
            "gym/search",
            "goods/goods-list",
            "goods/type",
            "supplier/get-supplier",
            "goods/type-sub",
            "goods/by-type-supplier",
            "goods/save",
            "goods/goods-detail",
            "customer-order/get-list",
            "customer-order/pur-order-detail",
            "chargeback/get-pur-list",
            "chargeback/get-detail",
            "chargeback/pur-check",
            "chargeback/pur-charge-detail",
            "warehouse/get-warehouse",
            "supplier/get-search-data",
            "supplier/get-suppliers-list",
            "supplier/get-supplier-add-data",
            "supplier/add",
            "supplier/get-supplier-info",
            "supplier/edit",
            "purchase/get-add-purchase-data",
            "purchase/get-purchase-goods-list",
            "purchase/add-purchase",
            "purchase/get-purchase-list",
            "purchase/get-purchase-list-search-data",
            "purchase/edit-purchase",
            "purchase/confirm-goods",
            "purchase/adjust-goods",
            "purchase/get-purchase-info",
            "purchase/close-purchase",
            "purchase/add-godown-entry",
            "warehouse/get-goods",
            "warehouse/get-warehouse-in-search-data",
            "warehouse/get-warehouse-in-list",
            "warehouse/get-warehouse-in-info",
            "warehouse/confirm-warehouse-in",
            "warehouse/adjusting-inventory",
            "warehouse/get-warehouse-in-detail-list",
            "warehouse/get-warehouse-out-search-data",
            "warehouse/get-warehouse-out-list",
            "warehouse/get-warehouse-out-info",
            "warehouse/get-goods-inventory-num",
            "warehouse/confirm-warehouse-out",
            "warehouse/get-warehouse-goods",
            "warehouse/adjusting-inventory-out",
            "warehouse/get-warehouse-out-detail-list",
            "warehouse/get-get-goods-search-data",
            "warehouse/get-get-goods-list",
            "warehouse/get-add-goods-data",
            "warehouse/add-get-goods",
            "warehouse/get-get-goods-info",
            "warehouse/get-warehouse-in-infos",
            "warehouse/add-return-goods",
            "warehouse/get-return-goods-info",
            "warehouse/get-return-goods-list",
            "warehouse/get-return-goods-detail-list",
            "warehouse/get-all-warehouse",
            "warehouse/add-warehouse-check",
            "warehouse/get-warehouse-check-info",
            "warehouse/get-warehouse-check-list",
            "warehouse/add-warehouse-scrap",
            "warehouse/get-warehouse-scrap-info",
            "warehouse/get-warehouse-scrap-list",
            "warehouse/get-warehouse-scrap-detail-list",
            "warehouse/add-warehouse",
            "warehouse/edit-warehouse",
            "warehouse/get-warehouse-list",
            "warehouse/get-goods-warehouse-num",
            "warehouse/get-warehouse-info",
            "warehouse/get-warehouse-one-list",
            "warehouse/add-balance-account",
            "warehouse/get-balance-account-list",
            "warehouse/get-search-balance-account-list",
            "warehouse/get-balance-account-info",
            "warehouse/close-balance-account",
            "warehouse/change-balance-account",
            "warehouse/get-warehouse-out-goods",
            "purchase/pur-order-list"
        ], //采购专员
        "customer-manager" => [
            "customer-order-list-page",
            "customer-refund-list-page",
            "index-page",
            "customer-order/get-list",
            "customer-order/order-status",
            "gym/get-gym",
            "goods/goods-list",
            "customer-order/save-order",
            "customer-order/commit",
            "customer-order/order-detail",
            "customer-order/status",
            "customer-order/finish",
            "customer-order/all-finish",
            "chargeback/get-list",
            "chargeback/order-detail",
            "chargeback/add",
            "chargeback/commit",
            "chargeback/get-detail",
            "chargeback/pur-charge-detail",
            "chargeback/status",
            "chargeback/complete",
            "chargeback/update-refund",
            "customer-order/close",
            "chargeback/charge-status",
            "customer-order/get-remark",
            "chargeback/get-remark",
            "chargeback/update-status"

        ],//客服经理
        "customer-specialist" => [
            "customer-order-list-page",
            "customer-refund-list-page",
            "index-page",
            "customer-order/get-list",
            "customer-order/order-status",
            "gym/get-gym",
            "goods/goods-list",
            "customer-order/save-order",
            "customer-order/commit",
            "customer-order/order-detail",
            "customer-order/status",
            "customer-order/finish",
            "customer-order/all-finish",
            "chargeback/get-list",
            "chargeback/order-detail",
            "chargeback/add",
            "chargeback/commit",
            "chargeback/get-detail",
            "chargeback/pur-charge-detail",
            "chargeback/status",
            "chargeback/complete",
            "chargeback/update-refund",
            "customer-order/close",
            "chargeback/charge-status",
            "customer-order/get-remark",
            "chargeback/get-remark",
            "chargeback/update-status"
        ],//客服专员
    ];

    public $roles = [
        [
            "role_name" => "boss",
            "display_name" => "boss"
        ],
        [
            "role_name" => "operation-manager",
            "display_name" => "运营经理"
        ],
        [
            "role_name" => "operation-assistant",
            "display_name" => "运营助理"
        ],
        [
            "role_name" => "selection-manager",
            "display_name" => "选址经理",
            "pid" => "selection-manager"
        ],
        [
            "role_name" => "selection-leader",
            "display_name" => "选址组长",
            "pid" => "selection-manager",

        ],
        [
            "role_name" => "selection-specialist",
            "display_name" => "选址专员",
            "pid" => "selection-leader",

        ],
        [
            "role_name" => "merchants-manager",
            "display_name" => "招商经理",
            "pid" => "merchants-manager"
        ],
        [
            "role_name" => "merchants-leader",
            "display_name" => "招商组长",
            "pid" => "merchants-manager",

        ],
        [
            "role_name" => "merchants-specialist", //选址专员
            "display_name" => "招商专员",
            "pid" => "merchants-leader",

        ],
        [
            "role_name" => "project-manager",
            "display_name" => "项目经理",
        ],
        [
            "role_name" => "project-specialist",
            "display_name" => "项目专员",
            "pid" => "project-manager",

        ],
        [
            "role_name" => "flow-manager",
            "display_name" => "流程经理"
        ],
        [
            "role_name" => "flow-specialist",
            "display_name" => "流程专员",
            "pid" => "flow-manager",

        ],
        [
            "role_name" => "financial-manager",
            "display_name" => "财务经理"
        ],
        [
            "role_name" => "financial-specialist",
            "display_name" => "财务专员",
            "pid" => "financial-manager",

        ],
        [
            "role_name" => "purchase-manager",
            "display_name" => "采购经理"
        ],//采购经理
        [
            "role_name" => "purchase-specialist",
            "display_name" => "采购专员",
            "pid" => "purchase-manager",
        ],
        [
            "role_name" => "customer-manager",
            "display_name" => "客服经理",
        ],
        [
            "role_name" => "customer-specialist",
            "display_name" => "客服专员",
            "pid" => "customer-manager",
        ],
    ];

    /**
     * 生成角色权限
     *
     * @throws \Exception
     * @CreateTime 18/3/20 11:48:02
     * @Author: fangxing@likingfit.com
     */
    public function actionAuth()
    {
        $auth = \Yii::$app->getAuthManager();
        $auth->removeAll();
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            foreach ($this->roleMapRules as $role => $permissions) {
                $roleObj = new Role(["name" => $role]);
                $auth->add($roleObj);
                $this->stdout("Role: $role" . PHP_EOL, Console::FG_GREEN);
                foreach ($permissions as $permission) {
                    if (($permissionObj = $auth->getPermission($permission)) === null) {
                        $permissionObj = new Permission(["name" => $permission]);
                        $auth->add($permissionObj);
                        $this->stdout("Permission: $permission" . PHP_EOL, Console::FG_YELLOW);
                    }
                    $auth->addChild($roleObj, $permissionObj);
                    $this->stdout("Assign: $permission -----> $role" . PHP_EOL, Console::FG_BLUE);
                }
            }
            $transaction->commit();
        } catch (\Exception $exception) {
            $transaction->rollBack();
            $this->stderr($exception->getMessage(), Console::FG_RED);
        }
    }

    /**
     * 把角色分层次入库
     *
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 18/3/21 12:17:51
     * @Author: fangxing@likingfit.com
     */
    public function actionMakeRoles()
    {
        $db = \Yii::$app->db;
        $transaction = $db->beginTransaction();
        try {
            Roles::getDb()->createCommand("truncate " . Roles::tableName())->execute();
            array_walk($this->roles, function (&$role) {
                if (!isset($role["pid"]))
                    $role["pid"] = "";
            });
            $this->stdout((new Roles)->batchInsert($this->roles), Console::FG_GREEN);
            $transaction->commit();
        } catch (\Exception $e) {
            $transaction->rollBack();
            $this->stderr($e->getMessage(), Console::FG_RED);
        }
    }

    /**
     * @throws \Throwable
     * @CreateTime 18/4/28 21:48:40
     * @Author: fangxing@likingfit.com
     */
    public function actionMakeUser()
    {
        \Yii::$app->db->transaction(function ($db) {
            $users = [
                "Boss" => ["name" => "徐志岩", "email" => "xuzhiyan@likingfit.com", "phone" => "18521006738", "password" => "cs7014", "department_id" => 0],
                "operation-manager" => [
                    ["name" => "翟理", "email" => "zhaili@likingfit.com", "phone" => "18516106984", "password" => "cs7079", "department_id" => 2],
                    ["name" => "陆婉婉", "email" => "luwanwan@likingfit.com", "phone" => "15921927479", "password" => "cs8989", "department_id" => 2],
                ],
                "operation-assistant" => ["name" => "刘芳", "email" => "liufang@likingfit.com", "phone" => "15921927479", "password" => "cs5708", "department_id" => 2],
                "selection-manager" => ["name" => "刘长帅", "email" => "liuchangshuai@likingfit.com", "phone" => "15001367775", "password" => "cs3225", "department_id" => 9],
                "project-manager" => ["name" => "周蕾", "email" => "zhoulei@likingfit.com", "phone" => "18930596626", "password" => "cs7954", "department_id" => 10],
                "project-specialist" => [
                    ["name" => "刘永政", "email" => "liuyongzheng@likingfit.com", "phone" => "17621729508", "password" => "cs4484", "department_id" => 10],
                    ["name" => "吴洋", "email" => "wuyang@likingfit.com", "phone" => "18616960164", "password" => "cs5784", "department_id" => 10],
                    ["name" => "李冬", "email" => "lidong@likingfit.com", "phone" => "13052198776", "password" => "cs8545", "department_id" => 10]
                ],
                "flow-manager" => ["name" => "唐国强", "email" => "tangguoqiang@likingfit.com", "phone" => "13764962862", "password" => "cs6804", "department_id" => 8],
                "flow-specialist" => ["name" => "唐国强", "email" => "tangguoqiang@likingfit.com", "phone" => "13764962862", "password" => "cs6804", "department_id" => 8],
                "purchase-manager" => ["name" => "杨国伟", "email" => "yangguowei@likingfit.com", "phone" => "15201961326", "password" => "cs8416", "department_id" => 7],
                "purchase-specialist" => [
                    ["name" => "王荣花", "email" => "wangronghua@likingfit.com", "phone" => "15121114489", "password" => "cs7176", "department_id" => 7],
                    ["name" => "周荣", "email" => "zhourong@likingfit.com", "phone" => "18701857530", "password" => "cs5263", "department_id" => 7],
                ],
                "financial-manager" => ["name" => "张芳芳", "email" => "zhangfangfang@likingfit.com", "phone" => "18121231160", "password" => "cs2280", "department_id" => 6],
                "financial-specialist" => [
                    ["name" => "龚军霞", "email" => "gongjunxia@likingfit.com", "phone" => "17621980860", "password" => "cs4847", "department_id" => 6],
                    ["name" => "张园", "email" => "zhangyuan@likingfit.com", "phone" => "15201720557", "password" => "cs2859", "department_id" => 6],
                    ["name" => "侯沁", "email" => "houqin@likingfit.com", "phone" => "15618069755", "password" => "cs2917", "department_id" => 6]
                ],
                "customer-manager" => ["name" => "李晓晓", "email" => "lixiaoxiao@likingfit.com", "phone" => "18321619092", "password" => "cs5068", "department_id" => 11],
                "customer-specialist" => [
                    ["name" => "沈莺", "email" => "shenying@likingfit.com", "phone" => "13661709621", "password" => "cs6526", "department_id" => 11],
                    ["name" => "潘星婷", "email" => "panxingting@likingfit.com", "phone" => "18055071460", "password" => "cs4981", "department_id" => 11]
                ]
            ];
            $auth = \Yii::$app->getAuthManager();
            $time = time();
            foreach ($users as $role => $user) {
                $tmp = $user;
                if (ArrayHelper::isAssociative($user)) {
                    $tmp = [$tmp];
                }
                foreach ($tmp as $v) {
                    $staff = new Staff;
                    if (strpos($role, "manager") !== false) {
                        $v["is_leader"] = 1;
                    }
                    $v["create_time"] = $time;
                    $v["update_time"] = $time;
                    $v["show_index"] = "indexForSale";
                    $v["password"] = md5(md5($v["password"]) . $v["email"]);
                    $staff->setAttributes($v, false);
                    $staff->save();
                    $auth->assign($auth->createRole($role), $staff->id);
                    $this->stdout($v["name"] . "----->" . $role . PHP_EOL, Console::FG_GREEN);
                }
            }
        });
    }
}