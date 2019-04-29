<?php

namespace app\controllers;

use app\services\PurchaseService;
use Yii;
use yii\web\Controller;

class PdfController extends Controller
{
    public $layout = false;

    public function actionPurchase(){
        $purchase_id = \Yii::$app->request->get("purchase_id");
        $purchase = PurchaseService::getPurchaseInfo($purchase_id);
        $mpdf = new \Mpdf\Mpdf(['tempDir' => Yii::$app->getBasePath() . '/web/tmp']);
        $mpdf->useAdobeCJK = true;
        $mpdf->autoScriptToLang = true;
        $mpdf->autoLangToFont = true;
        $mpdf->WriteHTML($this->render('purchase', ['purchase'=>$purchase]));
        $mpdf->Output('上海真快信息技术有限公司采购订单.pdf', 'D');
    }
}
