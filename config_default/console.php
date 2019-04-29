<?php
$redis  = include_once('redis.php');
$db     = include_once('db.php');
$params = include_once(__DIR__ . '/../params/params.php');
$config = [
    'id'                  => 'Office Automation',
    'basePath'            => dirname(__DIR__),
    'bootstrap'           => [
        'log',
        'queue',
        'eventListener'
    ],
    'modules'    => [
        'gii' => yii\gii\Module::class
    ],
    'controllerNamespace' => 'app\commands',
    'params'              => $params,
    'aliases'             => [
        '@ext' => '@app/ext',
    ],
    'components'          => [
        'db'           => $db,
        'redis'        => $redis,
        'queue'        => [
            'class'   => \yii\queue\redis\Queue::class,
            'redis'   => 'redis',
            'channel' => 'queue',
        ],
        'log'          => [
            'flushInterval' => 1,
            'targets'       => [
                [
                    'exportInterval' => 1,
                    'class'          => yii\log\FileTarget::class,
                    'categories'     => [],
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
        'errorHandler' => [
            'class' => app\components\Error::class,
        ],
        'authManager' => [
            "class" => yii\rbac\DbManager::class,
            "itemTable" => "t_auth_item",
            "itemChildTable" => "t_auth_item_child",
            "assignmentTable" => "t_auth_assignment",
            "ruleTable" => "t_auth_rule"

        ],
        'eventListener' => \likingfit\Events\EventListener::class
    ],
];
return $config;