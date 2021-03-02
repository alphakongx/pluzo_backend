<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "stream".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $channel
 * @property string|null $created_at
 * @property string|null $category
 * @property string|null $name
 * @property string|null $invite_only
 * @property int|null $stop
 */
class Stream extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id', 'stop'], 'integer'],
            [['channel', 'created_at', 'category', 'name', 'invite_only'], 'string', 'max' => 255],
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
            'channel' => 'Channel',
            'created_at' => 'Created At',
            'category' => 'Category',
            'name' => 'Name',
            'invite_only' => 'Invite Only',
            'stop' => 'Stop',
        ];
    }
}
