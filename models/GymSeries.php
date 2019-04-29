<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_gym_series".
 *
 * @property int $series_id
 * @property int $project_id
 * @property int $customer_id
 * @property int $series_type
 * @property int $series_status
 * @property string $point
 */
class GymSeries extends \yii\db\ActiveRecord
{
    const MAIN = 1;
    const APPEND = 2;
    const OPEN_DIRECT = "OpenDirect.Activity29";
    const OPEN_MAIN = "Main.Activity17";
    const CLOSED = 3;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_gym_series';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['series_id'], 'required'],
            [['series_id', 'project_id', 'customer_id'], 'integer'],
            [['series_id'], 'unique'],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'series_id' => 'Series ID',
            'project_id' => 'Project ID',
            'customer_id' => 'Customer ID',
        ];
    }

    public function getOpenProject()
    {
        return $this->hasOne(OpenProject::class, ["id" => "project_id"]);
    }

    public function getCustomer()
    {
        return $this->hasOne(Customer::class, ["id" => "customer_id"]);
    }

    /**
     * @param $series_id
     * @return array|null|GymSeries
     * @CreateTime 18/4/9 23:28:49
     * @Author: fangxing@likingfit.com
     */
    public static function findBySeriesId($series_id)
    {
        return static::find()
            ->with("openProject")
            ->where(["series_id" => $series_id])
            ->one();
    }
}
