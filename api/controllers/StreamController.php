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
        if ($stream->save()) {
            $st = Stream::find()->where(['id'=>$stream->id])->asarray()->one();
            $connection = Yii::$app->getDb();
            $command = $connection->createCommand("SELECT `client`.`id` FROM `friend` l1 
                INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
                LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
                WHERE l1.user_source_id = ".\Yii::$app->user->id);
            $result = $command->queryAll();  
            $friends = [];
            foreach ($result as $key => $value) {
                array_push($friends, $value['id']);
            }
            $socket = [
                'stream'=>$st,
                'friends'=>$friends
            ];
            User::socket(0, $socket, 'Start_stream');
            return $stream;
        } else {
            throw new \yii\web\HttpException('500','Error save stream'); 
        }
    }

    public function actionStreamStop() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $stream = Stream::find()->where(['channel'=>$_POST['channel_id'], 'user_id'=>\Yii::$app->user->id])->one();
        if ($stream) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream', ['id' => $stream->id])
            ->execute();
            User::socket(0, $stream->id, 'Stop_stream');
            return 'Stream deleted!';
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

    public function actionStreamUsers() {
        return Stream::getUsers();
    }

    public function actionStreamJoin() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $stream = new StreamUser();
        $stream->user_id = \Yii::$app->user->id;
        $stream->channel = $_POST['channel_id'];
        if ($stream->save()) {
            $result = ['user'=>self::userForApi(\Yii::$app->user->id), 'stream'=>$_POST['channel_id']];
            User::socket(0, $result, 'Stream_join_user');
            return $stream;
        } else {
            throw new \yii\web\HttpException('500','Error save stream'); 
        }
    }

    public function actionStreamDisconnect() {
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }

        $result = ['user'=>self::userForApi(\Yii::$app->user->id), 'stream'=>$_POST['channel_id']];
            User::socket(0, $result, 'Stream_disconnect_user');

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['channel' => $_POST['channel_id'], 'user_id'=>\Yii::$app->user->id])
            ->execute();


            return 'User was disconnect fomr stream!';

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


    public function actionStreamInvite() {
        if(!$_POST['friend_id']){
            throw new \yii\web\HttpException('500','frined_id cannot be blank.'); 
        }
        if(!$_POST['channel_id']){
            throw new \yii\web\HttpException('500','channel_id cannot be blank.'); 
        }
        $result = ['user'=>UserMsg::find()->where(['id'=>\Yii::$app->user->id])->one(), 'stream'=>$_POST['channel_id']];
        User::socket($_POST['friend_id'], $result, 'Stream_invite');
        return $result;
    }

}