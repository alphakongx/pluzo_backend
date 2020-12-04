<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "last_seen".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $chat
 * @property string|null $time
 */
class LastSeen extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'last_seen';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'chat'], 'integer'],
            [['time'], 'safe'],
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
            'chat' => 'Chat',
            'time' => 'Time',
        ];
    }
}
