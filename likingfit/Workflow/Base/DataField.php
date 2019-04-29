<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/15
 * Time: 下午5:18
 */
namespace likingfit\Workflow\Base;

/**
 * Class DataField
 * @package likingfit\Workflow\Base
 */
class DataField extends Object {
    private  $value;
    
    public function getValue() {
        return $this->value;
    }
    
    public function setValue($value) {
        $this->value = $value;
    }
}