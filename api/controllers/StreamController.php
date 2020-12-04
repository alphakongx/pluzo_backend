<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\User;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Chat;
use api\models\Party;
use api\models\Message;
use api\models\ChatMessage;
use api\models\Stream;
use api\models\StreamUser;
use api\models\UserMsg;
use api\models\Badge;
use api\models\StreamAsk;
use api\models\StreamChat;
use api\models\Friend;
use api\models\StreamInvite;
use api\models\StreamBan;
use yii\helpers\ArrayHelper;

class StreamController extends Controller
{   

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' =>  HttpBearerAuth::className(),
            'except' => '',
        ];

        return $behaviors;
    }

    public function actionStreamUpdate() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $stream = Stream::find()->where(['channel'=>$_POST['channel_id'], 'user_id'=>\Yii::$app->user->id])->one();
        if (isset($_POST['name'])) {$stream->name = $_POST['name'];}
        if (isset($_POST['category'])) {$stream->category = $_POST['category'];}
        if (isset($_POST['invite_only'])) {$stream->invite_only = $_POST['invite_only'];}
        if($stream->save()){
            $socket = [
                'stream'=>$stream,
                'friends'=>Friend::getFriend(\Yii::$app->user->id)
            ];
            User::socket(0, $socket, 'Start_update');
            return $socket;
        } else {
            throw new \yii\web\HttpException('500','channel_id cannot be blank.');             
        }
    }

    public function actionStreamStart() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!isset($_POST['category'])){
            throw new \yii\web\HttpException('500','category cannot be blank.'); 
        }
        if(!isset($_POST['name'])){
            throw new \yii\web\HttpException('500','name cannot be blank.'); 
        }
        $check = Stream::find()->where(['channel'=>$_POST['channel_id']])->one();
        if ($check) {
            throw new \yii\web\HttpException('500','Stream channel already exist!'); 
        }
        $stream = new Stream();
        $stream->user_id = \Yii::$app->user->id;
        $stream->created_at = time();
        $stream->channel = $_POST['channel_id'];
        $stream->category = $_POST['category'];
        $stream->name = $_POST['name'];
        $stream->invite_only = (int)$_POST['invite_only'];
        if ($stream->save()) {
            //add stream_user
            StreamUser::addUser(\Yii::$app->user->id, $_POST['channel_id'], StreamUser::__USER_BROAD__, StreamUser::__USER_HOST__);
            $socket = [
                'stream'=>$stream,
                'friends'=>Friend::getFriend(\Yii::$app->user->id)
            ];
            User::socket(0, $socket, 'Start_stream');
            return $socket;
        } else {
            throw new \yii\web\HttpException('500','Error save stream'); 
        }
    }

    public function actionStreamUserType()
    {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if(!isset($_POST['type'])){
            throw new \yii\web\HttpException('500','type cannot be blank.'); 
        }
        if((int)$_POST['type'] > 1){
            throw new \yii\web\HttpException('500','type can be only 1 or 0'); 
        }
        StreamUser::changeUser($_POST['user_id'], $_POST['channel_id'], $_POST['type']);
        $result = [
            'user'=>Stream::userForApi($_POST['user_id']),
            'stream'=>$_POST['channel_id'],
            'type'=>$_POST['type'],
        ];
        User::socket(0, $result, 'Stream_user_change_type');
        return $result;
    }

    public function actionStreamChatAddMsg()
    {
        return StreamChat::addMsg(Yii::$app->request);
    }

    public function actionStreamChatGetMsg()
    {
        return StreamChat::getMsg(Yii::$app->request);
    }

    public function actionStreamStop() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $stream = Stream::find()->where(['channel'=>$_POST['channel_id'], 'user_id'=>\Yii::$app->user->id])->one();
        if ($stream) {
            //change message from invite to close and send socket
            $msg = Message::find()->where(['type'=>'invite', 'channel_id'=>$_POST['channel_id']])->all();
            if($msg){

                foreach ($msg as $key => $value) {

                    $msg_one = Message::find()->where(['id'=>$value['id']])->one();
                    $msg_one->type = Message::__CLOSE_;
                    if ($msg_one->save(false)) {
                        $party = Party::find()->where(['chat_id'=>$msg_one->chat_id])->andWhere(['<>','user_id', $msg_one->user_id])->one();
                        if ($party) {
                            $message_source_id = $party->user_id;
                        } else {
                            $message_source_id = 0;
                        }
                        $socket_data = [
                            'message_id'=>$msg_one->id,
                            'stream_info'=>['name'=>$stream->name],
                        ];
                        User::socket($message_source_id, $socket_data, 'Stream_invite_msg_cancel');
                    }
                }
            
            }

            \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream', ['id' => $stream->id])
            ->execute();
            User::socket(0, $_POST['channel_id'], 'Stop_stream');
            StreamUser::deleteAllUser($_POST['channel_id']);
            return 'Stream stop!';
        } else {
            throw new \yii\web\HttpException('500','Stream '.$_POST['channel_id'].' not exist');
        }
    }

    public function actionStreamListApi() {
        return Stream::getChannelList();
    }

    public function actionStreamList() {
        return Stream::getStream();
    }

    public static function userForApi($id){
        $user = UserMsg::find()->where(['id'=>$id])->one();
            $user = ArrayHelper::toArray($user, [
                'api\models\UserMsg' => [
                    '_id' => 'id',
                    'username',    
                    'first_name',
                    'last_name',  
                    'phone',
                    'status',
                    'gender',
                    'image',
                    'birthday',
                    'latitude',
                    'longitude',
                    'address',
                    'last_activity',
                    'premium',
                    'bio',
                    'images',
                    'friends'=>function(){ 
                        return User::friendCount(\Yii::$app->user->id);
                    },
                    'badges'=>function(){ 
                        return Badge::getBadge(\Yii::$app->user->id);
                    },
                ],
            ]);    
        return $user;
    }

    public function actionStreamUserListApi() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $channel = $_POST['channel_id'];
        return Stream::getUserChannelList($channel);
    }

    public function actionStreamReport() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$_POST['reason']){
            throw new \yii\web\HttpException('500','reason cannot be blank.'); 
        }
        if(!$_POST['msg']){
            throw new \yii\web\HttpException('500','msg cannot be blank.'); 
        }
        return Stream::streamReport(Stream::REPORT_STREAM, $_POST['channel_id'], $_POST['reason'], $_POST['msg'], \Yii::$app->user->id);
    }

    public function actionStreamUsers() {
        return Stream::getUsers();
    }

    public function actionStreamJoin() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }

        $check = StreamBan::find()->where(['user_id'=>\Yii::$app->user->id, 'channel_id'=>$_POST['channel_id']])->one();
        if($check){
            throw new \yii\web\HttpException('500','You was banned in this stream'); 
        }

        StreamUser::addUser(\Yii::$app->user->id, $_POST['channel_id'], StreamUser::__USER_AUDIENCE__, StreamUser::__USER_NOT_HOST__);
        $result = ['user'=>self::userForApi(\Yii::$app->user->id), 'stream'=>$_POST['channel_id']];
        User::socket(0, $result, 'Stream_join_user');
        return $result;
    }

    public function actionStreamDisconnect() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        StreamUser::deleteUser(\Yii::$app->user->id, $_POST['channel_id']);
        $result = ['user'=>self::userForApi(\Yii::$app->user->id), 'stream'=>$_POST['channel_id']];
        User::socket(0, $result, 'Stream_disconnect_user');
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['channel' => $_POST['channel_id'], 'user_id'=>\Yii::$app->user->id])
            ->execute();
        return 'User was disconnect from stream!';
    }

    public function actionStreamUserAcceptJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$request->post('user_id')){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if($request->post('user_id') == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','user_id = your ID!'); 
        }
            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user'=>Stream::userForApi($request->post('user_id')),
                'stream'=>$request->post('channel_id'),
            ];
            User::socket(0, $result, 'Stream_user_accept_join');
            return $result;
    }

    public function actionStreamUserRefusedJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$request->post('user_id')){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if($request->post('user_id') == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','user_id = your ID!'); 
        }
        $result = [
            'host'=>Stream::userForApi(\Yii::$app->user->id),
            'user'=>Stream::userForApi($request->post('user_id')),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(0, $result, 'Stream_user_refused_join');
        return $result;
    }

    public function actionStreamUserAskJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        return StreamAsk::checkAsk('Stream_user_ask_join', $request->post('channel_id'), \Yii::$app->user->id);
    }

    public function actionStreamUserCancelAsk() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $result = [
            //'host'=>Stream::userForApi($stream->user_id),
            'user'=>Stream::userForApi(\Yii::$app->user->id),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(\Yii::$app->user->id, $result, 'Stream_user_cancel_ask');
        return $result;
    }

    public function actionStreamAskJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$request->post('user_id')){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        return StreamAsk::checkAsk('Stream_ask_join', $request->post('channel_id'), $request->post('user_id'));
    }

    public function actionStreamAcceptJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $result = [
            'user'=>Stream::userForApi(\Yii::$app->user->id),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(0, $result, 'Stream_accept_join');
        return $result;
    }

    public function actionStreamRefusedJoin() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $result = [
            'user'=>Stream::userForApi(\Yii::$app->user->id),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(0, $result, 'Stream_refused_join');
        return $result;
    }

    public function actionStreamDisconnectBroad() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        if(!$request->post('user_id')){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        $result = [
            'user'=>Stream::userForApi($request->post('user_id')),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(0, $result, 'Stream_broadcast_disconnect_by_host');
        return $result;
    }

    public function actionStreamDisconnectBroadByUser() {
        $request = Yii::$app->request;
        if(!$request->post('channel_id')){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $result = [
            'user'=>Stream::userForApi(\Yii::$app->user->id),
            'stream'=>$request->post('channel_id'),
        ];
        User::socket(0, $result, 'Stream_broadcast_disconnect_by_user');
        return $result;
    }

    public function actionStreamBanList() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        return StreamBan::find()->where(['channel_id'=>$_POST['channel_id']])->all();
    }

    public function actionStreamBanUser() {
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if($_POST['user_id'] == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','Cant ban yourself'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        
        $check_host = Stream::find()->where(['user_id'=>\Yii::$app->user->id,'channel'=>$_POST['channel_id']])->one();
        if(!$check_host){
            throw new \yii\web\HttpException('500','You are not hoster of this stream or stream not exist.');
        }

        $check = StreamBan::find()->where(['channel_id'=>$_POST['channel_id'], 'user_id'=>$_POST['user_id']])->one();
        if($check){
            throw new \yii\web\HttpException('500','User already banned.'); 
        }
        $ban = new StreamBan();
        $ban->user_id = $_POST['user_id'];
        $ban->channel_id = $_POST['channel_id'];
        $ban->created_at = time();
        if ($ban->save()) {
            $result = [
                'user'=>$ban->user_id,
                'stream'=>Stream::find()->where(['channel'=>$_POST['channel_id']])->one(),
            ];
            User::socket($ban->user_id, $result, 'Stream_user_ban');
            return $result;
        } else {
            throw new \yii\web\HttpException('500','Error save ban user.'); 
        }
    }

    public function actionStreamUnbanUser() {

        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if($_POST['user_id'] == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','Cant unban yourself'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }

        $check_host = Stream::find()->where(['user_id'=>\Yii::$app->user->id,'channel'=>$_POST['channel_id']])->one();
        if(!$check_host){
            throw new \yii\web\HttpException('500','You are not hoster of this stream or stream not exist.');
        }

        $check = StreamBan::find()->where(['channel_id'=>$_POST['channel_id'], 'user_id'=>$_POST['user_id']])->one();
        if(!$check){
            throw new \yii\web\HttpException('500','User not banned.'); 
        }

        \Yii::$app
        ->db
        ->createCommand()
        ->delete('stream_ban', ['user_id' => $_POST['user_id'], 'channel_id'=>$_POST['channel_id']])
        ->execute();

        $result = [
                'user'=>$ban->user_id,
                'stream'=>Stream::find()->where(['channel'=>$_POST['channel_id']])->one(),
            ];
            User::socket($ban->user_id, $result, 'Stream_user_unban');
        return 'User was deleted from ban list!';
    }

    public function actionStreamInvite() {
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $check_inv = StreamInvite::find()->where(['user_id'=>$_POST['user_id'],'channel_id'=>$_POST['channel_id']])->one();
        if($check_inv){
            throw new \yii\web\HttpException('500','User already invited.'); 
        } else {
            $inv = new StreamInvite();   
            $inv->user_id = $_POST['user_id'];
            $inv->channel_id = $_POST['channel_id'];
            $inv->created_at = time();
            $inv->save();

            $stream = Stream::streamInfo($_POST['channel_id']);
            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user'=>Stream::userForApi($_POST['user_id']),
                'stream'=>$stream,
            ];
            $msg = Chat::addStreamMessage($_POST['user_id'], $_POST['channel_id']);
            User::socket($_POST['user_id'], $result, 'Stream_invite');
            $res = [
                'result'=>$result,
                'msg'=>$msg,
            ];
            return $res;
        }

    }

    public function actionStreamCancelInvite() {
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $check_inv = StreamInvite::find()->where(['user_id'=>$_POST['user_id'],'channel_id'=>$_POST['channel_id']])->one();
        if($check_inv){
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_invite', ['user_id' => $_POST['user_id'], 'channel_id' => $_POST['channel_id']])
            ->execute(); 

            $connection = Yii::$app->getDb();
            $command = $connection->createCommand("
                SELECT * FROM `party` WHERE `party`.`chat_id` IN (SELECT `chat_id` FROM `party` WHERE `party`.`user_id` = ".\Yii::$app->user->id.") 
                AND `party`.`user_id` = ".$_POST['user_id']);
            $result = $command->queryAll();

            if ($result[0]['chat_id']) {
                \Yii::$app
                ->db
                ->createCommand()
                ->delete('message', ['user_id' => \Yii::$app->user->id, 'channel_id' => $_POST['channel_id'], 'chat_id' => $result[0]['chat_id']])
                ->execute(); 
            }

            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user'=>Stream::userForApi($_POST['user_id']),
                'stream'=>$_POST['channel_id'],
            ];
            User::socket($_POST['user_id'], $result, 'Stream_cancel_invite');
            return $result;
        } else {
            throw new \yii\web\HttpException('500','User not invited to this channel.');
        }
    }

    public function actionStreamNewPeople() {
        return Stream::newPps();
    }

    public function actionIsInvite() {
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $check_inv = StreamInvite::find()->where(['user_id'=>$_POST['user_id'],'channel_id'=>$_POST['channel_id']])->one();
        if($check_inv){
            $result = 1;
            //throw new \yii\web\HttpException('500','User already invited.'); 
        } else {
            $result = 0;
        }
        return ['invited' => $result];
    }

}