<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\User;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Like;
use api\models\Advance;
use api\models\PremiumUse;
use api\models\LiveSetting;

class LikeController extends Controller
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

    public function actionGetLiveSetting() {
        return LiveSetting::getLiveSetting();
    }

    public function actionSetLiveSetting() {
        $request = Yii::$app->request;
        return LiveSetting::setLiveSetting($request);
    }

    public function actionGetSwipeSetting() {
        return Like::GetSwipeSetting($request);
    }

    public function actionSetSwipeSetting() {
        $request = Yii::$app->request;
        return Like::setSwipeSetting($request);
    }

    public function actionSendLike() {
        $request = Yii::$app->request;
        $user_target_id = (int)$request->post('user_target_id');
        $is_like = (int)$request->post('is_like');

        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        if(!isset($is_like)){
            throw new \yii\web\HttpException('500','is_like cannot be blank.'); 
        }
        if($user_target_id == \Yii::$app->user->id){
             throw new \yii\web\HttpException('500','user_target_id can not be your ID'); 
        }

        return
        [
            'last_like_data' => Like::sendLike($user_target_id, $is_like),
            'like_info' => Like::getLikeInfo(),
        ];
    }

    public function actionSendLikeAll() {
        $request = Yii::$app->request;
        return Like::sendLikeAll($request);
    }

    public function actionGetMatch() {
        return Like::getMatch();
    }

    public function actionGetLikedUsers() {
        return Like::getLikedUsers();
    }

    public function actionGetLike() {
        $request = Yii::$app->request;
        $user_id = (int)$request->post('user_id');
        if(!$user_id){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        return Like::getLike($user_id);
    }

    public function actionSwipe() {
        return Like::swipe();
    }

    public function actionSwipeSearch() {
        $request = Yii::$app->request;
        return Like::swipeSearch($request);
    }
    

}