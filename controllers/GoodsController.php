<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:46:44
 */
namespace app\controllers;

use app\models\Type;
use app\services\GoodsService;

class GoodsController extends BaseController{
    public function actionReaderExcel(){
        $goodsService = new  GoodsService();
        $results = $goodsService->readExcel();
        return $results;
    }

    public function actionSave(){
        $param = \Yii::$app->request->post();
        GoodsService::addGoods($param);
    }

    public function actionType(){
        $type =  Type::getTypes();
        return $type;
    }

    public function actionTypeSub(){
        $param = \Yii::$app->request->post();
        $condition = [
            'id'=> $param['type_id']
        ];
        $sub = Type::find()->with('sub')->onCondition($condition)->asArray()->all();
        return $sub[0]['sub'];
    }


    public function actionGoodsList(){
        $params = \Yii::$app->request->post();
        $list = GoodsService::getGoodsList($params);
        return $list;
    }

    public function actionByTypeSupplier(){
        $params = \Yii::$app->request->post();
        $result = GoodsService::byTypeSupplier($params['type_id']);
        return $result;
    }

    public function actionGoodsDetail(){
        $params = \Yii::$app->request->post();
        $result = GoodsService::getGoodsDetail($params['goods_id']);
        return empty($result['rows'][0])?'':$result['rows'][0];
    }








}

