<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/3/20 14:05:07
 */

namespace app\helpers;

use yii\httpclient\Client;

class Helper
{
    public static function curl($url, $method='GET'){
        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => $method
        ));

        $response = curl_exec($curl);
        $err = curl_error($curl);

        curl_close($curl);

        if ($err) {
            return json_encode($err);
        } else {
            return $response;
        }
    }

    /**
     * php curl模拟post提交
     *
     * @param string $url http://xxx.xxx.xxx.xx/xx/xxx/top.php
     * @param array $data 需要post的数据
     * @return mixed
     */
    public static function curl_post($url, $data) {
        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_URL => $url
        ];
        $ch = curl_init();
        curl_setopt_array($ch, $options);
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }

    /**
     * 发送短信
     *
     * @param $phones
     * @param $templateId
     * @param null $smsData
     * @return bool
     * @CreateTime 18/4/26 20:54:00
     * @Author: fangxing@likingfit.com
     */
    public static function sendSms ($phones, $templateId, $smsData = null) {
        if (!$phones || !$templateId) {
            return false;
        }

        if (!is_array($phones)) {
            $phones = [$phones];
        }

        $params = [
            'phones'      => $phones,
            'template_id' => $templateId,
            'data'        => $smsData,
        ];

        $client = new Client();
        $response = $client->createRequest()
            ->setFormat(Client::FORMAT_JSON)
            ->setMethod('post')
            ->setUrl(SMS_URL)
            ->setData($params)
            ->send();
        if ($response->isOk) {
            return true;
        }
        \Yii::info(print_r($response->getData(), 1));
        return false;
    }
}