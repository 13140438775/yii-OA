<?php
/**
 * Created by PhpStorm.
 * @Author: fangxing@likingfit.com
 * @CreateTime 18/3/17 13:32:23
 */

namespace app\services;


use app\models\Collection;
use app\models\OpenProject;
use app\models\PayList;
use app\models\WorkItem;

class IndexService
{
    /**
     * @return array
     * @throws \Throwable
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/4/27 13:28:38
     * @Author: fangxing@likingfit.com
     */
    public static function getUserWorkItem()
    {
        $data = \Yii::$app->request->post();
        return WorkItemService::getUserWorkItems($data, WorkItem::CLAIMED);
    }

    public static function feeChart($start, $end)
    {
        $legend = \Yii::$app->params["cost_type"];
        $feeTypes = array_keys($legend);
        $statistics = PayList::find()
            ->where([
                "and",
                [">=", "create_time", $start],
                ["<", "create_time", $end],
                [
                    "pay_status" => PayList::ALL_ARRIVED,
                    "is_valid" => AVAILABLE,
                    "cost_type" => $feeTypes
                ],
            ])
            ->groupBy("cost_type, date_group")
            ->select([
                "date_group" => "FROM_UNIXTIME(create_time, \"%Y/%m/%d\")",
                "cost_type",
                "money" => "SUM(actual_amount)"
            ])
            ->asArray()
            ->all();
        $statistics = array_group($statistics, "date_group");

        $originX = getX($start, $end);
        $x = array_map(function ($val){
            return substr($val, 6);
        }, $originX);

        $series = [];
        foreach ($feeTypes as $i => $type) {
            $series[$i] = [
                "name" => $legend[$type],
                "data" => []
            ];
            foreach ($originX as $day) {
                if (!array_key_exists($day, $statistics)) {
                    array_push($series[$i]["data"], 0);
                    continue;
                }
                $flag = false;
                foreach ($statistics[$day] as $v) {
                    if ($type == $v["cost_type"]) {
                        array_push($series[$i]["data"], $v["money"] / 100);
                        $flag = true;
                        break;
                    }
                }
                if (!$flag) {
                    array_push($series[$i]["data"], 0);
                }
            }
        }
        return compact("x", "series", "legend");
    }

    public static function feeStatistic()
    {
        $joinFee = PayList::find()
            ->where([
                "pay_status" => PayList::ALL_ARRIVED,
                "cost_type" => PayList::JOIN_FEE,
                "is_valid" => AVAILABLE,
            ])
            ->sum("actual_amount");
        $joinFee = $joinFee / 100;

        $fee = OpenProject::find()
            ->groupBy("open_type")
            ->select(["fee" => "SUM(open_cost)", "open_type"])
            ->indexBy("open_type")
            ->asArray()
            ->all();

        $feeConsortium = 0;
        if (isset($fee[OpenProject::CONSORTIUM])) {
            $feeConsortium = $fee[OpenProject::CONSORTIUM]["fee"] / 100;

        }

        $feeDirect = 0;
        if (isset($fee[OpenProject::DIRECT])) {
            $feeDirect = $fee[OpenProject::DIRECT]["fee"] / 100;
        }

        return compact("joinFee", "feeConsortium", "feeDirect");
    }

    /**
     * 收藏待办事项
     *
     * @param $workItemId
     * @CreateTime 18/4/10 15:52:26
     * @Author: fangxing@likingfit.com
     */
    public static function collect($workItemId)
    {
        $staffId = \Yii::$app->user->getId();
        $model = Collection::findOne([
            "staff_id" => $staffId,
            "work_item_id" => $workItemId,
            "relation_status" => AVAILABLE
        ]);
        if(is_null($model)){
            $model = new Collection();
            $model->staff_id = $staffId;
            $model->work_item_id = $workItemId;
        }else{
            $model->relation_status = UNAVAILABLE;
        }
        $model->save(false);
    }
}