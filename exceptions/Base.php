<?php

namespace app\exceptions;

class Base extends \Exception
{
    private $_data;

    /**
     * 错误码对应错误消息
     * @var array
     */
    public static $reasons = [];

    public function __construct($code = null, $data = null)
    {
        if ($code) {
            $this->code    = $code;
            $this->message = self::getReason($code);
        }

        if ($data) {
            $this->_data = $data;
        }
    }

    /**
     * 获取错误码对应错误消息
     * @param $code
     * @return mixed|string
     * @CreateTime 2018/3/2 13:36:15
     * @Author     : pb@likingfit.com
     */
    public static function getReason($code)
    {
        return isset(static::$reasons[$code]) ? static::$reasons[$code] : 'Unknown error code';
    }

    public function getData(){
        return $this->_data;
    }
}