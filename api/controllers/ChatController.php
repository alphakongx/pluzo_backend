<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Chat;
use api\models\User;
use api\models\Party;
use api\models\Message;
use api\models\ChatMessage;

class ChatController extends Controller
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

    public function actionChat() {
        return Chat::getChat();
    }

    public function actionCloseChat() {
        $request = Yii::$app->request;
        return Chat::closeChat($request);
    }
    
    public function actionOpenChat() {
        $request = Yii::$app->request;
        return Chat::openChat($request);
    }

    public function actionDeleteChat() {
        $request = Yii::$app->request;
        return Chat::deleteChat($request);
    }

    public function actionGetChatUser() {
        $request = Yii::$app->request;
        $user_target_id = $request->post('user_target_id');
        if(!isset($user_target_id)){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        if($user_target_id == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','user_target_id = your id.'); 
        }

        //banned users
            if (in_array($user_target_id, User::bannedUsers())) {
                throw new \yii\web\HttpException('500','User was banned you'); 
            }
            if (in_array($user_target_id, User::whoBannedMe())) {
                throw new \yii\web\HttpException('500','User has banned you'); 
            }

        return Chat::getChatUser($user_target_id);
    }
    public function actionGetCurrentChat() {
        $request = Yii::$app->request;
        return ChatMessage::getCurrentChat($request);
    }

    public function actionReadMessage() {
        $request = Yii::$app->request;
        return Message::readMessage($request);
    }

    public function actionChatMessage() {
        $id = \Yii::$app->user->id;
        return ChatMessage::getChatMessage($id);
    }

    public function actionRecentlyMessagedUsers() {
        $id = \Yii::$app->user->id;
        return ChatMessage::getRecentlyMessaged($id);
    }

    public function actionSendMessage() {
        $request = Yii::$app->request;
        return Chat::addMessage($request);
    }

    public function actionUpdateMessage() {
        $request = Yii::$app->request;
        return Chat::updateMessage($request);
    }

    public function actionDeleteMessage() {
        $request = Yii::$app->request;
        return Chat::deleteMessage($request);
    }

}