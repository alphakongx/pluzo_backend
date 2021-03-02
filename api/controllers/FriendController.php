<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\User;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Friend;

class FriendController extends Controller
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

    public function actionReadFlag() {
        $request = Yii::$app->request;
        $user_id = $request->post('user_id');
        if(!isset($user_id)){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        return Friend::readFlag($user_id);
    }

    public function actionAddFriend() {
        $request = Yii::$app->request;
        $user_target_id = (int)$request->post('user_target_id');
        return Friend::addFriend($user_target_id);
    }

    public function actionFriendRequestsReject() {
        $request = Yii::$app->request;
        return Friend::friendRequestsReject($request);
    }

    public function actionFriendRequestsToMeReject() {
        $request = Yii::$app->request;
        return Friend::friendRequestsToMeReject($request);
    }

    public function actionGetFriends() {
        $id = \Yii::$app->user->id;
        return Friend::getFriend($id);
    }

    public function actionFriendRequestsMy() {
        return Friend::friendRequestsMy();
    }

    public function actionFriendRequestsToMe() {
        return Friend::friendRequestsToMe();
    }

    public function actionAddFriendUsername() {
        $request = Yii::$app->request;
        $username = $request->post('username');
        if(!$username){
            throw new \yii\web\HttpException('500','username cannot be blank.'); 
        }
        $user = User::find()->where(['username'=>$username])->one();
        if (!$user) {
            throw new \yii\web\HttpException('500','User with username '.$username.' not exist'); 
        }
        if($user->id == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','user ID can not be your ID'); 
        }
        return Friend::addFriend($user->id);
    }

    public function actionFriendRemove() {
        $request = Yii::$app->request;
        return Friend::friendRemove($request);
    }
    
    public function actionIsFriend() {
        $request = Yii::$app->request;
        $user_target_id = (int)$request->post('user_target_id');
        return Friend::isFriend($user_target_id);
    }

}