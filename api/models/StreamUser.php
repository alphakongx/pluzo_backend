<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "stream_user".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $channel
 */
class StreamUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'required'],
            [['user_id'], 'integer'],
            [['channel'], 'string', 'max' => 255],
        ];
    }

    public function checkUserInStream($user_id, $channel)
    {   
        $check = StreamUser::find()->where(['user_id'=>$user_id,'channel'=>$channel,])->one();
        if (!$check) {
            throw new \yii\web\HttpException('500','User not joined to this channel');
        }
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
        ];
    }
}
