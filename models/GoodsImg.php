<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/3/1 14:29:09
 */
namespace app\models;

class GoodsImg extends \yii\db\ActiveRecord{
    public static function tableName()
    {
        return 't_goods_img';
    }
    public static function unavailable($goodsId){
        self::updateAll(['img_status'=>UNAVAILABLE,'update_time'=>time()],['goods_id'=>$goodsId]);
    }

}