<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "party".
 *
 * @property int $id
 * @property int $user_id
 * @property int $chat_id
 */
class Party extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'party';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'chat_id'], 'required'],
            [['user_id', 'chat_id'], 'integer'],
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
            'chat_id' => 'Chat ID',
        ];
    }
}
