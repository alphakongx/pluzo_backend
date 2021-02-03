<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\User;
use api\models\Like;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Service;
use api\models\Payment;
use api\models\Advance;
use api\models\Stream;

class PaymentController extends Controller
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

    public function actionServices() {
        return Service::getService();
    }

    public function actionPay() {
        $request = Yii::$app->request;
        return Payment::pay_temp($request);
        return Payment::pay($request);
    }

    public function actionRunService() {
        $request = Yii::$app->request;
        $type = (int)$request->post('type');
        return Advance::runService($request);
    }

    public function actionRunBoost() {
        $request = Yii::$app->request;
        $type = (int)$request->post('type');
        $channel_id = $request->post('channel_id');
        if($type == Advance::BOOST_TYPE_LIVE){
            if(!$channel_id){
                throw new \yii\web\HttpException('500','channel_id cannot be blank for boost type = 2.');
            }

            //invite_only
            $stream = Stream::find()->where(['channel'=>$channel_id])->one();
            if(!$stream){
                throw new \yii\web\HttpException('500','stream not exist.');
            }
            if ($stream->invite_only == 1 ) {
                throw new \yii\web\HttpException('500','you shouldn’t be able to boost a live that is invite only');
            }
        }
        if(!$type){
            throw new \yii\web\HttpException('500','type cannot be blank.');
        }

        return [
            'info'=>Advance::useBoost($type),
            'user'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
        ];
    }

    public function actionRunRewind() {
        $request = Yii::$app->request;
        $user_target_id = $request->post('user_target_id');
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }

        //check match likes
        $like_you = Like::find()->select('user_source_id')
        ->where(['user_source_id'=>$user_target_id, 'user_target_id'=>\Yii::$app->user->id])
        ->andwhere(['in','like',[1,2]])
        ->asArray()
        ->all();
        foreach ($like_you as $key => $value) {
            if ($value['user_source_id'] == $user_target_id) {
                throw new \yii\web\HttpException('500','You cannot do rewind for user who already likes you!');
            }
        }

        //check friends
        $connection = Yii::$app->getDb();
        $sql = "SELECT ".User::userFields()." FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $key => $value) {
            if ($value['id'] == $user_target_id) {
                throw new \yii\web\HttpException('500','You cannot do rewind for friends');
            }
            
        }

        return [
            'info'=>Advance::useReminder($user_target_id),
            'user'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
        ];
    }
    


}