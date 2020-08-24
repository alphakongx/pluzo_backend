<?php

namespace api\controllers;

use Yii;
use yii\rest\Controller;
use api\models\LoginForm;
use api\models\User;
use api\models\SearchUser;
use api\models\Images;
use api\models\Friend;
use api\models\UserMsg;
use api\models\Chat;
use api\models\Badge;
use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;

class SiteController extends Controller
{   

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' =>  HttpBearerAuth::className(),
            'except' => ['login-sms', 'check-number', 'index','check-username', 'sms', 'login', 'signup', 'forgot-sms-send', 'forgot-sms-code', 'new-pass-code', 'verify-sms-send', 'verify-sms-code', 'login-sms-send', 'login-sms-code']
        ];

        return $behaviors;
    }


    public function actionSearchAll()
    {   
        if(!$_POST['search']){
            throw new \yii\web\HttpException('500','search cannot be blank.'); 
        }
        return [
            'all'=>[
                'friends'=>Friend::getSearch($_POST['search']),
                'people'=>SearchUser::getSearch($_POST['search']),
                'chat'=>Chat::getSearch($_POST['search']),
                'live'=>[],
            ],
        ];
    }


    public function actionDeleteAccount()
    {
        User::deleteAccount();
        return 'Account deleted';
    }  
    
    public function actionSortImage()
    {   
        $array = $_POST['sort'];
        if(count($array) < 1){
            throw new \yii\web\HttpException('500','sorrt cannot be < 1'); 
        }
        if (!is_array($_POST['sort'])) {
            throw new \yii\web\HttpException('500','sort must be array sort[25]=0 '); 
        }
        foreach ($array as $key => $value) {
            $image = Images::find()->where(['user_id'=>\Yii::$app->user->id, 'id'=>$key])->one();
            if($image){
                $image->sort = $value;
                $image->save();
            }
        }
        User::setAvatar();
        return User::find()->where(['id'=>\Yii::$app->user->id])->one();

    }

    public function actionGetUserInfo()
    {   
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        $user = User::find()->where(['id'=>$_POST['user_id']])->one();
        if ($user) {
            return $user;
        } else {
            throw new \yii\web\HttpException('500','not found'); 
        }
        
    }


    public function actionCheckUsername()
    { 
        if(!$_POST['username']){
            throw new \yii\web\HttpException('500','username cannot be blank.'); 
        }
        $user = User::find()->where(['username'=>$_POST['username']])->one();
        if ($user) {
            throw new \yii\web\HttpException('500','username is not available.'); 
        } else {
            return 'username is available';
        }
    }

    public function actionForgotSmsSend()
    {      
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone'])])->one();
        if ($user) {
            //send sms
            $digits = 4;
            $user->forgot_sms_code = rand(pow(10, $digits-1), pow(10, $digits)-1);
            $user->forgot_sms_code_exp = time()+3600;
            if ($user->save()) {
                $message = 'Your app code is: '.$user->forgot_sms_code.'

'.env('SMS_HASH');
                User::Sms($_POST['phone'], $message);
                return 'Code sent to '.str_replace(' ', '', $_POST['phone']).'!';
            }
        }
        throw new \yii\web\HttpException('500','User with this number not found!'); 
    }

    public function actionForgotSmsCode()
    {   
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        if(!$_POST['code']){
            throw new \yii\web\HttpException('500','code cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone']), 'forgot_sms_code'=>$_POST['code']])->one();
        if ($user) {
            $digits = 10;
            $user->reset_pass_code = rand(pow(10, $digits-1), pow(10, $digits)-1);
            $user->status = User::STATUS_ACTIVE;
            if ($user->save()) {
                return ['pass_code'=> $user->reset_pass_code];
            }
        }
        throw new \yii\web\HttpException('500','Code for number '.str_replace(' ', '', $_POST['phone']).' is incorrect !'); 
    }

    public function actionNewPassCode()
    {   
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        if(!$_POST['pass_code']){
            throw new \yii\web\HttpException('500','pass_code cannot be blank.'); 
        }
        if(!$_POST['password']){
            throw new \yii\web\HttpException('500','password cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone']), 'reset_pass_code'=>$_POST['pass_code']])->one();
        if ($user) {
            $user->password_hash = Yii::$app->security->generatePasswordHash($_POST['password']);
            $user->forgot_sms_code = '';
            $user->forgot_sms_code_exp = '';
            $user->reset_pass_code = '';
            $token = new Token();
            $token->user_id = $user->id;
            $token->generateToken(time() + 3600 * 24 * 365);
            $token->save();
            if ($user->save()) {
                $us = User::find()->where(['id'=>$user->id])->one();
                return $us;
            }
        }
        throw new \yii\web\HttpException('500','User with this pass_code not found!');
    }


    public function actionVerifySmsSend()
    {      
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone'])])->one();
        if ($user) {
            $digits = 4;
            $user->verify_sms_code = rand(pow(10, $digits-1), pow(10, $digits)-1);
            if ($user->save()) {
                $message = 'Your app code is: '.$user->verify_sms_code.'

'.env('SMS_HASH');
                User::Sms($_POST['phone'], $message);
                return 'Verify code sent to '.$_POST['phone'].'!';
            }
        }
        throw new \yii\web\HttpException('500','User with this number not found!'); 
    }

    public function actionVerifySmsCode()
    {   
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        if(!$_POST['code']){
            throw new \yii\web\HttpException('500','code cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>$_POST['phone'], 'verify_sms_code'=>$_POST['code']])->one();
        if ($user) {
            $user->status = User::STATUS_ACTIVE;
            $user->verify_sms_code = '';
            $token = new Token();
            $token->user_id = $user->id;
            $token->generateToken(time() + 3600 * 24 * 365);
            $token->save();
            if ($user->save()) {
                return $user;
            }
        }
        throw new \yii\web\HttpException('500','Code for number '.$_POST['phone'].' is incorrect !'); 
    }

    public function actionDeleteImage()
    {  
        if(!$_POST['image_id']){
            throw new \yii\web\HttpException('500','image_id cannot be blank.'); 
        }
        $im = Images::find()->where(['id'=>$_POST['image_id'], 'user_id'=>\Yii::$app->user->id])->one();
        if ($im) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('images', ['id' => $im->id])
            ->execute();
            User::setAvatar();
            return 'Image deleted!';
        } else {
            throw new \yii\web\HttpException('500','Image with id = '.$_POST['image_id'].' not exist');
        }
    }

    public function actionCheckNumber()
    { 
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        $user = UserMsg::find()->where(['phone'=>str_replace(' ', '', $_POST['phone'])])->one();
        if ($user) {
            return $user->phone;
        } else {
            throw new \yii\web\HttpException('500','User with this number not found!'); 
        }
    }

    public function actionLoginSmsSend()
    {      
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone'])])->one();
        if ($user) {
            $digits = 4;
            $user->login_sms_code = rand(pow(10, $digits-1), pow(10, $digits)-1);
            if ($user->save()) {
                $message = 'Your app code is: '.$user->login_sms_code.'

'.env('SMS_HASH');
                User::Sms($_POST['phone'], $message);
                return 'Verify code sent to '.str_replace(' ', '', $_POST['phone']).'!';
            }
        }
        throw new \yii\web\HttpException('500','User with this number not found!'); 
    }

    public function actionLoginSmsCode()
    {   
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        if(!$_POST['code']){
            throw new \yii\web\HttpException('500','code cannot be blank.'); 
        }
        $user = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone']), 'login_sms_code'=>$_POST['code']])->one();
        if ($user) {
            $user->login_sms_code = '';
            $user->status = User::STATUS_ACTIVE;
            $token = new Token();
            $token->user_id = $user->id;
            $token->generateToken(time() + 3600 * 24 * 365);
            $token->save();
            if ($user->save()) {
                return $user;
            }
        }
        throw new \yii\web\HttpException('500','Code for number '.str_replace(' ', '', $_POST['phone']).' is incorrect !'); 
    }
    

    public function actionIndex()
    {   
        return 'api';
    }

    public function actionLogin()
    {   
        $model = new LoginForm();
        $model->username = $_POST['username'];
        $model->password = $_POST['password'];
        if ($token = $model->auth()) {  
            return User::findOne($token->user_id); 
        } else {
            return $model;
        }
    }

    public function actionProfile()
    {  
        return User::find()->where(['id'=>\Yii::$app->user->id])->one();
    }

    public function actionUpdate()
    {
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        //$model->scenario = 'update';

        if (isset($_POST['latitude'])) { $user->latitude = $_POST['latitude']; }
        if (isset($_POST['longitude'])) { $user->longitude = $_POST['longitude']; }
        if (isset($_POST['phone'])) { $user->phone = str_replace(' ', '', $_POST['phone']); }
        if (isset($_POST['bio'])) { $user->bio = $_POST['bio']; }
        if (count($_POST['badges']) > 0 ) {
            foreach ($_POST['badges'] as $key => $value) {
                $badge = Badge::find()->where(['badge_id'=>$value, 'user_id'=>\Yii::$app->user->id])->one();
                if($badge){
                } else {
                    $badge = new Badge();
                    $badge->user_id = \Yii::$app->user->id;
                    $badge->badge_id = $value;
                    $badge->save();
                }
            }
        }
        if (isset($_POST['remove_badges']) AND $_POST['remove_badges'] == 1) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('badge', ['user_id' => \Yii::$app->user->id])
            ->execute();
        }
        if (isset($_POST['premium'])) { $user->premium = $_POST['premium']; }
        if (isset($_POST['gender'])) { $user->gender = $_POST['gender']; }
        if (isset($_POST['birthday'])) { $user->birthday = $_POST['birthday']; }
        if (isset($_POST['first_name'])) { $user->first_name = $_POST['first_name']; }
        if (isset($_POST['last_name'])) { $user->last_name = $_POST['last_name']; }
        if (isset($_POST['password'])) { $user->password_hash = Yii::$app->security->generatePasswordHash($_POST['password']); }
        if( count($_FILES['images'])>0 AND $_FILES['images']['tmp_name'] ) {
            User::savePhoto((array)$_FILES['images']);
            User::setAvatar();
        }
        if (isset($_POST['latitude']) AND isset($_POST['longitude'])) {
            $lat = $_POST['latitude'];
            $long = $_POST['longitude'];
            $user->address = User::getAddress($lat, $long);
        }
        $user->save();
        return User::findOne($user->id);
    }   

    public function actionSignup()
    {   
        $model = new User();
        $model->scenario = 'create';
        $model->auth_key = 'pluzo';
        $model->access_token = 'access_token'.time();
        $model->password_hash = Yii::$app->security->generatePasswordHash($_POST['password']);
        $model->username = $_POST['username'];
        $model->email = $_POST['username'];
        $model->birthday = $_POST['birthday'];
        $model->gender = $_POST['gender'];
        $model->status = User::STATUS_NOT_ACTIVE;
        $model->first_name = $_POST['first_name'];
        $model->last_name = $_POST['last_name'];
        $model->phone = str_replace(' ', '', $_POST['phone']);
        $model->latitude = $_POST['latitude'];
        $model->longitude = $_POST['longitude'];
        //$model->address = $_POST['address'];
        if (isset($_POST['latitude']) AND isset($_POST['longitude'])) {
            $lat = $_POST['latitude'];
            $long = $_POST['longitude'];
            $model->address = User::getAddress($lat, $long);
        }
        $model->premium = User::NOT_PREMIUM;
        if( count($_FILES)>0 AND $_FILES['image']['tmp_name'] ) {
            $file_name = uniqid().'.jpg';   
            $temp_file_location = $_FILES['image']['tmp_name']; 
            User::s3Upload('user/', $file_name, $temp_file_location);
            $model->image = env('AWS_S3_PLUZO').'user/'.$file_name;

        }
        if ($model->save()) {
            if( $model->image ) {
                $im = new Images();
                $im->user_id = $model->id; 
                $im->avator = 0;
                $im->created_at = time();
                $im->path = $model->image;
                $im->sort = 0;
                $im->save();
            }
            $token = new Token();
            $token->user_id = $model->id;
            $token->generateToken(time() + 3600 * 24 * 365);
            $token->save();
            return User::findOne($model->id);            

        } elseif (!$model->hasErrors()) {
            throw new \yii\web\HttpException('Failed to create the object for unknown reason.');
        };
        return $model;
    }

    public function actionSearchUser() {
        $request = Yii::$app->request;
        return User::searchUser($request);
    }

    public function actionAddBadge() {
        $request = Yii::$app->request;
        return Badge::addfupdBadge($request);
    }

    public function actionGetBadge() {
        return Badge::getBadge();
    }

    public function actionDeteleBadge() {
        $request = Yii::$app->request;
        return Badge::deleteBadge($request);
    }

}
