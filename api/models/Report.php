<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "report".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $type
 * @property string|null $msg
 * @property int|null $reason
 * @property string|null $channel
 * @property string|null $time
 */
class Report extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'report';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'reason'], 'safe'],
            [['msg'], 'safe'],
            [['time'], 'safe'],
            [['channel'], 'safe'],
        ];
    }

    public function fields()
    {
        return [
            'id' => 'id',
            'type' => 'type', 
            'msg' => 'msg', 
            'reason' => 'reason', 
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'msg' => 'Msg',
            'reason' => 'Reason',
            'channel' => 'Channel',
            'time' => 'Time',
        ];
    }
}
