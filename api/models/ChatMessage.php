<?php

namespace api\models;

use Yii;
use api\models\Chat;
use api\models\Party;
use api\models\Message;
use api\models\SearchUserPpl;
use api\models\MessageHide;
use yii\helpers\ArrayHelper;

/**
 * This is the model class for table "chat".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $user_id
 */
class ChatMessage extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'party';
    }


    public static function getCurrentChat($request)
    {  
        if(!$request->post('chat_id')){
            throw new \yii\web\HttpException('500','chat_id cannot be blank.'); 
        }
        $chats = Party::find()->where(['chat_id'=>$request->post('chat_id'), 'user_id'=>\Yii::$app->user->id])->one();
        if(!$chats){
            throw new \yii\web\HttpException('500','You not have this chat_id'); 
        }
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("UPDATE `message` SET `status`=1 WHERE `chat_id`=".$request->post('chat_id')." AND `user_id`<>".\Yii::$app->user->id);
        $result = $command->execute(); 
            $party = Party::find()->where(['chat_id'=>$request->post('chat_id')])->andWhere(['<>','user_id', \Yii::$app->user->id])->one();
            if (in_array($party->user_id, User::bannedUsers())) {
                throw new \yii\web\HttpException('500','User from this chat_id was banned you');
            }
            if (in_array($party->user_id, User::whoBannedMe())) {
                throw new \yii\web\HttpException('500','User from this chat_id has banned you'); 
            }

        $result = ChatMessage::find()->select('chat_id')->where(['chat_id'=>$request->post('chat_id')])->one();
        return $result;
    }

    public static function getChatMessage($id)
    {   
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = ".\Yii::$app->user->id.")
            AND `party`.`user_id` <> ".\Yii::$app->user->id);
        $result = $command->queryAll();

        $banned = [];
        $us1 = User::bannedUsers();
        $us2 = User::whoBannedMe();
        foreach ($result as $key => $value) {
            if (in_array($value['user_id'], $us1)) {
                continue;
            }
            if (in_array($value['user_id'], $us2)) {
                continue;
            }
            array_push($banned, $value['chat_id']);
        }
        $result = ChatMessage::find()
        ->select('chat_id')
        ->where(['user_id'=>$id])
        ->andWhere(['in', 'chat_id', $banned])
        ->distinct()
        ->all();
        return $result;
    }

    public static function getRecentlyMessaged($id)
    {   
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = ".$id.") AND `party`.`user_id` <> 0 AND `party`.`user_id` <> ".$id);
        $result = $command->queryAll();
        $users = [];
        foreach ($result as $key => $value) {
            array_push($users, $value['user_id']);
        }
        $users = array_unique($users);
        return SearchUserPpl::find()
        ->where(['in', 'id', $users])
        ->andwhere(['status'=>1])
        //->orderBy(['created_at' => SORT_DESC])
        ->limit(20)
        ->all();
    }
    

    public function fields()
    {
        return [
            'chat_id' => 'chat_id',
            'messages' => 'message_tbl',
            'partner_info' => 'partner_info',
           
        ];
    }

    public function getPartner_info()
    {   
        $party = Party::find()->where(['chat_id'=>$this->chat_id])->andWhere(['<>','user_id', \Yii::$app->user->id])->one();
        if($party->user_id == 0){
            return 'Pluzo Team';
        }
        return UserMsg::find()->where(['id'=>$party->user_id])->one();
    }

    public function getMessage_tbl()
    {   
        $party = Party::find()->where(['chat_id'=>$this->chat_id])->andWhere(['<>','user_id', \Yii::$app->user->id])->one();
        $limit = 10000;
        if (Yii::$app->request->post('limit')) {
            $limit = Yii::$app->request->post('limit');
        }
        if($party->user_id == 0){

                $hideMsg = MessageHide::find()->where(['chat_id'=>$this->chat_id, 'user_id'=>\Yii::$app->user->id])->one();
                if($hideMsg){
                    $hidetime = $hideMsg->time;
                } else {
                    $hidetime = 0;
                }
            return $this->hasMany(Message::className(), ['chat_id' => 'chat_id'])->andWhere(['>', 'created_at', $hidetime])->orderBy(['created_at'=>SORT_DESC])->limit($limit); 
        } else {
            return $this->hasMany(Message::className(), ['chat_id' => 'chat_id'])->orderBy(['created_at'=>SORT_DESC])->limit($limit); 
        }
    }

}