<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/6 15:21:16
 */

namespace app\models;

use Yii;

class OpenFlow extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_open_flow';
    }

    public static function getOpenFlowInfoBySeriesId($seriesId)
    {
        return self::find()
            ->select("opening_time,expect_open_time,create_time,open_time,purchase")
            ->where(['series_id' => $seriesId])
            ->one();
    }

    public function beforeSave($insert)
    {
        $time = time();
        if ($insert) {
            $this->create_time = $time;
        }
        return parent::beforeSave($insert);
    }

    public function getGym()
    {
        return $this->hasOne(OpenProject::class, ["series_id" => "series_id"]);
    }
}
