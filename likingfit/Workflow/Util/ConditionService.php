<?php
namespace likingfit\Workflow\Util;

use likingfit\Workflow\Workflow;
class ConditionService{
    private $expression;
    private $param;
    private $config = [
	   '&amp;' => '&',
	   '&lt;' => '<',
	   '&gt;' => '>'
    ];
    
    
    /**
     * 表达式解析
     */
    public function resolve($expression,$param){
    	$this->expression = $expression;
    	$this->param = $param;
    	$this->formatExpression();
    	$this->setParam();
    	return $this->judge();
    }
    
    /**
     * 格式化条件表达式
     */
    private function formatExpression(){
    	foreach ($this->config as $search => $replace){
    	    $this->expression = str_replace($search, $replace, $this->expression);
    	}
    }
    
    /**
     * 设置参数
     */
    private function setParam(){
    	foreach ($this->param as $paramName => $value){
    	    $this->expression = str_replace($paramName, $value, $this->expression);
    	}
    }
    
    private function judge(){
    	$cstr = $this->expression;
    	$result = eval("return $cstr;");
    	Workflow::getLogService()->log("condition:".$this->expression." resolve to-->".$result);
    	return $result;
    }
    
   

}