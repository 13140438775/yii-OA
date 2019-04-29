<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 16/12/21
 * Time: 下午6:45
 */
namespace likingfit\Workflow\Exception;
use likingfit\Workflow\Workflow;

class ProcessException extends \Exception{
    public function __construct($processId, $msg){
        $log = $processId . '-' . $msg;
        Workflow::getLogService()->log($log);
        parent::__construct($log);
    }
}