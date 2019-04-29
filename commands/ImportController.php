<?php
/**
 * Created by PhpStorm.
 * @Author: gaocanjie@likingfit.com
 * @CreateTime 2018/5/2 17:44:07
 */
namespace app\commands;

use app\models\Supplier;
use app\services\GoodsService;
use Box\Spout\Reader\ReaderFactory;
use yii\console\Controller;
use yii\httpclient\Client;


class ImportController extends Controller {

    const TYPE = array(
        '大器械' => 1,
        '小器械' => 2,
        '智能硬件' => 3,
        '监控设备' => 4,
        '定制物料' => 5,
        '装修施工物料' => 6,
        '门头' => 7,
        '运营物料' => 8,
    );
    const  TYPE_SUB = array(
        '有氧器械' => 1,
        '力量器械' => 2,
        '力量类'  => 3,
        '配件类' => 4,
        '电线材料' => 5,
        '瓷砖' => 6,
        '木地板' => 7,
        '卫浴' => 8,
        '涂料' => 9,
        '木饰面' => 10,
        '地胶' => 11,
        '灯具' => 12,
        '热水器' => 13,
        '苔藓墙' => 14
    );

    const WAREHOUSE = array(
        '实库' => 1,
        '虚库' => 2
    );

   const UPLOAD = IMAGE_DOMAIN."/upload/image";

    public function actionGoodsImport(){
        try{
            $filePath = __DIR__ . "/../excel/deck.xlsx";
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
                    $img =__DIR__."/../excel/deck/". $row[11];
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
                    GoodsService::addGoods($goods);
                }
            }
            $reader->close();

        }catch (\Exception $e){
            $this->stderr($e->getMessage()) ;
        }

    }
}