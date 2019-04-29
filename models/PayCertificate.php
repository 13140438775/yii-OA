<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "t_pay_certificate".
 *
 * @property int $id
 * @property string $pay_task_id
 * @property string $certificate 凭证
 * @property int $create_time
 */
class PayCertificate extends Base
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 't_pay_certificate';
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'pay_task_id' => 'Pay Task ID',
            'certificate' => 'Certificate',
            'create_time' => 'Create Time',
        ];
    }
}
