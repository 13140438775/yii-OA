<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:38:50
 */
namespace app\models;

class GetGoodsDetail extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 't_get_goods_detail';
    }

    public function getGoods(){
        return $this->hasOne(Goods::className(),['goods_id' => 'goods_id'])
            ->select(['goods_id','goods_name','brand','model','unit','inventory']);
    }
}