<?php
$rules = include_once "rules.php";
$gym = include_once "gym.php";
$commands = include_once "commands.php";
return [
    'form' => [
        'TOOL' => 1,
        'SUBFLOW' => 2,
        'FORM' => 3,
    ],
    'rules' => $rules,
    'order_entry' => [
        'order_type' => [
            1 => [
                "name" => "大器械",
                "value" => 1,
                'default' => 1,
                'var' => 'largeEquipment',
                'confirm-var' => "LargeEquipmentConfirm",
                'cg' => 109
            ],
            2 => [
                "name" => "小器械",
                "value" => 2,
                'default' => 1,
                'var' => 'smallEquipment',
                'confirm-var' => "SmallEquipmentConfirm",
                'cg' => 109
            ],
            3 => [
                "name" => "智能硬件",
                "value" => 3,
                'default' => 1,
                'var' => 'smartDevice',
                'confirm-var' => "SmartDeviceConfirm",
                'cg' => 109
            ],
            4 => [
                "name" => "监控",
                "value" => 4,
                'default' => 1,
                'var' => 'monitor',
                'confirm-var' => "MonitorConfirm",
                'cg' => 109
            ],
            5 => [
                "name" => "定制物料",
                "value" => 5,
                'default' => 1,
                'var' => 'materials',
                'confirm-var' => "MaterialsConfirm",
                'cg' => 110
            ],
            6 => [
                "name" => "装修施工物料",
                "value" => 6,
                'is_required' => 1,
                'default' => 1,
                'var' => 'constructMaterials',
                'confirm-var' => "ConstructMaterialsConfirm",
                'cg' => 109
            ],
            7 => [
                "name" => "门头",
                "value" => 7,
                'default' => 1,
                'var' => 'firstDoor',
                'confirm-var' => "FirstDoorConfirm",
                'cg' => 110
            ],
            9 => [
                "name" => "预售处",
                "value" => 9,
                'default' => 1,
                'var' => 'presaleEquipment',
                'confirm-var' => "PresaleEquipmentConfirm",
                'cg' => 110
            ],
            10 => [
                "name" => "拆除费用",
                "value" => 10,
                'default' => 0,
                'var' => 'dismantle',
                'cg' => 109
            ]
        ],
        "order_status" => [
            1 => "提交订单", //提交订单
            2 => "确认订单", //确认订单
            3 => "提交款项", //录入款项
            4 => "确认款项", //确认款项
            5 => "提交期望到货时间",
            6 => "已发货", //确认发货时间
            7 => "确认到货", //确认发货
        ],
    ],
    'commands' => $commands,
    'gym' => $gym,
    'area_list' => [
        [
            'id' => 1,
            'area_name' => '华东'
        ],
        [
            'id' => 2,
            'area_name' => '华南'
        ],
        [
            'id' => 3,
            'area_name' => '华中'
        ],
        [
            'id' => 4,
            'area_name' => '华北'
        ],
        [
            'id' => 5,
            'area_name' => '西北'
        ],
        [
            'id' => 6,
            'area_name' => '西南'
        ],
        [
            'id' => 7,
            'area_name' => '东北'
        ],
        [
            'id' => 8,
            'area_name' => '台港澳'
        ]
    ],
    'labels' => [
        'source_arr' => [
            ['key' => 1, 'val' => '官网'],
            ['key' => 2, 'val' => 'app'],
            ['key' => 3, 'val' => '微信'],
            ['key' => 4, 'val' => '电话'],
            ['key' => 5, 'val' => '其他']
        ],
        'label_arr' => [
            ['key' => 0, 'intention' => '无'],
            ['key' => 1, 'intention' => '高意向'],
            ['key' => 2, 'intention' => '看店有资金'],
            ['key' => 3, 'intention' => '有资金'],
            ['key' => 4, 'intention' => '待定'],
            ['key' => 5, 'intention' => '无资金']
        ],
        'docking_status' => [
            ['key' => 1, 'intention' => '新客户'],
            ['key' => 2, 'intention' => '沟通中'],
            ['key' => 3, 'intention' => '签约成功'],
            ['key' => 4, 'intention' => '无效客户']
        ],
        'deal_time' => [
            ['key' => '0', 'deal_time' => '全部'],
            ['key' => '1', 'deal_time' => '0天～5天'],
            ['key' => '2', 'deal_time' => '5天～10天'],
            ['key' => '3', 'deal_time' => '10天～20天'],
            ['key' => '4', 'deal_time' => '20天～30天'],
            ['key' => '5', 'deal_time' => '30天以上']
        ]
    ],
    'warehouse_in_status_arr' => [
        [
            'id' => 1,
            'warehouse_status' => '初始状态'
        ],
        [
            'id' => 2,
            'warehouse_status' => '已入库'
        ]
    ],
    'warehouse_out_status_arr' => [
        [
            'id' => 1,
            'warehouse_status' => '初始状态'
        ],
        [
            'id' => 2,
            'warehouse_status' => '已出库'
        ]
    ],
    'balance_status_list' => [
        [
            'id' => 1,
            'balance_status' => '初始状态'
        ],
        [
            'id' => 2,
            'balance_status' => '已提交'
        ],
        [
            'id' => 3,
            'balance_status' => '财务驳回'
        ],
        [
            'id' => 4,
            'balance_status' => '已关账'
        ],
        [
            'id' => 5,
            'balance_status' => '已关闭'
        ]
    ],
    'balance_finance_status_list' => [
        [
            'id' => 2,
            'balance_status' => '待审核'
        ],
        [
            'id' => 3,
            'balance_status' => '已驳回'
        ],
        [
            'id' => 4,
            'balance_status' => '已关账'
        ]
    ],
    'purchase_status_arr' => [
        [
            'id' => 1,
            'purchase_status' => '初始状态'
        ],
        [
            'id' => 2,
            'purchase_status' => '确认到货'
        ],
        [
            'id' => 3,
            'purchase_status' => '入库中'
        ],
        [
            'id' => 4,
            'purchase_status' => '已入库'
        ],
        [
            'id' => 5,
            'purchase_status' => '已关闭'
        ]
    ],
    "nearlyActivity" => [
        //直营
        "OpenDirect.Activity1" => [
            "next" => "确认租房合同&费用"
        ],
        "OpenDirect.Activity3" => [
            "next" => "指定项目专员"
        ],
        "OpenDirect.Activity5" => [
            "next" => "指定项目专员"
        ],
        "OpenDirect.Activity11" => [
            "prev" => "确认租房合同&费用"
        ],
        "Order.Activity13" => [
            "next" => "录入开店订单"
        ],
        "OpenDirect.Activity15" => [
            "prev" => "开店准备完成"
        ],
        "Order.Activity11" => [
            "next" => "确认施工队已进场"
        ],
        "Order.Activity8" => [
            "prev" => "装修竣工且智能设备调试完成"
        ],

        //合营
        "Main.Activity18" => [
            "prev" => "申请面试",
            "next" => "录入客户签约合同"
        ],
        "Main.Activity6" => [
            "prev" => "确认签约合同及费用",
            "next" => "选择开店租房地址"
        ],
        "Main.Activity8" => [
            "prev" => "确认租房合同&费用"
        ],
        "Main.Activity20" => [
            "next" => "录入开店订单"
        ],
        "Main.Activity14" => [
            "next" => "录入订单期望到货时间"
        ],
        "Main.Activity23" => [
            "next" => "确认施工队已进场"
        ],
        "Main.Activity28" => [
            "prev" => "装修竣工&智能设备调试完成"
        ],
        "Main.Activity15" => [
            "prev" => "开店准备完成"
        ],
        "ContractTask.Activity1" => [
            "prev" => "面试已通过"
        ],
        "ContractTask.Activity20" => [
            "next" => "确认签约合同及费用"
        ],
        "ContractTask.Activity4" => [
            "next" => "指定项目专员"
        ],
        "ContractTask.Activity5" => [
            "next" => "指定项目专员"
        ],
        "HouseTask.Activity15" => [
            "next" => "确认租房合同&费用"
        ],
        "HouseTask.Activity2" => [
            "next" => "确认平面图完成"
        ],
        "HouseTask.Activity3" => [
            "next" => "确认平面图完成"
        ],
        //补单
        //"Relenishment.Activity9" => ""
    ],
    "menu_check_off" => false,
    "menus" => [
        [
            'name' => '首页',
            'icon' => 'fa-home',
            'href' => 'index',
            'auth' => 'boss-index-page'
        ],
        [
            'name' => '首页',
            'icon' => 'fa-home',
            'href' => 'index',
            'auth' => 'index-page'
        ],
        [
            'name' => '客户',
            'icon' => 'fa-user',
            'submenus' => [
                [
                    'name' => '客户列表',
                    'icon' => '',
                    'href' => 'customerList',
                    'auth' => 'customer-list-page'
                ],
                [
                    'name' => '客户公海',
                    'icon' => '',
                    'href' => 'customer-sea',
                    'auth' => 'high-seas-list-page'
                ],
            ],
            'auth' => ''
        ],
        [
            'name' => '开店订单',
            'icon' => 'fa-reorder',
            'submenus' => [
                [
                    'name' => '订单列表',
                    'icon' => '',
                    'href' => 'order-list',
                    'auth' => 'order-list-page'
                ]
            ],
            'auth' => ''
        ],
        [
            'name' => '健身房',
            'icon' => 'fa-bolt',
            'submenus' => [
                [
                    'name' => '健身房列表',
                    'icon' => '',
                    'href' => 'gym-list',
                    'auth' => 'gym-list-page'
                ]
            ],
            'auth' => ''
        ],
        [
            'name' => '我的团队',
            'icon' => 'fa-group',
            'submenus' => [
                [
                    'name' => '招商团队',
                    'icon' => '',
                    'href' => 'basic-setting',
                    'auth' => 'merchants-list-page'
                ],
                [
                    'name' => '选址团队',
                    'icon' => '',
                    'href' => 'myTeam',
                    'auth' => 'selection-list-page'
                ]
            ],
            'auth' => ''
        ],
        [
            'name' => '选址池',
            'icon' => 'fa-map-marker',
            'submenus' => [
                [
                    'name' => '选址列表',
                    'icon' => '',
                    'href' => 'locationPool',
                    'auth' => 'address-list-page'
                ]
            ],
            'auth' => ''
        ],
        [
        'name' => '客服订单',
        'icon' => 'fa-file-text',
        'submenus' => [
            [
                'name' => '订单列表',
                'icon' => '',
                'href' => 'oneStopOrder',
                'auth' => 'customer-order-list-page'

            ],
            [
                'name' => '退单列表',
                'icon' => '',
                'href' => 'orderSendBack',
                'auth' => 'customer-refund-list-page'
            ]
        ],
        'auth' => ''
    ],
        [
            'name' => '财务管理',
            'icon' => 'fa-yen',
            'submenus' => [
                [
                    'name' => '订单列表',
                    'icon' => '',
                    'href' => 'onestopFinanceOrderList',
                    'auth' => 'finance-order-list-page'

                ],
                [
                    'name' => '退单列表',
                    'icon' => '',
                    'href' => 'onestopFinanceRefundList',
                    'auth' => 'finance-refund-list-page'
                ],
                [
                    'name' => '采购对账单',
                    'icon' => '',
                    'href' => 'onestopComparisonAccounting',
                    'auth' => 'finance-check-list-page'
                ],
            ],
            'auth' => ''
        ],
        [
            'name' => '采购管理',
            'icon' => 'fa-cart-plus',
            'submenus' => [
                [
                    'name' => '采购单',
                    'icon' => '',
                    'href' => 'oneStopPurchase',
                    'auth' => 'purchase-order-list-page'

                ],
                [
                    'name' => '供应商管理',
                    'icon' => '',
                    'href' => 'onestopSupplier',
                    'auth' => 'supplier-refund-list-page'
                ],
                [
                    'name' => '商品管理',
                    'icon' => '',
                    'href' => 'onestopCommodity',
                    'auth' => 'goods-list-page'
                ],
            ],
            'auth' => ''
        ],
        [
            'name' => '仓库管理',
            'icon' => 'fa-university',
            'submenus' => [
                [
                    'name' => '入库单',
                    'icon' => '',
                    'href' => 'onestopInstock',
                    'auth' => 'warehouse-in-list-page'

                ],
                [
                    'name' => '入库单明细',
                    'icon' => '',
                    'href' => 'onestopInstockDetail',
                    'auth' => 'warehouse-in-detail-list-page'

                ],
                [
                    'name' => '出库单',
                    'icon' => '',
                    'href' => 'onestopOutstock',
                    'auth' => 'warehouse-out-list-page'
                ],
                [
                    'name' => '出库单明细',
                    'icon' => '',
                    'href' => 'onestopOutstockDetail',
                    'auth' => 'warehouse-out-detail-list-page'
                ],
                [
                    'name' => '领货单',
                    'icon' => '',
                    'href' => 'onestopGetstock',
                    'auth' => 'give-goods-list-page'
                ],
                [
                    'name' => '退货单',
                    'icon' => '',
                    'href' => 'onestopReturns',
                    'auth' => 'refund-goods-list-page'

                ],
                [
                    'name' => '退货单明细',
                    'icon' => '',
                    'href' => 'onestopReturnsAllDetail',
                    'auth' => 'refund-goods-detail-list-page'

                ],
                [
                    'name' => '盘点单',
                    'icon' => '',
                    'href' => 'onestopChecks',
                    'auth' => 'goods-check-list-page'
                ],
                [
                    'name' => '对账单',
                    'icon' => '',
                    'href' => 'onestopComparisonList',
                    'auth' => 'pur-check-list-page'
                ],
                [
                    'name' => '报废单',
                    'icon' => '',
                    'href' => 'onestopScrap',
                    'auth' => 'cancel-list-page'
                ],
                [
                    'name' => '仓库',
                    'icon' => '',
                    'href' => 'onestopWarehouse',
                    'auth' => 'Warehouse-list-page'
                ],
            ],
            'auth' => ''
        ],
        [
            'name' => '订单管理',
            'icon' => 'fa-file-text',
            'submenus' => [
                [
                    'name' => '订单列表',
                    'icon' => '',
                    'href' => 'oneStopPurchaseOrderList',
                    'auth' => 'order-purchase-check-list-page'

                ],
                [
                    'name' => '退单列表',
                    'icon' => '',
                    'href' => 'oneStopPurchaseReturnList',
                    'auth' => 'refund-purchase-check-list-page'
                ],
            ],
            'auth' => ''
        ],
    ],
    'purchase' => [
        'purchase_status' => [
            1 => '初始状态',
            2 => '确认到货',
            3 => '入库中',
            4 => '已入库',
            5 => '已关闭'
        ],
        'out_status' => [
            1 => '初始状态',
            2 => '已出库'
        ],
        'order_status' => [
            1 => '未提交',
            2 => '已完成',
            3 => '财务审核',
            4 => '审核驳回',
            5 => '采购中',
            6 => '发货中',
            7 => '已关闭',
        ],
        'detail_status' => [
            1 => '未提交',
            2 => '已完成',
            3 => '财务审核',
            4 => '审核驳回',
            5 => '采购中',
            6 => '发货中',
            7 => '已关闭',
        ],

        'chargeback_status' =>[
            1 => '未提交',
            2 => '退货中',
            3 => '退货驳回',
            4 => '财务打款',
            5 => '退款驳回',
            6 => '退款中',
            7 => '已完成',
            8 => '已关闭',
            9 => '已取消',
        ],
        'refund_pur_status' =>[
            1 => '初始状态',
            2 => '已驳回',
            3 => '已入库'
        ],
        'refund_finance_status' => [
            1 => '待退款',
            2 => '退款驳回',
            3 => '已退款'
        ],
        'finance_check_status' => [
            1 => '待审核',
            2 => '审核驳回',
            3 => '审核通过'
        ]
    ],
    "pay_status" => [
        1 => "未到账",
        2 => "部分到账",
        3 => "全部到账"
    ],
    "cost_type" => [
        1 => "加盟",
        2 => "器械",
        3 => "装修"
    ],
    "amount_type" => [
        1 => "补款",
        2 => "退款"
    ],
    "flowDepartment" => [
        [
            "id" => 2,
            "key" => "yy",
            "default" => ""
        ],
        [
            "id" => 4,
            "key" => "zs",
            "default" => ""
        ],
        [
            "id" => 6,
            "key" => "cw",
            "default" => ""
        ],
        [
            "id" => 7,
            "key" => "cg",
            "default" => ""
        ],
        [
            "id" => 8,
            "key" => "lc",
            "default" => ""
        ],
        [
            "id" => 10,
            "key" => "xm",
            "default" => ""
        ],
    ],
    "orderFlowDepartment" => [
        [
            "id" => 6,
            "key" => "cw",
            "default" => "暂无"
        ],
        [
            "id" => 10,
            "key" => "xm",
            "default" => "暂无"
        ],
    ]
];