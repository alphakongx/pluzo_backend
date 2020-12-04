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
    const __USER_AUDIENCE__ = 0;
    const __USER_BROAD__ = 1;
    const __USER_NOT_HOST__ = 0;
    const __USER_HOST__ = 1;

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
            [['user_id'], 'safe'],
            [['user_id'], 'safe'],
            [['channel'], 'safe'],
        ];
    }

    public function checkUserInStream($user_id, $channel)
    {   
        $check = StreamUser::find()->where(['user_id'=>$user_id,'channel'=>$channel,])->one();
        if (!$check) {
            throw new \yii\web\HttpException('500','User not joined to this channel');
        }
    }

    public static function addUser($user_id, $channel, $type, $host)
    {   
        $check = StreamUser::find()->where(['user_id'=>$user_id,'channel'=>$channel,])->one();
        if ($check) {
            throw new \yii\web\HttpException('500','User already joined to this channel');
        }
        $add = new StreamUser();
        $add->user_id = $user_id;
        $add->channel = $channel;
        $add->type = $type;
        $add->host = $host;
        $add->save();
    }

    public static function deleteUser($user_id, $channel)
    {   
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['user_id'=>$user_id,'channel'=>$channel])
            ->execute();
    }

    public static function deleteAllUser($channel)
    {   
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['channel'=>$channel])
            ->execute();
    }

    public static function changeUser($user_id, $channel, $type)
    {   
        $check = StreamUser::find()->where(['user_id'=>$user_id,'channel'=>$channel])->one();
        if (!$check) {
            throw new \yii\web\HttpException('500','User not joined to this channel');
        }
        $check->type = $type;
        $check->save();
        return $check;
    }
    

    public function fields()
    {
        return [
            'id' => 'id',
            'user_id' => 'user_id',
            'channel_id' => 'channel',
            'type' => 'type',
            'host' => 'host',
        ];
    }
}
