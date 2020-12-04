<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "stream_ban_user".
 *
 * @property int $id
 * @property int $user_source_id
 * @property int $user_target_id
 * @property string|null $reason
 * @property string|null $stream_id
 * @property string|null $time
 */
class StreamBanUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream_ban_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_source_id', 'user_target_id'], 'required'],
            [['user_source_id', 'user_target_id'], 'integer'],
            [['reason', 'stream_id', 'time'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_source_id' => 'User Source ID',
            'user_target_id' => 'User Target ID',
            'reason' => 'Reason',
            'stream_id' => 'Stream ID',
            'time' => 'Time',
        ];
    }
}
