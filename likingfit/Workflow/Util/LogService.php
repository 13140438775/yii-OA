<?php
namespace likingfit\Workflow\Util;

abstract class  LogService{
    public static function log($str){
        echo $str.PHP_EOL;
    }
}