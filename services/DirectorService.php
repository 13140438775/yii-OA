<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/4/13 21:58:47
 */

namespace app\services;


use app\models\ProjectDirector;

class DirectorService
{
    /**
     * @param $seriesId
     * @param $role_name
     * @return null|ProjectDirector
     * @CreateTime 18/4/14 16:18:21
     * @Author: fangxing@likingfit.com
     */
    public static function getSeriesDirector($seriesId, $role_name){

        return ProjectDirector::findOne(["series_id" => $seriesId, "role_name" => $role_name]);
    }

    public static function assignStaff($info)
    {
        $director = new ProjectDirector();
        $director->setAttributes($info, false);
        return $director->save();
    }
}