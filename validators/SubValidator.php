<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/10 15:31:08
 */

namespace app\validators;


use yii\base\DynamicModel;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\validators\Validator;

class SubValidator extends Validator
{

    public $rules = [];

    public $sub_attributes = [];

    /**
     * @var DynamicModel
     */
    protected $subModel;

    public function init()
    {
        parent::init();
        /*if ($this->message === null) {
            $this->message = \Yii::t('yii', '{attribute} is invalid.');
        }*/
    }

    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @throws InvalidConfigException
     * @CreateTime 18/3/10 16:36:43
     * @Author: fangxing@likingfit.com
     */
    public function validateAttribute($model, $attribute)
    {
        $value = $model->$attribute;
        if (!(is_array($value) || $value instanceof \ArrayAccess)){
            $value = [];
        }
        foreach ($this->sub_attributes as $sub_attribute){
            if(!array_key_exists($sub_attribute, $value)){
                $value[$sub_attribute] = null;
            }
        }
        $results = $this->validateValue($value);
        if(!empty($results)){
            $model->addErrors($results);
        }
        $model->$attribute = $this->subModel->getAttributes();
        /*if (is_array($value) || $value instanceof \ArrayAccess) {
            $results = $this->validateValue($value);
            if(!empty($results)){
                $model->addErrors($results);
            }
        }else{
            $this->addError($model, $attribute, $this->message);
        }*/
    }

    /**
     * @param mixed $value
     * @return array|null
     * @throws InvalidConfigException
     * @CreateTime 18/3/10 16:25:53
     * @Author: fangxing@likingfit.com
     */
    public function validateValue($value)
    {
        /*foreach ($this->sub_attributes as $attribute){
            if(!array_key_exists($attribute, $value)){
                $value[$attribute] = null;
            }
        }*/
        $this->subModel = DynamicModel::validateData($value, $this->rules);
        if($this->subModel->hasErrors()){
            return $this->subModel->getErrors();
        }
        return null;
    }
}