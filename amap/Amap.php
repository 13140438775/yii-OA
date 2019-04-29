<?php
/**
 * Created by PhpStorm.
 * @Author     : pb@likingfit.com
 * @CreateTime 2018/4/13 13:16:21
 */
namespace app\amap;

use app\helpers\Helper;

class Amap {
    // 高德密钥
    public static $key = '03fa243e309ce1682afea92eea1dae71';
    // 高德地址搜索
    public static $url = "http://restapi.amap.com/v3/place/text?key=%s&city=%s&citylimit=true&keywords=%s&offset=1";

    public static function search($city, $address){
        $url = sprintf(self::$url, self::$key, $city, $address);
        $data = json_decode(Helper::curl($url), true);

        $res = [
            'longitude' => '0',
            'latitude' => '0'
        ];
        if(isset($data['pois'][0])){
            list($res['longitude'], $res['latitude']) = explode(',', $data['pois'][0]['location']);
        }
        return $res;
    }
}