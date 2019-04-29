<?php

namespace app\models;

use app\exceptions\Gym;
use Yii;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "t_open_project".
 *
 * @property int $id
 * @property int $flow_id 流程ID
 * @property int $series_id 流程组标识,顶级流程id
 * @property string $gym_name 健身房名字
 * @property int $province_id 健身房省份ID
 * @property string $province_name 健身房省份名字
 * @property int $city_id 健身房城市ID
 * @property string $city_name 健身房城市名字
 * @property int $district_id 健身房地区ID
 * @property string $district_name 健身房地区名称
 * @property string $address 具体地址
 * @property int $gym_area 健身房面积 平方米
 * @property string $tel 门店座机 带区号
 * @property int $change_stair 是否变动楼梯
 * @property string $advertisement_discount 赠送广告额度
 * @property string $material_discount 赠送物料额度
 * @property string $longitude 经度
 * @property int gym_status
 * @property int $can_replenishment 能否补单
 * @property string $latitude 维度
 * @property int $pre_sale_office 是否有预售处
 * @property string remark
 * @property int $open_type 1合营 2直营
 * @property string $create_time 创建时间
 * @property string $update_time 更新时间
 */
class OpenProject extends Base
{
    const CONSORTIUM = 1;
    const DIRECT = 2;

    const WILL_OPEN = 1;
    const OPENING = 2;
    const CLOSE = 3;

    const NO_REPLENISHMENT = 0; // 不能补单

    const PROJECT_LIMIT=15; //健身房列表
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_open_project';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            /*[['flow_id', 'series_id', 'province_id', 'city_id', 'district_id', 'gym_area'], 'integer'],
            [['series_id'], 'required'],
            [['gym_name', 'province_name', 'city_name', 'district_name'], 'string', 'max' => 32],
            [['address'], 'string', 'max' => 64],
            [['tel'], 'string', 'max' => 20],
            [['create_time'], 'integer'],*/
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'flow_id' => 'Flow ID',
            'series_id' => 'Series ID',
            'gym_name' => 'Gym Name',
            'province_id' => 'Province ID',
            'province_name' => 'Province Name',
            'city_id' => 'City ID',
            'city_name' => 'City Name',
            'district_id' => 'District ID',
            'district_name' => 'District Name',
            'address' => 'Address',
            'gym_area' => 'Gym Area',
            'tel' => 'Tel',
            'material_discount' => 'Material Discount',
            'longitude' => 'Longitude',
            'latitude' => 'Latitude',
           // 'pre_sale_office' => 'Pre Sale Office',
            'open_type' => 'Open Type',
            'create_time' => 'Create Time',
        ];
    }

    public static function getGymInfoByProjectId($projectId)
    {
        return self::find()
               ->where(['id' => $projectId])
               ->one();
    }

    public static function getGymInfoByarea($area,$gymStatus,$page)
    {
        return self::find()
            ->select("series_id,province_name,city_name,district_name,gym_name,open_type,start_date,end_date,presale_cost")
            ->where(["like","province_name", $area])
            ->orWhere(["like","city_name" ,$area])
            ->orWhere(["like","district_name", $area])
            ->orWhere(["like","address" , $area])
            ->orWhere(["like",'gym_name' , $area])
            ->andWhere(['gym_status' => $gymStatus])
            ->andWhere([">","id",$page])
            ->limit(self::PROJECT_LIMIT)
            ->asArray()
            ->all();

    }

    public static function getGymDescBySeriesId($seriesId,$gymStatus)
    {
        return self::find()
            ->select("gym_name,province_name,city_name,district_name,open_type")
            ->where(['series_id'=>$seriesId])
            ->andWhere(['gym_status' => $gymStatus])
            ->one();
    }

    public static function getProjectList()
    {
        return self::find()
            ->select("series_id,province_name,city_name,district_name,gym_name,open_type")
            ->limit(self::PROJECT_LIMIT)
            ->asArray()
            ->all();
    }

    public static function getGym($condition){
        return self::find()
            ->where($condition)
            ->asArray()
            ->all();
    }

    public function getOpenContract()
    {
        return $this->hasOne(OpenContract::class, ["series_id" => "series_id"]);
    }

    /**
     * @param $conditions
     * @return $this|array|null|\yii\db\ActiveRecord
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/9 11:15:12
     * @Author: fangxing@likingfit.com
     */
    public static function getGymAndContract($conditions)
    {
        $projectModel = new OpenProject();
        $with = ['openContract'];
        return $projectModel->getOneRecord($conditions, $with);
    }

    public function getWorkItem()
    {
        return $this->hasOne(WorkItem::class, ["series_id" => "series_id"]);
    }

    public function getOpenFlow()
    {
        return $this->hasOne(OpenFlow::class, ["series_id" => "series_id"]);
    }

    public function getGymSeries(){
        return $this->hasMany(GymSeries::class, ["project_id" => "id"]);
    }

    public function getProjectDirector(){
        return $this->hasMany(ProjectDirector::class, ["series_id" => "series_id"]);
    }

    public function getChargeList(){
        return $this->hasMany(ChargeList::class, ["series_id" => "series_id"]);
    }

    public static function convert2string(&$results, $labels = [])
    {
        parent::convert2string($results, array_merge([
            "open_type" => "gym.open_type.name"
        ], $labels));
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = time();
        }
        $this->update_time = time();
        return true;
    }
}
