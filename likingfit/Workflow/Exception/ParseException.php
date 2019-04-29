<?php
namespace likingfit\Workflow\Exception;

use likingfit\Workflow\Workflow;
class ParseException extends \Exception{
    public function __construct($msg){
        Workflow::getLogService()->log($msg);
        parent::__construct($msg);
    }
}