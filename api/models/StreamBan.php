<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "stream_ban".
 *
 * @property int $id
 * @property int $user_id
 * @property string $channel_id
 * @property string|null $created_at
 */
class StreamBan extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream_ban';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'channel_id'], 'required'],
            [['user_id'], 'safe'],
            [['channel_id', 'created_at'], 'safe'],
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
            'channel_id' => 'Channel ID',
            'created_at' => 'Created At',
        ];
    }
}
