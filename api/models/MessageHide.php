<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "message_hide".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $time
 * @property int|null $chat_id
 */
class MessageHide extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message_hide';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'chat_id', 'time'], 'safe'],
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
            'time' => 'Time',
            'chat_id' => 'Chat ID',
        ];
    }
}
