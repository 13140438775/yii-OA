<?php

namespace app\models;

use Yii;

class CustomerRemark extends Base
{
    /**
     * 商务洽谈 限制条数
     */
    const NegotiateLimit = 2;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_customer_remark';
    }

    /**
     * @return \yii\db\ActiveQuery
     * @CreateTime 2018/3/2 15:40:20
     * @Author: heyafei@likingfit.com
     */
    public function getStaff(){
        return $this->hasOne(Staff::className(),['id' => 'operator_id']);
    }

    /**
     * 备注时间格式化
     *
     * 小于1小时，以分钟显示，如：5分钟前
     * 小于12小时，以小时显示，如：11小时前
     * 大于12小时，显示具体的日期及时间，如：10月2号 12:34
     *
     * @param $time
     * @return false|string
     * @CreateTime 2018/3/6 14:09:08
     * @Author     : pb@likingfit.com
     */
    public static function filterCreateTime($time){
        $diff = time()-$time;
        if($diff < 3600){
            return ceil($diff/60).'分钟前';
        }elseif ($diff < 12*3600){
            return ceil($diff/3600).'小时前';
        }else{
            return date('m月d号 H:i', $time);
        }
    }

    public function beforeSave($insert){
        if(!parent::beforeSave($insert)){
            return false;
        }
        if($insert){
            $this->create_time = time();
        }
        return true;
    }
}
