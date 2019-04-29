<?php
/**
 * Created by PhpStorm.
 *
 * @Author     : screamwolf@likingfit.com
 * @CreateTime 2018/3/6 15:06:03
 */
return [
    'open_type' => [
        1 => [
            'name' => '合营',
            'val'  => 1,
            'purchase' => [
                1 => [
                    'label' => "申请加盟",
                    'val' => 20
                ],
                2 => [
                    'label' => "选址签约",
                    'val' => 20
                ],
                3 => [
                    'label' => "装修准备",
                    'val' => 60
                ],
                4 => [
                    'label' => "门店竣工",
                    'val' => 80
                ],
                5 => [
                    'label' => "营业准备",
                    'val' => 95
                ],
                6 => [
                    'label' => "完成开店",
                    'val' => 100
                ]
            ]
        ],
        2 => [
            'name' => '直营',
            'val'  => 2,
            'purchase' => [
                2 => [
                    'label' => "选址签约",
                    'val' => 20
                ],
                3 => [
                    'label' => "装修准备",
                    'val' => 60
                ],
                4 => [
                    'label' => "门店竣工",
                    'val' => 80
                ],
                5 => [
                    'label' => "营业准备",
                    'val' => 95
                ],
                6 => [
                    'label' => "完成开店",
                    'val' => 100
                ]
            ]
        ],
        3 => [
            'name' => '加盟',
            'val'  => 3,
        ]
    ],
    'gym_status' => [
        1 => "开店中",
        2 => "已开店",
        3 => "已关店"
    ]
];