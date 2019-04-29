<?php

const EPOCH = 1464948542000;

/**
 * @param string $prefix
 * @param int $len
 * @return string
 * @throws \yii\base\Exception
 * @CreateTime 18/3/6 15:33:00
 * @Author: fangxing@likingfit.com
 */
function generateId($prefix = '', $len = 32)
{
    return $prefix . date("Ymd") . \Yii::$app->security->generateRandomString($len);
}

/**
 * @param string $prefix
 * @return string
 * @throws Exception
 * @CreateTime 18/3/6 16:36:04
 * @Author: fangxing@likingfit.com
 */
function generateIntId($prefix = '')
{
    return $prefix . date("Ymd") . mt_rand(1000000, 9999999);
}

function generateIntOrderId($prefix = '')
{
    return $prefix . date("YmdHis") . mt_rand(10000, 99999);
}

/**
 * 数组分组
 *
 * @param $arr
 * @param $key
 * @param $closure
 * @param bool $forget
 * @return array
 * @CreateTime 18/3/14 18:37:18
 * @Author: fangxing@likingfit.com
 */
function array_group($arr, $key, $closure=null, $forget=false)
{
    $new_arr = [];
    foreach ($arr as $row){

        if(is_callable($closure)){
            $row = call_user_func($closure, $row);
        }
        $kv = $row[$key];
        if($forget){
            unset($row[$key]);
        }
        $new_arr[$kv][] = $row;
    }
    return $new_arr;
}

function getX($start, $end)
{
    $x = [];
    while ($start < $end){
        array_push($x, date("Y/m/d", $start));
        $start+=86400;
    }
    return $x;
}

function array_merge2($a, $b)
{
    $args = func_get_args();
    $res = array_shift($args);
    while (!empty($args)) {
        foreach (array_shift($args) as $k => $v) {
            if (is_array($v) && isset($res[$k]) && is_array($res[$k])) {
                $res[$k] = array_merge2($res[$k], $v);
            } else {
                $res[$k] = $v;
            }
        }
    }

    return $res;
}