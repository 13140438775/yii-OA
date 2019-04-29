<?php
/**
 * Created by PhpStorm.
 * User: mungbeansoup/zhouminjie@likingfit.com
 * Date: 17/3/3
 * Time: 上午10:34
 */

namespace app\services;

use app\models\Message;

class MessageService
{

    public static function readAll($staffId)
    {
        return Message::updateAll(['read_status' => Message::READ], ['staff_id' => $staffId, 'message_status' => AVAILABLE]);
    }

    public static function getStaffUnreadMessage($staffId)
    {
        $condition = [
            'staff_id' => $staffId,
            'message_status' => AVAILABLE,
            'read_status' => Message::UNREAD
        ];
        return Message::find()->where($condition)->all();
    }

    /**
     * 获取消息列表
     *
     * @param $param
     * @return array
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 16:55:12
     * @Author: fangxing@likingfit.com
     */
    public static function getList($param)
    {
        $additions = [
            "select" => [
                [
                    'id',
                    'title',
                    'content',
                    'param',
                    'message_type',
                    'create_time'
                ]
            ],
            "orderBy" => ["create_time desc"]
        ];
        $message = new Message($additions);
        return $message->getList($param);
    }

    /**
     * 置为已读或者生成消息
     *
     * @param $messageInfo
     * @throws \yii\base\InvalidConfigException
     * @CreateTime 18/3/23 17:01:02
     * @Author: fangxing@likingfit.com
     */
    public static function save($messageInfo)
    {
        if (!empty($messageInfo['id'])) {
            $message = Message::findOne(['id' => $messageInfo['id']]);
        } else {
            $message = new Message();
        }
        $message->setAttributes($messageInfo, false);
        $message->save();
        return;
    }

    public static function push($message)
    {
        $redis = \Yii::$app->redis;
        $data = [
            'module' => 'OA',
            'key' => $message['staff_id'],
            'data' => [
                'type' => 'message',
                'data' => [
                    'title' => $message['title'],
                    'content' => $message['content']
                ]
            ]
        ];
        $redis->publish('MESSAGE', json_encode($data));
    }
}