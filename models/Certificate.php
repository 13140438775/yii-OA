<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_certificate".
 *
 * @property int $id
 * @property int $series_id
 * @property int $object_id 1 pay_task_id 2 rent_contract_id 3 order_id
 * @property string $certificate 凭证
 * @property int $type 1 付款单凭证 2房租凭证 3到货凭证
 */
class Certificate extends Base
{
    public static $RentFeeType = 1;

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_certificate';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'series_id' => 'Series ID',
            'object_id' => 'Object ID',
            'certificate' => 'Certificate',
            'type' => 'Type',
        ];
    }
}
