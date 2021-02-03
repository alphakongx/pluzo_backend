<?php

namespace api\models;

use Yii;
use api\models\Chat;
use api\models\Stream;
use api\models\Party;
use api\models\Message;
use api\models\UserMsg;
use api\models\ChatMessage;
use api\models\MessageHide;
use api\models\Badge;
use api\components\PushHelper;

use yii\helpers\ArrayHelper;
/**
 * This is the model class for table "chat".
 *
 * @property int $id
 * @property string|null $name
 * @property string|null $user_id
 */
class Chat extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'chat';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'string', 'max' => 255],
            [['user_id'], 'integer'],
        ];
    }

    
    public static function openChat($request)
    {
        $chat_id = $request->post('chat_id');
        if(!$chat_id){
            throw new \yii\web\HttpException('500','chat_id cannot be blank.'); 
        }
        $party = Party::find()->where(['chat_id'=>$chat_id])->andWhere(['<>','user_id', \Yii::$app->user->id])->one();

        $socket_result = [
                'user'=>\Yii::$app->user->id,
                'chat_id'=>$chat_id,
                'time'=>time(),
            ];

        User::socket($party->user_id, $socket_result, 'Open_chat');
        return $socket_result;

    }

    public static function closeChat($request)
    {
        $chat_id = $request->post('chat_id');
        if(!$chat_id){
            throw new \yii\web\HttpException('500','chat_id cannot be blank.'); 
        }
        $party = Party::find()->where(['chat_id'=>$chat_id])->andWhere(['<>','user_id', \Yii::$app->user->id])->one();

        $socket_result = [
                'user'=>\Yii::$app->user->id,
                'chat_id'=>$chat_id,
                'time'=>time(),
            ];
        User::socket($party->user_id, $socket_result, 'Close_chat');
        return $socket_result;

    }

    public static function getChatUser($user_id)
    {   
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = ".\Yii::$app->user->id.") 
            AND `party`.`user_id` = ".$user_id);
        $result = $command->queryAll();
        return [
            'chat_id' => $result[0]['chat_id']
        ];
    }

    //return all chats for auth user
    public static function getChat()
    {   
        $result = Party::find()->select('chat_id')->where(['user_id'=>\Yii::$app->user->id])->distinct()->all();
        return $result;
    }

    public function getSearch($request)
    {   
        $my_chats = Party::find()->select('chat_id')->where(['user_id'=>\Yii::$app->user->id])->distinct()->all();
        $array = [];
        foreach ($my_chats as $key => $value) {
            array_push($array, $value['chat_id']);
        }
        $array = implode("','",$array);
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT ".User::userFields().", `message`.`text`, `message`.`type`, `message`.`chat_id`, `message`.`created_at` FROM `message`
            LEFT JOIN `client` ON `client`.`id` = `message`.`user_id`
            WHERE `message`.`text` Like '%".$request."%' AND `message`.`chat_id` IN ('".$array."')");
        $result = $command->queryAll();



        $chat_with_team = self::getChatUser(0);
        if($chat_with_team['chat_id']){
            $hideMsg = MessageHide::find()->where(['chat_id'=>$chat_with_team['chat_id'], 'user_id'=>\Yii::$app->user->id])->one();
            if($hideMsg){
                $hidetime = $hideMsg->time;
            } else {
                $hidetime = 0;
            }
        } else {
            $hidetime = 0;
        }

        $final = [];
        $us1 = User::bannedUsers();
        $us2 = User::whoBannedMe();
        foreach ($result as $key => $value) {

            //banned users
            if (in_array($value['id'], $us1)) {
                continue;
            }
            if (in_array($value['id'], $us2)) {
                continue;
            }

            $partner_id = Party::find()
            ->where(['chat_id'=>$value['chat_id']])
            ->andwhere(['<>', 'user_id', $value['id']])
            ->one();

            if($partner_id->user_id == 0){
                $partner_model = [
                    'id' => 0,
                    'username' => 'Pluzo Team',
                    'text' => $value['text'],
                    'type' => $value['type'],
                    'chat_id' => $value['chat_id'],
                    'created_at' => $value['created_at'],
                    ];
            } else {
                $partner_model = Stream::userForApi($partner_id->user_id);
            }

            if ($value['id']) {
                $ar = [
                    'id' => $value['id'],
                    'username' => $value['username'],
                    'phone' => $value['phone'],
                    'image' => $value['image'],
                    'gender' => $value['gender'],
                    'birthday' => $value['birthday'],
                    'age'=>User::getAge($value['birthday']),
                    'status' => $value['status'],
                    'first_name' => $value['first_name'],
                    'last_name' => $value['last_name'],
                    'latitude' => $value['latitude'],
                    'longitude' => $value['longitude'],
                    'address' => $value['address'],
                    'city' => $value['city'],
                    'state' => $value['state'],
                    'last_activity' => $value['last_activity'],
                    'premium' => User::checkPremium($value['id']),
                    'first_login' => $value['first_login'],
                    'text' => $value['text'],
                    'type' => $value['type'],
                    'chat_id' => $value['chat_id'],
                    'created_at' => $value['created_at'],
                    'badges'=>Badge::getBadge($value['id']),
                    'friends'=>User::friendCount($value['id']),
                    'partner_model' => $partner_model,
                ];
                array_push($final, $ar);
            } else {
                if ($value['created_at'] > $hidetime) {
                    $ar = [
                        'id' => 0,
                        'username' => 'Pluzo Team',
                        'text' => $value['text'],
                        'type' => $value['type'],
                        'chat_id' => $value['chat_id'],
                        'created_at' => $value['created_at'],
                        'partner_model' => Stream::userForApi(\Yii::$app->user->id),

                    ];
                    array_push($final, $ar);
                }
                
            }   
            
        }
        return $final;
    }

    //create new message
    public static function addMessage($request)
    {   
        $send_to = $request->post('send_to');
        if(!isset($send_to)){
            throw new \yii\web\HttpException('500','send_to cannot be blank.'); 
        }
        $chat_id = (int)$request->post('chat_id');

        if (in_array($send_to, User::bannedUsers())) { 
            throw new \yii\web\HttpException('500','You cant send message to banned user'); 
        }
        if (in_array($send_to, User::whoBannedMe())) {
            throw new \yii\web\HttpException('500','You cant send message to user who banned you');
        }

        $send_push = $request->post('send_push');
        if (!isset($send_push)) { $send_push = 1; }

        $chat_id_with_user = self::getChatUser($send_to);
        if($chat_id_with_user['chat_id']){
            if ($chat_id != $chat_id_with_user['chat_id']) {
                $chat_id = $chat_id_with_user['chat_id'];
            }
        }
        if (!$chat_id) {
            $chat = new Chat();
            $chat->user_id = \Yii::$app->user->id;
            if ($chat->save()) {
                $chat_id = $chat->id;
                $party1 = new Party();
                $party1->user_id = \Yii::$app->user->id;
                $party1->chat_id = $chat_id;
                $party1->save();
                $party2 = new Party();
                $party2->user_id = $request->post('send_to');
                $party2->chat_id = $chat_id;
                $party2->save();
            }
        } else {
            $chat_exist = Chat::find()->where(['id'=>$chat_id])->one();
            if(!$chat_exist){
                throw new \yii\web\HttpException('500','You are trying to send message to chat_id '.$chat_id.' but chat with this ID not exist');
            }
            $checkParty1 = Party::find()->where(['chat_id'=>$chat_id, 'user_id'=>\Yii::$app->user->id])->one();
            if (!$checkParty1) {
                $party1 = new Party();
                $party1->user_id = \Yii::$app->user->id;
                $party1->chat_id = $chat_id;
                $party1->save();
            }
            $checkParty2 = Party::find()->where(['chat_id'=>$chat_id, 'user_id'=>$request->post('send_to')])->one();
            if (!$checkParty2) {
                $party2 = new Party();
                $party2->user_id = $request->post('send_to');
                $party2->chat_id = $chat_id;
                $party2->save();
            }
        }
        $message = new Message();
        $message->chat_id = $chat_id;
        $message->user_id = \Yii::$app->user->id;
        $message->status = 0;
        $message->type = 'message';
        $message->text = $request->post('text');
        $message->created_at = time();

        //image
        if( count($_FILES)>0 AND $_FILES['image']['tmp_name'] ) {
            $file_name = uniqid().'.jpg';   
            $temp_file_location = $_FILES['image']['tmp_name']; 
            User::s3Upload('chat/', $file_name, $temp_file_location);
            $message->image = env('AWS_S3_PLUZO').'chat/'.$file_name;
        }

        //check read message and send PN
        $check =  Message::find()->where(['chat_id'=>$chat_id, 'type'=>Message::__MESSAGE_, 'status'=>0, 'user_id'=>\Yii::$app->user->id])->count();
        if($check == 0 AND $send_push == 1){
            $send_to = $request->post('send_to');
            $host = User::find()->where(['id'=>\Yii::$app->user->id])->one();
            $user = User::find()->where(['id'=>$send_to])->one();
            $text_message = $host->first_name.' sent you message';
            $data = array("action" => "chat", 'user_model'=>Stream::userForApi($host), 'chat_id'=>$chat_id); 
            PushHelper::send_push($user, $text_message, $data);
        }


        $message->save();


        $result = Chat::getMessagers($message->chat_id, $message->id);
        User::socket($request->post('send_to'), (array)$result, 'Chat');
        return $result;
    }

    //register msg
    public static function signupMsg($user_id)
    {  
        $chat = new Chat();
        $chat->user_id = $user_id;
        if ($chat->save()) {
            $chat_id = $chat->id;
            $party1 = new Party();
            $party1->user_id = $user_id;
            $party1->chat_id = $chat_id;
            $party1->save();
            $party2 = new Party();
            $party2->user_id = 0;
            $party2->chat_id = $chat_id;
            $party2->save();

            $message = new Message();
            $message->chat_id = $chat_id;
            $message->user_id = 0;
            $message->status = 0;
            $message->type = 'message';
            $message->text = 'Welcome to Pluzo 🌟

Pluzo is all about meeting new people and making new friends!

Here are some tips to get started:
- Upload profile pictures to your account
- Pick out some cool badges to display
- Join live and talk to new people
- Swipe to meet new people
- Be kind and make some new friends!

Make sure you follow our community guidelines and have a good time!
https://pluzo.com/community-guidelines';
            $message->created_at = time();
            $message->save();
            $result = Chat::getMessagers($message->chat_id, $message->id);
            User::socket(\Yii::$app->user->id, (array)$result, 'Chat');
        } 
    }

    //create new message
    public static function addStreamMessage($user_id, $channel_id)
    {   
        //find chat with users
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("
            SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = ".\Yii::$app->user->id.") 
            AND `party`.`user_id` = ".$user_id);
        $result = $command->queryAll();
        if($result[0]['chat_id']){
            $chat_id = $result[0]['chat_id'];
        } else {
            $chat = new Chat();
            $chat->user_id = \Yii::$app->user->id;
            if ($chat->save()) {
                $chat_id = $chat->id;
                $party1 = new Party();
                $party1->user_id = \Yii::$app->user->id;
                $party1->chat_id = $chat_id;
                $party1->save();
                $party2 = new Party();
                $party2->user_id = $user_id;
                $party2->chat_id = $chat_id;
                $party2->save();
            } 
        }
        if(!$chat_id){
            throw new \yii\web\HttpException('500','chat_id cannot be blank.'); 
        }
        $message = new Message();
        $message->chat_id = $chat_id;
        $message->user_id = \Yii::$app->user->id;
        $message->status = 0;
        $message->text = Stream::getStreamName($channel_id);;
        $message->type = 'invite';
        $message->channel_id = $channel_id;
        $message->created_at = time();
        $message->save();
        $result = Chat::getMessagers($message->chat_id, $message->id);
        User::socket($user_id, (array)$result, 'Chat');
        return $result;
    }

    public static function getMessagers($chat_id, $message_id)
    {   
        $result = Message::find()->where(['id'=>$message_id])->orderby('created_at DESC')->all();
        $result = ArrayHelper::toArray($result, [
            'api\models\Message' => [
                'id',
                'text',
                'created_at',
                'image',
                'chat_id',
                'status',
                'type',
                'user' => 'user_info',
            ],
        ]);
        return $result;
    }

    public static function updateMessage($request)
    {   
        if(!$request->post('message_id')){
            throw new \yii\web\HttpException('500','message_id cannot be blank.'); 
        }
        $msg = Message::find()->where(['id'=>$request->post('message_id')])->one();
        if ($msg) {
            if ($request->post('text')) { $msg->text = $request->post('text'); }
            if( count($_FILES)>0 AND $_FILES['image']['tmp_name'] ) {
                $file_name = uniqid().'.jpg';   
                $temp_file_location = $_FILES['image']['tmp_name']; 
                User::s3Upload('chat/', $file_name, $temp_file_location);
                $msg->image = env('AWS_S3_PLUZO').'chat/'.$file_name;
            }
            if ($msg->save()) {
                return Message::find()->where(['chat_id'=>$msg->chat_id])->orderby('created_at DESC')->all();
            } else {
                throw new \yii\web\HttpException('500', 'Error save message!');
            }
            
        } else {
            throw new \yii\web\HttpException('500', 'Message with id = '.$request->post('message_id').' not exist');
        }
    }

    public static function deleteMessage($request)
    {   
        if(!$request->post('message_id')){
            throw new \yii\web\HttpException('500','message_id cannot be blank.'); 
        }
        $msg = Message::find()->where(['id'=>$request->post('message_id')])->one();
        if ($msg) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('message', ['id' => $msg->id])
            ->execute();
            return Message::find()->where(['chat_id'=>$msg->chat_id])->orderby('created_at DESC')->all();
        } else {
            throw new \yii\web\HttpException('500', 'Message with id = '.$request->post('message_id').' not exist');
        }
    }

    public static function deleteChat($request)
    {   
        if(!$request->post('chat_id')){
            throw new \yii\web\HttpException('500','chat_id cannot be blank.'); 
        }
        $msg = Party::find()->where(['chat_id'=>$request->post('chat_id'), 'user_id'=>\Yii::$app->user->id])->one();
        if ($msg) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('party', ['user_id' => \Yii::$app->user->id, 'chat_id' =>$request->post('chat_id')])
            ->execute();
            return ["result"=>'Deleted'];
        } else {
            throw new \yii\web\HttpException('500', 'Message with id = '.$request->post('message_id').' not exist');
        }
    }

    
    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Name',
            'user_id' => 'User ID',
        ];
    }

    public function fields()
    {
        return [
            'id' => 'id',
        ];
    }
}
