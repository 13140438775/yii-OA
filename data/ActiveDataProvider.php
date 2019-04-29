<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/6 13:50:00
 */
namespace app\data;

class ActiveDataProvider extends \yii\data\ActiveDataProvider
{
    public $attributes;

    protected function prepareModels()
    {
        $all = parent::prepareModels();

        foreach ($all as $k=>$v){
            foreach ($this->attributes as $name => $value){
                if(isset($v[$name])) {
                    $all[$k][$name] = call_user_func($value, $v);
                }
            }
        }

        return $all;
    }

    public function getCount()
    {
        $query = clone $this->query;
        return $query->count();
    }
}