<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/28 10:29:45
 */

namespace app\models;

use yii\db\Expression;
use yii\helpers\ArrayHelper;

class Goods extends Base
{
    const pageNum = 15;
    const Y_EARLY_WARNING = 1;
    const N_EARLY_WARNING = 0;

    public static function tableName()
    {
        return 't_goods';
    }

    public static function getOne($goodsId)
    {
        return self::findOne(['goods_id' => $goodsId]);
    }

    public static function goodsList($condition = null, $page = 1, $pageSize = 15,$export=false)
    {
        $query = self::find()->joinWith('img')->joinWith('supplier');
        if (!empty($condition)) {
            $query = $query->onCondition($condition);
        }
        if ($export){
            return $result = $query->select(['distinct(t_goods.id)','t_goods.*'])->orderBy(['id'=>SORT_DESC])->asArray()->all();
        }
        $list = clone $query;
        $count = $query->count('distinct(t_goods.id)');
        if (!empty($page)) {
            $list = $list->limit($pageSize)->offset(($page - 1) * $pageSize);
        }
        $result = $list->select(['distinct(t_goods.id)','t_goods.*'])->orderBy(['goods_id'=>SORT_ASC])->asArray()->all();
        return array(
            'rows' => $result,
            'total' => $count
        );

    }

    public function getImg()
    {
        return $this->hasMany(GoodsImg::className(), ['goods_id' => 'goods_id'])->onCondition(['img_status'=>AVAILABLE])
            ->select(['id AS img_id', 'goods_img', 'goods_id']);
    }

    public function getSupplier()
    {
        return $this->hasMany(Supplier::className(), ['id' => 'supplier_id'])
            ->viaTable(GoodsSupplier::tableName(),['goods_id'=>'goods_id'])
            ->select(['id', 'supplier_name']);
    }


    public static function getGoodsInfo($goodsId){
        $condition = [
            'goods_id'=>$goodsId
        ];
        return self::find()
            ->where($condition)
            ->indexBy('goods_id')
            ->asArray()
            ->all();
    }

    public function getSupplierOne()
    {
        return $this->hasOne(Supplier::className(), ['id' => 'supplier_id'])
            ->viaTable(GoodsSupplier::tableName(),['goods_id'=>'goods_id'])
            ->select(['id', 'supplier_name']);
    }
}