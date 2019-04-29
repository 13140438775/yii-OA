<?php
/**
 * Created by PhpStorm.
 * User: fangxing
 * Date: 17/9/6
 * Time: 下午4:00
 */

namespace app\behaviors;


use app\exceptions\CheckParameterException;
use yii\base\Action;
use yii\base\ActionFilter;
use app\exceptions\Param;
use yii\helpers\HtmlPurifier;


class CheckParametersBehaviors extends ActionFilter
{
    /**
     * 检测参数
     *
     * @param Action $action
     * @return bool
     * @throws Param
     * @CreateTime 18/4/16 10:53:51
     * @Author: fangxing@likingfit.com
     */
    public function beforeAction($action){
        $request = \Yii::$app->getRequest();
        $params = array_merge($request->get(), $request->post());
        return $this->isContainsSpecialCharacters($params);
    }

    /**
     * 检测参数
     *
     * @param $params
     * @return bool
     * @throws Param
     * @CreateTime 18/4/16 10:52:14
     * @Author: fangxing@likingfit.com
     */
    protected function isContainsSpecialCharacters($params){
        $valid = true;
        foreach ($params as $value){
            if(is_array($value)){
                $valid == $valid && $this->isContainsSpecialCharacters($value);
            }else{
                $clean = HtmlPurifier::process($value);
                $valid = ($valid && $clean == $value);
                if(!$valid){
                    throw new CheckParameterException(CheckParameterException::ILLEGAL_STRING);
                }
            }
        }
        return $valid;
    }
}