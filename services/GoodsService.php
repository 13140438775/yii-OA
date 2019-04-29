<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/2/27 16:53:22
 */

namespace app\services;

use app\exceptions\Param;
use app\helpers\Helper;
use app\helpers\Lock;
use app\models\Goods;
use app\models\GoodsImg;
use app\models\GoodsSupplier;
use app\models\Supplier;
use app\models\Type;
use app\models\Warehouse;
use Box\Spout\Reader\ReaderFactory;
use yii\helpers\ArrayHelper;
use yii\httpclient\Client;

class GoodsService
{

    const TYPE = array(
        '大器械' => 1,
        '小器械' => 2,
        '智能控制系统' => 3,
        '监控设备' => 4,
        '定制增值商品' => 5,
        '生活物料' => 6,
    );
    const  TYPE_SUB = array(
      '有氧器械' => 1,
        '力量器械' => 2,
    );
    const GOODS_TYPE = array(
        '淘宝' => 1,
        '京东' => 2,
    );
    const WAREHOUSE = array(
        '实库' => 1,
        '虚库' => 2
    );
    const   PAGE = 1;
    const   PAGESIZE = 15;

    //const UPLOAD = "http://local.upload.com/upload/image";
    const UPLOAD = IMAGE_DOMAIN."/upload/image";

    public function readExcel()
    {
        var_dump(self::UPLOAD);die;
        $filePath = __DIR__ . "/../excel/test.xlsx";
        $reader = ReaderFactory::create('xlsx');
        $reader->open($filePath);
        $goods = array();
        foreach ($reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $k => $row) {
                if ($k == 1) {
                    continue;
                }
                //供应商不为空时
                if (!empty($row[4])){
                   $supplier = Supplier::find()->select(['id','supplier_name'])->where(['supplier_name'=>$row[4]])->asArray()->one();
                }
                $img =__DIR__."/../excel/picture/". $row[11];
                $client = new Client();
                $response = $client->createRequest()
                    ->setFormat(Client::FORMAT_JSON)
                    ->setMethod('post')
                    ->setUrl(self::UPLOAD)
                    ->setData(['img'=>base64_encode(file_get_contents($img))])
                    ->send();
                if ($response->isOk) {
                    $imgData  = $response->getData();
                    if ($imgData['err_code'] == 0) {
                        $imgUrl = $imgData['data']['url'];
                    }
                }
                else{
                    return 'IMG FAIL';
                }
                if (!empty($row[2])){
                    $subId = self::TYPE_SUB[$row[2]];
                    $subName = $row[2];
                }else{
                    $subId = 0;
                    $subName = '';
                }

                $goods = array(
                    'goods_type' => self::WAREHOUSE[$row[0]],
                    'type_id' => self::TYPE[$row[1]],
                    'type_name' => $row[1],
                    'sub_id' => $subId,
                    'sub_name' => $subName,
                    'goods_name' => $row[3],
                    'suppliers' =>[ [
                        'supplier_id' => $supplier['id']
                    ]],
                    'model' => $row[5],
                    'brand' => $row[6],
                    'unit' => $row[7],
                    'weight' => $row[8],
                    'param' => $row[9],
                    'description' => $row[10],
                    'img' =>[[
                        'img_url' => $imgUrl
                    ]]  ,
                    'purchase_amount' => $row[12],
                    'price' => $row[13],
                    'min_sell' => $row[14],
                    'warn' => $row[15]
                );
                self::addGoods($goods);
            }
        }
        $reader->close();
    }

    public static function checkAddRequest($param)
    {
    }

    public static function createGoodsId($typeId)
    {
        $directory = "goods/".date('Ymd');
        $fileName = 'app_add_goods';
        $lock = new Lock($fileName,$directory);
        $lock->lock();
        $goods = Goods::find()->where(['type_id'=>$typeId])->orderBy(['id'=>SORT_DESC])->limit(1)->asArray()->one();
        if (empty($goods)){
            $goodsId = sprintf("%02d", $typeId).'.0001';
        }else{
            $left = substr($goods['goods_id'],0,strpos($goods['goods_id'],'.'));
            $right = substr($goods['goods_id'],strpos($goods['goods_id'],'.')+1)+1;
            $right = sprintf("%04d", $right);
            $goodsId = $left.'.'.$right;
        }
        $lock->unlock();
        return $goodsId;



    }

    /**
     * @param $params
     * @throws \app\exceptions\Goods
     * @CreateTime ${DATE} ${HOUR}:${MINUTE}:${SECOND}
     * @Author: ${USER}@likingfit.com
     */
    public static function addGoods($params)
    {
        try {
            $transaction = \Yii::$app->db->beginTransaction();
            $goodsId = empty($params['goods_id']) ? self::createGoodsId($params['type_id']) : $params['goods_id'];
            self::saveGoods($params, $goodsId);
            self::insertGoodsImg($goodsId, $params['img']);
            self::insertGoodsSupplier($goodsId, $params['suppliers']);
            $transaction->commit();
        } catch (\Exception $e) {
            throw $e;
            throw new \app\exceptions\Goods(21001);
            $transaction->rollBack();
        }
    }

    public static function saveGoods($params, $goodsId)
    {
        $goodsModel = Goods::getOne($goodsId);
        if (empty($goodsModel)) {
            $goodsModel = new Goods();
            $goodsModel->goods_id = $goodsId;
            //生成商品的同时往对应类型仓库里面添加记录
            if($params['goods_type'] == Warehouse::REAL_LIBRARY){
                $conditions = ['warehouse_type'=>Warehouse::REAL_LIBRARY];
            }else{
                $conditions = ['warehouse_type'=>Warehouse::VIRTUAL_LIBRARY];
            }
            $warehouseList = Warehouse::find()->where($conditions)->asArray()->all();
            if(!empty($warehouseList)){
                WarehouseService::insertGoodWarehouse($goodsId,$warehouseList);
            }
        }
        $goodsModel->goods_name = $params['goods_name'];
        $goodsModel->goods_type = $params['goods_type'];
        $goodsModel->type_id = $params['type_id'];
        $goodsModel->type_name = $params['type_name'];
        $goodsModel->sub_id = ArrayHelper::getValue($params, 'sub_id',0);
        $goodsModel->sub_name = ArrayHelper::getValue($params, 'sub_name', '');
        $goodsModel->model = $params['model'];
        $goodsModel->weight = $params['weight'];
        $goodsModel->param = $params['param'];
        $goodsModel->description = $params['description'];
        $goodsModel->purchase_amount = bcmul($params['purchase_amount'],100);
        $goodsModel->price =  bcmul($params['price'],100);
        $goodsModel->min_sell = $params['min_sell'];
        $goodsModel->unit = $params['unit'];
        $goodsModel->brand = $params['brand'];
        $goodsModel->warn = $params['warn'];
        $goodsModel->create_time = time();
        $goodsModel->update_time = time();
        $goodsModel->save();


    }


    public static function insertGoodsImg($goodsId, $imgs)
    {
        GoodsImg::unavailable($goodsId);
        foreach ($imgs as $img) {
            $info[] = array(
                'goods_id' => $goodsId,
                'goods_img' => $img['img_url'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        $insert = GoodsImg::getDb()
            ->createCommand()
            ->batchInsert(GoodsImg::tableName(), $columns, $info)
            ->execute();

    }

    public static function insertGoodsSupplier($goodsId, $suppliers)
    {
        GoodsSupplier::unavailable($goodsId);
        foreach ($suppliers as $supplier) {
            $info[] = array(
                'goods_id' => $goodsId,
                'supplier_id' => $supplier['supplier_id'],
                'create_time' => time(),
                'update_time' => time()
            );
        }
        $columns = array_keys(reset($info));
        $insert = GoodsSupplier::getDb()
            ->createCommand()
            ->batchInsert(GoodsSupplier::tableName(), $columns, $info)
            ->execute();

    }


    public static function getGoodsList($param,$export=false)
    {
        $condition = ['AND'];
        foreach ($param as $k => $value) {
            if (empty($value) || $k == 'page' || $k == 'page_size') {
                continue;
            }
            if ($k != 'goods_name' && $k != 'goods_id' && $k != 'un_goods') {
                $condition[] = array(
                    "$k" => $value
                );
            } elseif ($k == 'un_goods') {
                $condition[] = ['NOT',['t_goods.goods_id'=>$value]];
            } else {
                $condition[] = ['LIKE', $k, $value];
            }
        }
        $page = ArrayHelper::getValue($param, 'page', self::PAGE);
        $pageSize = ArrayHelper::getValue($param, 'page_size', self::PAGESIZE);
        return Goods::goodsList($condition, $page, $pageSize,$export);
    }

    public static function byTypeSupplier($typeId)
    {
        $fields = ['id', 'type_name'];
        $condition = array(
            'id' => $typeId
        );
        $result = Type::find()->select($fields)->with('supplier')->where($condition)->asArray()->all();
        return $result[0]['supplier'];
    }

    public static function getGoodsDetail($goodsId)
    {
        $condition = ['t_goods.goods_id' => $goodsId];
        return Goods::goodsList($condition);
    }




}