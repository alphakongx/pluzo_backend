<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\User;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\models\Service;
use api\models\Payment;
use api\models\Advance;

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
        if(!$type){
            throw new \yii\web\HttpException('500','Type cannot be blank.');
        }
    
        return [
            'info'=>Advance::useBoost($type),
            'user'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
        ];
    }

    public function actionRunRemind() {
        return [
            'info'=>Advance::runService(3),
            'user'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
        ];
    }
    


}