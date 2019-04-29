<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/15 17:45:57
 */
namespace app\models;

use Yii;
use yii\helpers\VarDumper;

class CustomerStatistics extends Base
{
    const NEW_CUSTOMER = 1;

    const SUCCESS = 2;

    public static function tableName()
    {
        return 't_customer_statistics';
    }

    /**
     * 查找统计表信息
     * 陈旭旭
     */
    public static function getCustomerByType($type,$start,$end)
    {
        return self::find()
            ->select("num,time")
            ->where(['type' => $type])
            ->andWhere(['>=','time',$start])
            ->andWhere(['<=','time',$end])
            ->groupBy("time")
            ->asArray()
            ->all();
    }
}