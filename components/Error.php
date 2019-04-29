<?php

namespace app\components;

use app\exceptions\Base;
use Yii;
use yii\base\ErrorHandler;

class Error extends ErrorHandler
{
    /**
     * 调试模式
     * @var bool
     */
    public $debug = false;

    /**
     * 生产环境错误默认码
     * @var int
     */
    public $prodErrCode = 10000;

    /**
     * 生产环境错误默认消息
     * @var string
     */
    public $prodErrMsg = '系统繁忙,请稍后重试!';

    /**
     * 生产环境错误默认数据
     * @var array
     */
    public $prodErrData = [];

    /**
     * 错误处理
     * @param Base $exception
     */
    public function renderException($exception)
    {
        Yii::error(self::convertExceptionToString($exception));

        if (PHP_SAPI === 'cli') {
            return;
        }

        Yii::$app->response->data = $this->filterData($exception);
        Yii::$app->response->send();
    }

    /**
     * 获取异常错误响应错误
     * @param $exception
     * @return array
     * @CreateTime 2018/3/2 13:52:32
     * @Author     : pb@likingfit.com
     */
    public function filterData($exception)
    {
        $data = [
            'err_code' => $this->prodErrCode,
            'err_msg'  => $this->prodErrMsg,
            'data'     => $this->prodErrData
        ];

        if ($exception instanceof Base) {
            $data['err_code'] = $exception->getCode();
            $data['err_msg']  = $exception->getMessage();
            if (!is_null($exception->getData())) {
                $data['data'] = $exception->getData();
            }
        }

        if ($this->debug) {
            $data['debug'] = [
                'code' => $exception->getCode(),
                'msg'  => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace'=> explode(PHP_EOL,$exception->getTraceAsString())
            ];
        }

        return $data;
    }
}