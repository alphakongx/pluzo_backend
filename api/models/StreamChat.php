<?php

namespace api\models;

use Yii;
use api\models\User;

/**
 * This is the model class for table "stream_chat".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $created_at
 * @property string|null $text
 * @property string|null $channel_id
 */
class StreamChat extends \yii\db\ActiveRecord
{   
    const USER_MESSAGE = 0;
    const SYSTEM_MESSAGE = 1;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream_chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['text'], 'safe'],
            [['created_at', 'channel_id'], 'safe'],
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
            'created_at' => 'Created At',
            'text' => 'Text',
            'channel_id' => 'Channel ID',
        ];
    }

    public static function addMsg($message, $channel_id, $type)
    {   
        $msg = new StreamChat();
        $msg->user_id = \Yii::$app->user->id;
        $msg->created_at = time();
        $msg->text = $message;
        $msg->channel_id = $channel_id;
        $msg->type = $type;
        if ($msg->save()) {
            $result = [
                'user'=>Stream::userForApi(\Yii::$app->user->id),
                'message'=>$message,
                'type'=>$type,
                'stream'=>$channel_id,
            ];
            User::socket(0, $result, 'Stream_new_message');  
            return $msg;
        } else {
            throw new \yii\web\HttpException('500','Error save message.'); 
        }
    }

    public static function getMsg($request)
    {   
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        return StreamChat::find()->where(['channel_id'=>$request->post('channel_id')])->orderBy(['created_at'=>SORT_DESC])->all();
    }

    public function fields()
    {
        return [
            'id' => 'id',
            'user_id' => 'user',
            'created_at' => 'created_at',
            'message' => 'text',
            'type' => 'type',
            'channel_id' => 'channel_id',                                    
        ];
    }

    public function getUser()
    {   
        return $this->hasOne(UserMsg::className(), ['id' => 'user_id']);        
    }
}
