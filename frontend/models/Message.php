<?php

namespace frontend\models;

use Yii;
use common\models\Client;

/**
 * This is the model class for table "message".
 *
 * @property int $id
 * @property int $chat_id
 * @property int $user_id
 * @property int|null $status
 * @property string|null $text
 * @property string|null $image
 * @property string|null $created_at
 * @property string|null $type
 * @property string|null $channel_id
 */
class Message extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'message';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['chat_id', 'user_id'], 'safe'],
            [['chat_id', 'user_id', 'status'], 'safe'],
            [['text'], 'safe'],
            [['image', 'created_at', 'type', 'channel_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chat_id' => 'Chat ID',
            'user_id' => 'User ID',
            'status' => 'Status',
            'text' => 'Text',
            'image' => 'Image',
            'created_at' => 'Created At',
            'type' => 'Type',
            'channel_id' => 'Channel ID',
        ];
    }

    
    public static function getChat($user_id)
    {   
        $id = 0;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = 0) 
            AND `party`.`user_id` = ".$user_id);
        $result = $command->queryAll();
        if(isset($result[0]['chat_id'])){
            return $result[0]['chat_id'];
        } else {
            $chat = new Chat();
            $chat->user_id = 0;
            $chat->name = 'chat with '.$user_id;
            if ($chat->save()) {
                $chat_id = $chat->id;
                $party1 = new Party();
                $party1->user_id = 0;
                $party1->chat_id = $chat_id;
                $party1->save();
                $party2 = new Party();
                $party2->user_id = $user_id;
                $party2->chat_id = $chat_id;
                $party2->save();
            }
            return $chat_id;
        }
    }

    public function getUser()
    {   
        return $this->hasOne(Client::className(), ['id' => 'user_id']);
    }


    public static function addMessage($text, $chat_id)
    {
        $message = new Message();
        $message->chat_id = $chat_id;
        $message->user_id = 0;
        $message->status = 0;
        $message->type = 'message';
        $message->text = $text;
        $message->created_at = time();
        if($message->save()){

        } else {
            print_r($message->errors);
            echo 'Error save message'; die();
        }
        

        //$result = Chat::getMessagers($message->chat_id, $message->id);
        //User::socket($request->post('send_to'), (array)$result, 'Chat');
        //return $result;
    }
}
