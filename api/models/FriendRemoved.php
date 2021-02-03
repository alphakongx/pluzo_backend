<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "friend_removed".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $user_target_id
 * @property string|null $time
 */
class FriendRemoved extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'friend_removed';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'user_target_id'], 'safe'],
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
            'user_target_id' => 'User Target ID',
            'time' => 'Time',
        ];
    }
}
