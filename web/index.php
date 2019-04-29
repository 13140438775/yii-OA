<?php
defined('YII_DEBUG') or define('YII_DEBUG', true);

defined('YII_ENV') or define('YII_ENV', 'dev');

defined('AVAILABLE') or define('AVAILABLE', 1);

defined('UNAVAILABLE') or define('UNAVAILABLE', 0);

defined('IMAGE_DOMAIN') or define('IMAGE_DOMAIN', 'http://testimg.likingfit.com');

defined('PAGE') or define('PAGE', 1);

defined('PAGESIZE') or define('PAGESIZE', 15);

defined("SMS_URL") or define("SMS_URL", "http://127.0.0.1:4151/pub?topic=COMMA_SMS");

defined("SMS_CANCEL_GYM") or define("SMS_CANCEL_GYM", 112009);

defined("SMS_CANCEL_ORDER") or define("SMS_CANCEL_ORDER", 112010);

defined('LOCK_PATH') or define('LOCK_PATH', dirname(__FILE__).'/lock/');

defined("SMS_PASSWORD") or define("SMS_PASSWORD", 119823);

defined("SMS_VCODE") or define("SMS_VCODE", 108628);


require(__DIR__ . '/../vendor/autoload.php');
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
$config = require(__DIR__ . '/../config/web.php');
$app = new yii\web\Application($config);
$app->run();