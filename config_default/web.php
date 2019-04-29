<?php
$redis  = include_once('redis.php');
$db     = include_once('db.php');
$params = include_once(__DIR__ . '/../params/params.php');
$config = [
    'id'         => 'Office Automation',
    'basePath'   => dirname(__DIR__),
    'bootstrap'  => [
        'log',
        'queue',
        'eventListener'
    ],
    'timezone'   => 'PRC',
    'charset'    => 'UTF-8',
    'params'     => $params,
    'language'   => 'zh-CN',
    'aliases'    => [
        '@test' => '@vendor/test',
    ],
    'components' => [
        'log'          => [
            'targets'       => [
                [
                    'class'          => 'yii\log\FileTarget',
                    'logVars'        => [
                        '_GET',
                        '_POST',
                    ],
                    'levels'         => [
                        'error',
                    ],
                ],
            ],
        ],
        'queue'        => [
            'class'   => \yii\queue\redis\Queue::class,
            'redis'   => 'redis',
            'channel' => 'queue',
        ],
        'errorHandler' => [
            'class' => app\components\Error::class,
            'debug' => YII_DEBUG,
        ],
        'request'      => [
            'cookieValidationKey'  => 'pb-cookie-key',
            'enableCsrfValidation' => false,
            'parsers' => [
                'application/json' => yii\web\JsonParser::class
            ]
        ],
        'response'     => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'as ResponseBehavior' => [
                'class' => app\behaviors\ResponseFilter::class,
            ],
        ],
        'urlManager'   => [
            'enablePrettyUrl' => true,
            'showScriptName'  => false,
        ],
        /*'cache'        => [
            'class' => \yii\caching\FileCache::class,
        ],*/
        'redis'        => $redis,
        'db'           => $db,
        'user' => [
            'identityClass' => app\models\Staff::class,
            'enableSession' => false
        ],
        'authManager' => [
            "class" => yii\rbac\DbManager::class,
            "itemTable" => "t_auth_item",
            "itemChildTable" => "t_auth_item_child",
            "assignmentTable" => "t_auth_assignment",
            "ruleTable" => "t_auth_rule"

        ],
        'eventListener' => \likingfit\Events\EventListener::class,
        'sms' => app\components\Sms\Sms::class
    ],
    'as cors' => [
        //CORS跨域
        'class' => \yii\filters\Cors::class,
        'cors' => [
            'Origin' => ['http://localhost:8090'],
            'Access-Control-Request-Method' => ['POST', 'GET'],
            'Access-Control-Request-Headers' => ['Content-Type', 'Authorization'],
            'Access-Control-Allow-Credentials' => true,
            'Access-Control-Max-Age' => 1728000
        ]
    ],
];
return $config;
