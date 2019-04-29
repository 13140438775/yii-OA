<?php

namespace app\models;

use Yii;
use yii\base\InvalidParamException;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;
use yii\data\Pagination;
use yii\base\InvalidCallException;
use yii\db\ActiveRecordInterface;
use yii\helpers\ArrayHelper;


class Base extends ActiveRecord
{
    /**
     * 每页数目
     * @var int
     */
    public static $pageNum = 10;

    public static $available = 1;

    public static $unAvailable = 0;

    /**
     * @var $query ActiveQuery
     */
    protected $query;

    protected $additions = [];

    /**
     * Base constructor.
     * @param array $additions
     * @param array $config
     * @throws \yii\base\InvalidConfigException
     */
    public function __construct($additions = [], $config = [])
    {
        parent::__construct($config);
        $this->additions = $additions;
        $this->query     = $this->newQuery();
    }

    /**
     * @param array $additions
     * @return $this
     */
    public function setAdditions(array $additions = [])
    {
        $additions = array_merge($this->additions, $additions);
        foreach ($additions as $key => $v) {
            if (method_exists($this->query, $key)) {
                call_user_func_array([$this->query, $key], $v);
            } else {
                throw new InvalidCallException("invalid method call");
            }
        }
        return $this;
    }

    /**
     * @return ActiveQuery
     */
    public function getQuery()
    {

        return $this->query;
    }

    /**
     * @param string $q
     * @param null   $db
     * @return int|string
     * @CreateTime 18/3/14 11:47:31
     * @Author     : fangxing@likingfit.com
     */
    public function count($q = "*", $db = null)
    {

        return $this->getQuery()->count($q, $db);
    }

    /**
     * @return object
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/8 14:51:41
     * @Author     : fangxing@likingfit.com
     */
    public function newQuery()
    {
        return Yii::createObject(ActiveQuery::class, [get_called_class()]);
    }

    /**
     * @param array $conditions
     * @param array $join
     * @param bool  $return
     * @return $this|array|ActiveRecord[]
     * @CreateTime 18/3/8 14:53:07
     * @Author     : fangxing@likingfit.com
     */
    public function getList($conditions = [], $join = [], $return = false)
    {
        $join = array_replace([null, true, "LEFT JOIN"], $join);
        list($with, $eagerLoading, $joinType) = $join;
        $this->setAdditions()
            ->getQuery()
            ->joinWith($with, $eagerLoading, $joinType)
            ->filterWhere($conditions);
        if ($return) {
            return $this;
        }
        return $this->getQuery()->asArray()->all();
    }

    /**
     * 分页函数
     *
     * @param int   $page
     * @param int   $pageSize
     * @param array $join
     * @param array $conditions
     * @return array
     * @CreateTime 18/3/8 14:53:12
     * @Author     : fangxing@likingfit.com
     */
    public function paginate($page = 1, $pageSize = 10, $join = [], $conditions = [])
    {
        $this->setAdditions();
        $query = $this->getQuery();
        list($with, $eagerLoading, $joinType) = array_replace([null, true, "LEFT JOIN"], $join);
        $count = $query->joinWith($with, $eagerLoading, $joinType)
            ->filterWhere($conditions)
            ->count();
        $rows  = $query->limit($pageSize)
            ->offset(($page - 1) * $pageSize)
            ->asArray()
            ->all();
        return [
            'total' => $count,
            'rows'  => $rows
        ];
    }

    /**
     * @param array $conditions
     * @param array $join
     * @param bool  $return
     * @return $this|array|null|ActiveRecord
     * @CreateTime 18/3/6 18:37:47
     * @Author     : fangxing@likingfit.com
     */
    public function getOneRecord($conditions = [], $join = [], $return = false)
    {
        $this->setAdditions()
            ->getQuery()
            ->with($join)
            ->filterWhere($conditions);
        if ($return) {
            return $this;
        }
        return $this->getQuery()->asArray()->one();
    }

    /**
     * @param $data
     * @return int
     * @throws \yii\db\Exception
     * @CreateTime 18/3/10 14:52:14
     * @Author     : fangxing@likingfit.com
     */
    public function batchInsert($data)
    {
        if (empty($data)) {
            return 0;
        }
        $field = array_keys(reset($data));
        return static::find()->createCommand()
            ->batchInsert(static::tableName(), $field, $data)
            ->execute();
    }

    /**
     * 数据字典转换
     *
     * @param       $results
     * @param array $labels
     * @CreateTime 18/3/16 13:14:48
     * @Author     : fangxing@likingfit.com
     */
    public static function convert2string(&$results, $labels = [])
    {
        $params = \Yii::$app->params;
        if (is_string($labels)) {
            $labels = (array)$labels;
        }
        foreach ($results as &$result) {
            foreach ($labels as $key => $label) {
                $keyVal = ArrayHelper::getValue($result, $key);
                if(is_callable($label)){
                    $label_name = call_user_func($label, $keyVal);
                }else{
                    $parts = explode(".", $label);
                    $arr = [];
                    while ($p = array_shift($parts)){
                        array_push($arr, $p);
                        if(array_key_exists($p, $result)){
                            array_push($arr, $result[$p]);
                        }
                    }
                    $label_name = ArrayHelper::getValue($params, $arr);
                }
                ArrayHelper::setValue($result, "{$key}_label", $label_name);
            }
        }
    }

    /**
     * 获取类型文案
     * @param $typeTexts
     * @param $val
     * @return string
     * @CreateTime 2018/3/19 17:27:31
     * @Author     : pb@likingfit.com
     */
    public static function getTypeText($typeTexts, $val)
    {
        return isset($typeTexts[$val]) ? $typeTexts[$val] : '';
    }
}
