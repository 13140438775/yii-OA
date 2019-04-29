<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 18/2/28
 * Time: 下午5:42
 */
return [
    'login/login' => [
        'rules' => [
            [['email', 'password'], 'required'],
            ['email', 'email']
        ]
    ],
    'goods/add' => [
        'rules' => [
            [['goods_name', 'goods_type', 'type_id', 'type_name', 'model', 'weight', 'description', 'purchase_amount', 'price', 'min_sell', 'unit'],
                'required'
            ]
        ]
    ],
    'customer/remark-list' => [
        'rules' => [
            ['customer_id', 'required']
        ]
    ],
    'customer/city-list' => [
        'rules' => [
            ['province_id', 'required']
        ]
    ],
    'customer/district-list' => [
        'rules' => [
            ['city_id', 'required']
        ]
    ],
    'customer/add-customer' => [
        'rules' => [
            [['name', 'phone', 'source', 'province_id', 'city_id', 'district_id'], 'required'],
            // ['email', 'email']
        ]
    ],
    'customer/appoint-staff' => [
        'rules' => [
            [['customer_ids'], 'required']
        ]
    ],
    'customer/appoint-group' => [
        'rules' => [
            [['customer_ids', 'staff_id'], 'required']
        ]
    ],
    'customer/check-customer' => [
        'rules' => [['param'], 'required']
    ],
    'gym/save' => [
        'rules' => []
    ],
    /* ------------加盟签约--------------- */
    // 侧边栏-商务洽谈
    'flow/save/negotiate' => [
        'rules' => [
            [['status'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    // 侧边栏-记录面试结果
    'flow/save/interview' => [
        'rules' => [
            [['status'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    // 侧边栏-录入签约合同
    'flow/save/contract-record' => [
        'rules' => [
            [['franchisee_name', 'franchisee_phone', 'start_date', 'end_date', 'total_fee', 'gym_name'], 'required'],
            ['end_date', 'compare', 'compareAttribute' => 'start_date', 'operator' => '>='],
        ]
    ],
    'flow/save/contract-sure' => [
        'rules' => [
            [['status'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    'flow/save/contract-fee-sure' => [
        'rules' => [
            ['status', 'in', 'range' => [1, 2, 3]]
        ]
    ],
    /* ------------选址签约--------------- */
    // 侧边栏-记录租房费用
    'flow/save/rent-fee-record' => [
        'rules' => [
            ['pay_money', 'required'],
        ]
    ],
    // 侧边栏-确认租房合同
    'flow/save/rent-contract-sure' => [
        'rules' => [
            [['status'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    // 侧边栏-确认租房费用（直营）
    'flow/save/rent-fee-time' => [
        'rules' => [
            [['status'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    /* -------------设计出图-------------- */
    //确认平面图时间
    'flow/save/confirm-plan' => [
        'rules' => [
            ['confirm_floor_plan', 'required'],
            ['confirm_floor_plan', 'date', "format" => "yyyy-MM-dd"]
        ]
    ],
    //选择开店订单
    'flow/save/pick-order' => [
        'rules' => [
            [['order_type', "project_id"], 'required'],
            ['order_type', 'each', 'rule' => ['in', 'range' => [1, 2, 3, 4, 5, 6, 7, 9, 10]]],
            ['is_replenishment', 'default', 'value' => 0]
        ]
    ],
    //录入开店订单
    'flow/save/common-order' => [
        'rules' => [
            ['order_type', 'required'],
            ['order_type', 'in', 'range' => [1, 2, 3, 4, 5, 7, 9]],
            ['is_replenishment', 'default', 'value' => 0],
            ['stash', 'default', 'value' => 1],
            ['coupon_amount', 'default', 'value' => 1]
        ]
    ],
    //装修物料订单
    'flow/save/decoration-order' => [
        'rules' => [
            [['coupon_amount', 'decoration_amount'], 'required'],
            ['is_replenishment', 'default', 'value' => 0],
            ['stash', 'default', 'value' => 1]
        ]
    ],
    //拆除订单
    'flow/save/demolition-order' => [
        'rules' => [
            [['coupon_amount', 'total_amount', 'area'], 'required'],
            ['is_replenishment', 'default', 'value' => 0],
            ['stash', 'default', 'value' => 1]
        ]
    ],
    /*'order-entry/replenishment-save' => [
        'rules' => [
            [['order_type', 'project_id'], 'required'],
            ['order_type', 'each', 'rule' => ['in', 'range' => [1,2,3,4,6,8,9,10,11]]]
        ]
    ],*/
    //确认订单
    'flow/init/confirm-order' => [
        'rules' => [
            ['order_type', 'required'],
            ['order_type', 'in', 'range' => [1, 2, 3, 4, 5, 6, 7, 9]],
            ['page', app\validators\SubValidator::class,
                'rules' => [
                    ['page', 'default', 'value' => 1],
                    ['pageSize', 'default', 'value' => 10]
                ],
                'sub_attributes' => ['page', 'pageSize'],
                'skipOnEmpty' => false
            ]
        ]
    ],
    'flow/save/confirm-order' => [
        'rules' => [
            [['order_id', 'confirm_order'], 'required'],
            ['remark', 'required', 'when' => function ($model) {
                return $model->confirm_order == \app\models\OrderEntry::REJECT;
            }]
        ]
    ],
    //录入订单款项
    'flow/init/entry-pay' => [
        'rules' => [
            [['project_id', 'cost_type'], 'required'],
            ['cost_type', 'in', 'range' => [2, 3]]
        ]
    ],
    'flow/save/entry-pay' => [
        'rules' => [
            [['pay_list_id', 'payInfo', "project_id"], 'required'],
            ['payInfo', 'each', 'rule' => [
                \app\validators\SubValidator::class,
                'rules' => [
                    [['pay_person', 'pay_account', 'pay_amount', 'receive_account', 'pay_time', 'certificate'], 'required'],
                    ['certificate', 'each', 'rule' => ['required']]
                ],
                'sub_attributes' => ['pay_person', 'pay_account', 'pay_amount', 'receive_account', 'pay_time', 'certificate'],
                'skipOnEmpty' => false
            ]],
            ['stash', 'default', 'value' => 1]
        ]
    ],
    //确认订单款项
    'flow/save/confirm-pay' => [
        'rules' => [
            [['pay_list_id', 'pay_status'], 'required'],
            ['pay_status', 'in', 'range' => [1, 2, 3]]
        ]
    ],
    //录入订单商品（OA）
    'order-entry/save' => [
        'rules' => [
            [['project_id', 'order_type', 'goods', 'work_item_id'], 'required'],
            ['order_type', 'in', 'range' => [1, 2, 3, 4, 5, 6, 7, 9]],
            ['goods', 'each',
                'rule' => [
                    \app\validators\SubValidator::class,
                    'rules' => [
                        [['goods_id', 'good_num'], 'required']
                    ],
                    'sub_attributes' => ['goods_id', 'good_num'],
                    'skipOnEmpty' => false
                ]
            ],
            ['is_replenishment', 'default', 'value' => 0],
//            ['stash', 'default', 'value' => 1]
        ]
    ],
    //关闭订单
    'order-entry/close' => [
        'rules' => [
            ["order_id", 'required'],
        ]
    ],
    //补货
    'order-entry/replenishment-save' => [
        "rules" => [
            [['project_id', 'order_type'], 'required'],
            ['order_type', 'in', 'range' => [1, 2, 3, 4, 5, 6, 7]],
        ]
    ],
    /* 装修施工 && 营业准备 */
    // 侧边栏-确认施工队入场
    'flow/save/constration-team-enter' => [
        'rules' => [
            ['enter_time', 'required']
        ]
    ],
    // 侧边栏-确认骏工报告通过
    'flow/save/project-report-pass' => [
        'rules' => [
            ['enter_time', 'required']
        ]
    ],
    // 侧边栏-录入订单期望到货日期
    'flow/save/input-order-arrive' => [
        'rules' => [
            [['expect_time', 'order_type'], 'required']
        ]
    ],
    // 侧边栏-录入订单发货信息
    'flow/save/order-arrive-deliver' => [
        'rules' => [
            [['order_id', 'delivery_date', 'pre_arrive_date'], 'required']
        ]
    ],
    // 侧边栏-确认订单到货
    'flow/save/order-arrive-sure' => [
        'rules' => [
            [['order_id', 'certificate', 'arrive_date'], 'required'],
            ['status', 'in', 'range' => [0, 1]]
        ]
    ],
    // 侧边栏-确认智能设备调试完成
    'flow/save/brow-device-finish' => [
        'rules' => [
            ['device_debug_time', 'required']
        ]
    ],
    // 侧边栏-提交预售时间及成本
    'flow/save/presell-time-sure' => [
        'rules' => [
            [['date_time', 'presale_cost'], 'required']
        ]
    ],
    // 侧边栏-录入开业批准日期
    'flow/save/entry-opening-approval' => [
        'rules' => [
            ['expect_open_time', 'required']
        ]
    ],
    // 侧边栏-录入开店成本
    'flow/save/entry-shop-cos' => [
        'rules' => [
            [['total_amount', 'amount_type', 'is_use'], 'required']
        ]
    ],
    // 侧边栏-录入正式营业日期
    'flow/save/entry-business-date' => [
        'rules' => [
            ['open_time', 'required']
        ]
    ],
    //-----------------地址池----------------------
    // 侧边栏地址搜索
    'address/search' => [
        'rules' => [
        ]
    ],
    // 地址详情
    'address/info' => [
        'rules' => [
            [['address_id'], 'required']
        ]
    ],
    // 地址新增&保存
    'address/save' => [
        'rules' => [
            [['is_presale'], 'required'],
            ['is_presale', 'in', 'range' => [0, 1]]
        ]
    ],
    // 地址列表
    'address/list' => [
        'rules' => [
            ['is_replenishment', 'default', 'value' => 0]
        ],
        // 角色名称
        'jue' => [
            //'select' => ['id', 'address_type'],
        ]
    ],
    // 地址评审
    'address/audit' => [
        'rules' => [
            [['address_id', 'audit_status'], 'required'],
            ['audit_status', 'in', 'range' => [0, 1]]
        ]
    ],
    // 地址指定专员
    'address/record-staff' => [
        'rules' => [
            [['address_ids', 'staff_id'], 'required']
        ]
    ],
    // 地址记录合同
    'address/record-contract' => [
        'rules' => [
            [['address_id', 'contract_sn', 'receive_start_time', 'receive_end_time'], 'required']
        ]
    ],
    // 地址预留
    'address/occupancy' => [
        'rules' => [
            [['address_id'], 'required']
        ],
    ],
    /*我的团队*/
    // 新增员工
    'staff/add-staff' => [
        'rules' => [
            [['name', 'phone', 'role'], 'required'],
            ['email', 'email']
        ]
    ],
    // 编辑员工
    'staff/edit-staff' => [
        'rules' => [
            [['staff_id', 'staff_name', 'phone', 'role', 'staff_status'], 'required'],
        ]
    ],
    // 新增组
    'staff/add-department' => [
        'rules' => [
            ['name', 'required']
        ]
    ],
    // 编辑组
    'staff/edit-department' => [
        'rules' => [
            [['name', 'department_id'], 'required']
        ]
    ],
    // 编辑组
    'staff/staff-list' => [
        'rules' => [
            [['type'], 'required'],
            ['type', 'in', 'range' => [1, 2]]
        ]
    ],
    //健身房
    "gym/search" => [
        'rules' => [
            ['page', 'default', "value" => 1],
            ['pageNum', 'default', "value" => 10]
        ]
    ],
    // 城市列表
    "staff/city-list" => [
        'rules' => [
            [['city_name'], 'required']
        ]
    ],
    // 部门城市
    "staff/department-city" => [
        'rules' => [
            [['department_id'], 'required']
        ]
    ],
    // 部门城市
    "staff/save-follow" => [
        'rules' => [
            [['customer_follow'], 'required']
        ]
    ],
    // 修改密码
    "login/save-password" => [
        'rules' => [
            [['phone', 'password'], 'required']
        ]
    ],
    // 发送验证码
    "login/send-code" => [
        'rules' => [
            [['phone'], 'required']
        ]
    ],
    //一站式采购
    //供应商新增
    'supplier/add' => [
        'rules' => [
            ['suppliers', app\validators\SubValidator::class,
                'rules' => [
                    [
                        [
                            'supplier_name', 'province_id','city_id',
                            'district_id','address','contact_name',
                            'phone','email'
                        ], 'required'
                    ]
                ],
            ],
            [['goods_type','suppliers_area'], 'required'],
            ['suppliers_account', 'each',
                'rule' => [
                    app\validators\SubValidator::class,
                    'rules' => [
                        [['name', 'bankName','bankAccount'], 'required']
                    ]
                ]
            ],
        ]
    ],
    //供应商编辑
    'supplier/edit' => [
        'rules' => [
            ['suppliers', app\validators\SubValidator::class,
                'rules' => [
                    [
                        [
                            'supplier_name', 'province_id','city_id',
                            'district_id','address','contact_name',
                            'phone','email'
                        ], 'required'
                    ]
                ],
            ],
            [['supplier_id','goods_type','suppliers_area'], 'required'],
            ['suppliers_account', 'each',
                'rule' => [
                    app\validators\SubValidator::class,
                    'rules' => [
                        [['name', 'bankName','bankAccount'], 'required']
                    ]
                ]
            ],
        ]
    ],
    //供应商详情
    "supplier/get-supplier-info" => [
        'rules' => [
            [['supplier_id'], 'required']
        ]
    ],
    //采购单
    //新增采购单
    'purchase/add' => [
        'rules' => [
            ['purchase', app\validators\SubValidator::class,
                'rules' => [
                    [
                        [
                            'order_id', 'actual_amount','warehouse_id',
                            'purchase_type','supplier_id'
                        ], 'required'
                    ]
                ],
            ],
            ['purchase_goods', 'each',
                'rule' => [
                    app\validators\SubValidator::class,
                    'rules' => [
                        [['purchase_num', 'goods_id','actual_amount','warehouse_id'], 'required']
                    ]
                ]
            ],
            ['purchase_supplier', app\validators\SubValidator::class,
                'rules' => [
                    [
                        [
                            'supplier_id'
                        ], 'required'
                    ]
                ],
            ],
            ['purchase_warehouse', app\validators\SubValidator::class,
                'rules' => [
                    [
                        [
                            'warehouse_id'
                        ], 'required'
                    ]
                ],
            ]
        ]
    ],
    //客服退单
    'chargeback/add' => [
        'rules' => [['order_id','required']]
    ],
    'chargeback/commit' => [
        'rules' => [['order_id','required']]
        ],
    // 指定专员
    'consortium/assign-people' => [
        'rules' => [
            [['customer_ids', 'staff_id'], 'required']
        ]
    ]
];