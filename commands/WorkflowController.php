<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/3/28
 * Time: 上午11:51
 */

namespace app\commands;

use app\models\Flow;
use app\models\RightSideConfig;
use app\models\WorkItem;
use app\services\FlowService;
use likingfit\Workflow\Workflow;
use yii\console\Controller;
use yii\helpers\Console;
use yii\helpers\Json;

class WorkflowController extends Controller
{
    /**
     * 保存工作流资源文件
     * @param        $xmlPath
     * @param string $configFile
     * @CreateTime 2018/4/12 13:56:13
     * @Author     : pb@likingfit.com
     */
    public function actionStoreXml($xmlPath, $configFile='')
    {
        $fullPath = \Yii::getAlias("@app") . DIRECTORY_SEPARATOR . $xmlPath;
        $dirHandle = opendir($fullPath);
        $data = [];
        $date = date("Y-m-d H:i:s");
        while (($filename = readdir($dirHandle)) !== false) {
            if (strpos($filename, ".") === 0) {
                continue;
            }
            $info = pathinfo($filename);
            $name = $info['filename'];
            $data[] = [
                'NAME' => $name,
                'PROCESS_ID' => $name,
                'DEFINITION_TYPE' => 'fpdl',
                'DISPLAY_NAME' => '',
                'DESCRIPTION' => '',
                'VERSION' => 1,
                'STATE' => 1,
                'PROCESS_PATH' => DIRECTORY_SEPARATOR . $xmlPath . DIRECTORY_SEPARATOR . $filename,
                'CONFIG_PATH' => $configFile,
                'PUBLISH_TIME' => $date
            ];
        }
        $fields = array_keys(reset($data));
        try {
            $affect_rows = \Yii::$app->db->createCommand()
                ->batchInsert("T_FF_DF_WORKFLOWDEF", $fields, $data)
                ->execute();
            $this->stdout("insert " . $affect_rows . " rows", Console::FG_GREEN);
        } catch (\Exception $e) {
            $this->stderr($e->getMessage(), Console::FG_RED);
        }

    }

    /**
     * 入库配置
     *
     * @param $xmlPath
     * @CreateTime 18/3/17 16:06:02
     * @Author: fangxing@likingfit.com
     */
    public function actionMakeConfig($xmlPath)
    {
        $appPath = \Yii::getAlias("@app");
        $fullPath = $appPath . DIRECTORY_SEPARATOR . $xmlPath;
        $dirHandle = opendir($fullPath);
        $config = [];
        while (($filename = readdir($dirHandle)) !== false) {
            if (strpos($filename, ".") === 0) {
                continue;
            }
            $file = $fullPath . DIRECTORY_SEPARATOR . $filename;
            $xml = simplexml_load_file($file);
            $activities = $xml->children("fpdl", true)->Activities;
            foreach ($activities->children("fpdl", true) as $activity) {
                $activityAttr = $activity->attributes();
                $id = (string)$activityAttr->Id;
                $displayName = (string)$activityAttr->DisplayName;

                $tasks = $activity->children("fpdl", true)->Tasks->children("fpdl", true);
                if (empty($displayName) || strcmp($displayName, "辅助模块") === 0 || count($tasks) == 0) {
                    continue;
                }

                if (strcmp($tasks[0]->attributes()->Type, "SUBFLOW") === 0) {
                    continue;
                }
                $activityCfg = [
                    "activity_id" => $id,
                    "display_name" => $displayName
                ];
                $user_data = [];
                $extendAttributes = @$activity->children("fpdl", true)->ExtendedAttributes?:null;
                if($extendAttributes instanceof \SimpleXMLElement){
                    foreach ($extendAttributes->children("fpdl", true) as $extendAttribute) {
                        $value = $extendAttribute->attributes();
                        $attrName = (string)$value->Name;
                        $attrValue = (string)$value->Value;
                        if (strcmp($attrName, "page") === 0) {
                            $activityCfg["page"] = $attrValue;
                        }
                        if (strcmp($attrName, "role_name") === 0) {
                            $activityCfg["role_name"] = $attrValue;
                        }
                        if (in_array($attrName, ["order_type", "cost_type", "type"])) {
                            $user_data[$attrName] = $attrValue;
                        }
                    }
                }
                $activityCfg["page"] = isset($activityCfg["page"])?$activityCfg["page"]:'';
                $activityCfg["user_data"] = !empty($user_data)?Json::encode($user_data):"{}";
                $config[] = $activityCfg;
            }
        }
        try {
            $rows = (new RightSideConfig)->batchInsert($config);
            $this->stdout("insert " . $rows . " rows", Console::FG_GREEN);
        } catch (\Exception $e) {
            $this->stderr($e->getMessage(), Console::FG_RED);
        }
    }

    /**
     * 开始流程
     *
     * @param string $defineId
     * @CreateTime 18/3/28 11:33:20
     * @Author: fangxing@likingfit.com
     */
    public function actionStartFlow($defineId="OpenDirect")
    {
        list($flow, $process) = FlowService::startProcess($defineId, "test");
        $process->start();
    }

    /**
     * 完成流程
     * @param      $work_item_id
     * @param null $name
     * @param int  $value
     * @throws \ReflectionException
     * @throws \Throwable
     * @CreateTime 2018/4/12 13:57:47
     * @Author     : pb@likingfit.com
     */
    public function actionComplete($work_item_id, $name = null, $value = 0)
    {
        if ($name) {
            $workItem = FlowService::getWorkItem($work_item_id);
            $process = Workflow::getProcess($workItem->process_id);
            $process->setVariable($name, $value);
        }
        FlowService::completeWorkItem($work_item_id);
    }

    //-------------------流程测试自动化-------------------
    // 1. 生成流程测试配置
    // 2. 运行流程测试

    /**
     * 2. 流程程测试
     * @param $defId            `Main`
     * @param $testConfPath     `params/test.php`
     * @throws \Exception
     * @throws \Throwable
     * @CreateTime 2018/3/27 14:27:11
     * @Author     : pb@likingfit.com
     */
    public function actionFlowTest($defId, $testConfPath)
    {
        list($flow, $process) = FlowService::startProcess(
            $defId, "test", [
                'IsMainRejoin' => '0',
            ]
        );
        $process->start();
        $testConf = include_once \Yii::getAlias("@app") . "/{$testConfPath}";
        while (1) {
            $items = WorkItem::find()
                ->where(['series_id' => $flow->series_id, 'state' => '2'])
                ->all();
            if (!$items) {
                $this->stdout("流程测试完成", Console::FG_GREEN);
                return;
            }
            foreach ($items as $item) {
                if (isset($testConf[$item->activity_id])) {
                    $workItem = FlowService::getWorkItem($item->id);
                    $process = Workflow::getProcess($workItem->process_id);
                    foreach ($testConf[$item->activity_id]['attributes'] as $attribute => $value) {
                        $process->setVariable($attribute, (int)$value);
                    }
                }
                FlowService::completeWorkItem($item->id);
                $this->stdout($item->step_name . PHP_EOL, Console::FG_GREEN);
                if($item->step_name == "确认施工队已进场"){
                    die;
                }
            }
            usleep(500);
        }
    }

    /**
     * 1. 生成流程测试配置
     * @param $xmlPath          `resource/consortium`
     * @param $configFile       `params/consortium.php`
     * @CreateTime 2018/3/27 14:27:01
     * @Author     : pb@likingfit.com
     */
    public function actionMakeFlowConfig($xmlPath, $configFile)
    {
        $appPath = \Yii::getAlias("@app");
        $fullPath = $appPath . DIRECTORY_SEPARATOR . $xmlPath;
        $configFilePath = $appPath . DIRECTORY_SEPARATOR . $configFile;
        $dirHandle = opendir($fullPath);
        $config = [];
        while (($filename = readdir($dirHandle)) !== false) {
            if (strpos($filename, ".") === 0) {
                continue;
            }
            $file = $fullPath . DIRECTORY_SEPARATOR . $filename;

            $xml = simplexml_load_file($file);
            $activities = $this->getActivities($xml);
            $transitions = $this->getTransitions($xml);
            $this->setTransitionsCondition($xml, $transitions, $config);
            $this->setLoopsCondition($xml, $transitions, $config);
            foreach ($config as $k => $v) {
                if (isset($activities[$k])) {
                    $config[$k]['remark'] = $activities[$k]['displayName'];
                }
            }
        }
        $str = sprintf("<?php\r\nreturn %s;", var_export($config, 1));
        file_put_contents($configFilePath, $str);
    }

    public function getActivities($xml)
    {
        $params = [];
        $activities = $xml->children("fpdl", true)->Activities;
        foreach ($activities->children("fpdl", true) as $activity) {
            list($id, $name, $displayName) = $activity->attributes();
            $params[(string)$id] = [
                'id' => (string)$id,
                'name' => (string)$name,
                'displayName' => (string)$displayName,
            ];
        }

        return $params;
    }

    public function getTransitions($xml)
    {
        $params = [];
        $transitions = $xml->children("fpdl", true)->Transitions;
        foreach ($transitions->children("fpdl", true) as $transition) {
            list($id, $from, $to) = $transition->attributes();
            $params[(string)$to] = (string)$from;
        }

        return $params;
    }

    public function setTransitionsCondition($xml, $activities, &$config)
    {
        $transitions = $xml->children("fpdl", true)->Transitions;
        foreach ($transitions->children("fpdl", true) as $transition) {
            $condition = (string)$transition->children(
                "fpdl", true
            )->Condition;
            if ($condition === '') {
                continue;
            }
            list($id, $from) = $transition->attributes();
            $key = isset($activities[(string)$from])
                ? $activities[(string)$from] : (string)$from;
            list($attribute, $value) = explode('==', $condition);
            $config[$key]['attributes'][$attribute] = $value;
        }
    }

    public function setLoopsCondition($xml, $transitionsFrom, &$config)
    {
        $loops = $xml->children("fpdl", true)->Loops;
        if (!empty($loops)) {
            foreach ($loops->children("fpdl", true) as $loop) {
                $condition = (string)$loop->Condition;
                if ($condition === '') {
                    continue;
                }
                list($id, $from) = $loop->attributes();
                $key = isset($transitionsFrom[(string)$from])
                    ? $transitionsFrom[(string)$from] : (string)$from;
                list($attribute, $value) = explode('==', $condition);
                $config[$key]['attributes'][$attribute] = $value;
            }
        }
    }

    //------------------------------------------
}