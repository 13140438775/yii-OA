<?php

namespace app\services;

use app\models\OpenFlow;
use app\models\OrderEntry;
use app\models\ChargeList;
use app\models\OpenProject;
use yii\db\Expression;
use yii\helpers\VarDumper;
class WorkflowBusinessService
{
    /**
     * 侧边栏 - 录入开业批准日期
     * @param $request
     * @return bool
     * @CreateTime 2018/4/19 15:04:29
     * @Author: heyafei@likingfit.com
     */
    public static function entryOpeningApprovalSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $open_flow = OpenFlow::findOne(['series_id' => $series_id]);
        $open_flow->expect_open_time = $request['expect_open_time'];
        return $open_flow->save();
    }

    /**
     * 侧边栏 - 录入开店成本
     * @param $request
     * @return array|\yii\db\ActiveRecord[]
     * @CreateTime 2018/3/12 17:07:57
     * @Author: heyafei@likingfit.com
     */
    public static function entryShopCostInit($request)
    {
        $project_id = $request['project_id'];
        $series_id = $request['flow']['series_id'];
        $select = [
            'gym_name' => 't_open_project.gym_name',
            'open_type' => 't_open_project.open_type',
            'series_id' => 't_order_entry.series_id',
            'project_id' => 't_order_entry.project_id',
            'total_amount' => new Expression('sum(t_order_entry.total_amount)'),
            'coupon_amount' => new Expression('sum(t_order_entry.coupon_amount)'),
            'actual_amount' => new Expression('sum(t_order_entry.actual_amount)'),
            'order_count' => new Expression('count(t_order_entry.project_id)')
        ];
        $_order = OrderEntry::find()
            ->select($select)
            ->leftJoin('t_open_project', 't_open_project.id = t_order_entry.project_id')
            ->where(['t_order_entry.project_id' => $project_id])
            ->groupBy('t_order_entry.project_id')
            ->asArray()
            ->one();
        $_order['open_type'] = ($_order['open_type'] == OpenProject::CONSORTIUM) ? "合营" : "直营";

        $_charge = ChargeList::find()
            ->select(['total_amount' => new Expression('sum(total_amount)')])
            ->where(['series_id' => $series_id, 'is_use' => 1])
            ->groupBy('series_id')
            ->asArray()
            ->one();

        $_order['open_cost'] = ($_order['actual_amount'] + $_charge['total_amount']) / 100;
        $_order['other_cost'] = $_charge['total_amount'] / 100;

        $_order['total_amount'] = $_order['total_amount'] / 100;
        $_order['coupon_amount'] = $_order['coupon_amount'] / 100;
        $_order['actual_amount'] = $_order['actual_amount'] / 100;
        return $_order;
    }

    /**
     * 侧边栏 - 录入开店成本
     * @param $request
     * @return bool
     * @throws \yii\base\InvalidConfigException
     * @throws \yii\db\Exception
     * @CreateTime 2018/4/19 15:05:02
     * @Author: heyafei@likingfit.com
     */
    public static function entryShopCostSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $cost_list = $request['cost_list'];
        $project_id = $request['project_id'];

        $charge_list = new ChargeList();
        $data = [];
        foreach ($cost_list as $val) {
            if ($val['amount_type'] == 2) {
                $val['total_amount'] = 0 - $val['total_amount'];
            }
            $data[] = [
                'series_id' => $series_id,
                'total_amount' => $val['total_amount'] * 100,
                'remark' => isset($val['remark_label']) ? $val['remark_label']: '',
                'is_use' => 1,
                'amount_type' => $val['amount_type'],
                'create_time' => time()
            ];
        }
        $charge_list->batchInsert($data);
        $open_project = OpenProject::findOne(['id' => $project_id]);

        $_order = self::entryShopCostInit($request);
        if($_order){
            $open_project->open_cost = isset($_order['open_cost']) ? ($_order['open_cost'] * 100): 0;
            $open_project->order_cost = isset($_order['actual_amount']) ? ($_order['actual_amount'] * 100): 0;
            $open_project->other_cost = isset($_order['other_cost']) ? ($_order['other_cost'] * 100): 0;
            $open_project->save();
        }
        return true;
    }

    /**
     * 侧边栏 - 录入正式营业日期
     * @param $request
     * @return bool
     * @CreateTime 2018/3/8 14:41:04
     * @Author: heyafei@likingfit.com
     */
    public static function entryBusinessDateSave($request)
    {
        $series_id = $request['flow']['series_id'];
        $open_flow = OpenFlow::findOne(['series_id' => $series_id]);
        $open_flow->open_time = $request['open_time'];
        $open_flow->save();

        $open_project = OpenProject::findOne(['series_id' => $series_id]);
        $open_project->gym_status = OpenProject::OPENING;
        $open_project->save();
        return true;
    }
}