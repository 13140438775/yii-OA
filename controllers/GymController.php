<?php
/**
 * Created by PhpStorm.
 * @Author: apple@likingfit.com
 * @CreateTime 2018/3/6 10:41:04
 */

namespace app\controllers;

use app\models\OpenProject;
use app\services\GymService;

class GymController extends BaseController
{

    /**
     * @return 检测客户是否存在
     * @CreateTime 2018/3/9 15:40:54
     * @Author: chenxuxu@likingfit.com
     */
     public function actionCheck()
     {
         $params = \Yii::$app->request->post();
         $response = GymService::checkCustomer($params);
         return $response;
     }

    /**
     * 健身房详情
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/9 17:51:11
     * @Author: fangxing@likingfit.com
     * @Author: chenxuxu@likingfit.com
     */
    public function actionDetail()
    {
        $data = \yii::$app->request->post();
        $projectId = $data['project_id'];
        $gymDetail = GymService::detail($projectId);
        return $gymDetail;

    }

    /**
     * 新增直营健身房
     * @return array
     * @throws \app\exceptions\Gym
     * @throws \yii\db\Exception
     * @CreateTime 2018/3/6 17:04:07
     * @Author     : screamwolf@likingfit.com
     */
    public function actionSave ()
    {
        //TODO 流程推动
        $params = \Yii::$app->request->post();
        GymService::openGym($params);
        return [];
    }

    /**
     * 新增合营健身房
     * @return array
     * @throws \app\exceptions\Gym
     * @throws \yii\db\Exception
     * @CreateTime 2018/3/9 2:13:07
     * @Author     : chenxuxu@likingfit.com
     */
    public function actionRecord()
    {
        $params = \Yii::$app->request->post();
        return GymService::heGym($params);
    }


    /**
     * 健身房查询
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/9 14:05:18
     * @Author: chenuxu@likingfit.com
     * @Author: fangxing@likingfit.com
     */
    public function actionSearch()
    {
        $params = \Yii::$app->request->post();
        $data = GymService::search($params);
        return $data;
    }

    /**
     * 取消开店
     *
     * @throws \Exception
     * @CreateTime 18/4/9 22:32:54
     * @Author: fangxing@likingfit.com
     */
    public function actionClose(){
        $params = \Yii::$app->request->post();
        GymService::closeGym($params);
    }

    /**
     * 开店日志
     *
     * @return mixed
     * @CreateTime 18/4/4 14:08:02
     * @Author: fangxing@likingfit.com
     */
    public function actionGetOpenLog(){
        $params = \Yii::$app->request->post();
        return GymService::getOpenLog($params);
    }

    /**
     * 开店流程图
     *
     * @return array
     * @throws \ReflectionException
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/4 15:39:15
     * @Author: fangxing@likingfit.com
     */
    public function actionGetGraph(){
        $params = \Yii::$app->request->post();
        return GymService::getGraph($params);
    }

    /**
     * 流程图节点信息
     *
     * @return array|null|\yii\db\ActiveRecord
     * @CreateTime 18/4/8 11:24:05
     * @Author: fangxing@likingfit.com
     */
    public function actionGetNodeInfo(){
        $workItemId = \Yii::$app->request->post("work_item_id");
        return GymService::getNodeInfo($workItemId);
    }

    /**
     * 健身房下拉列表
     *
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/11 17:20:55
     * @Author: fangxing@likingfit.com
     */
    public function actionGetSimpleList(){
        $gym_name = \Yii::$app->request->post("gym_name");
       return GymService::getGymListForAutoComplete($gym_name);
    }

    public function actionGetGym(){
        $params = \Yii::$app->request->post();
        return GymService::getGym($params);
    }
}
