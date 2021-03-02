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
use api\models\Like;
use api\models\Badge;
use api\models\Stream;
use api\models\Tempcode;
use api\models\ClientSetting;
use api\models\BanUser;
use api\models\StreamUser;
use common\models\Analit;

use common\models\Token;
use yii\filters\auth\HttpBearerAuth;
use yii\helpers\Url;
use api\components\PushHelper;

class SiteController extends Controller
{   

    public function behaviors()
    {
        $behaviors = parent::behaviors();

        $behaviors['authenticator'] = [
            'class' =>  HttpBearerAuth::className(),
            'except' => ['test', 'login-sms', 'check-number', 'index','check-username', 'sms', 'login', 'signup', 'forgot-sms-send', 'forgot-sms-code', 'new-pass-code', 'verify-sms-send', 'verify-sms-code', 'login-sms-send', 'login-sms-code', 'agora-user-leave-channel']
        ];

        return $behaviors;
    }

    public function actionPageLeave()
    { 
        if(!$_POST['page']){
            throw new \yii\web\HttpException('500','page cannot be blank.'); 
        }
        if(!$_POST['leave']){
            throw new \yii\web\HttpException('500','leave cannot be blank.'); 
        }
        $track = new Analit();
        $track->user_id = \Yii::$app->user->id;
        $track->time = time();
        $track->leave = $_POST['leave'];
        $track->save();
        return $track;
    }

    public function actionPageTime()
    { 
        if(!$_POST['page']){
            throw new \yii\web\HttpException('500','page cannot be blank.'); 
        }
        if(!$_POST['during']){
            throw new \yii\web\HttpException('500','during cannot be blank.'); 
        }
        $track = new Analit();
        $track->user_id = \Yii::$app->user->id;
        $track->time = time();
        $track->time_start = $_POST['time_start'];
        $track->time_end = $_POST['time_end'];
        $track->during = $_POST['during'];
        $track->page = $_POST['page'];
        $track->leave = 1;
        $track->save();
        return $track;
    }

    public function actionAskQuestion()
    { 
        if(!$_POST['type']){
            throw new \yii\web\HttpException('500','type cannot be blank.'); 
        }
        if ($_POST['type'] < 0 OR $_POST['type'] > 4) {
            throw new \yii\web\HttpException('500','type can be from 1 to 4!'); 
        }
        $type = [
            '1'=>'I have a question',
            '2'=>'I found a bug',
            '3'=>'I`d like to report a Safety Concern',
            '4'=>'Take me to Safety Center',
        ];
        return 'Message sent to administrator!';
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
                'live'=>Stream::getSearch($_POST['search']),
            ],
        ];
    }

    public function actionUserOnline()
    {
        $online_users = [];
        $temp_array = [
            'id'=>\Yii::$app->user->id,
        ];
        array_push($online_users, $temp_array);                 
        $socket_result = [
            'user'=>$online_users,
            'online'=>1,
        ];
        User::socket(0, $socket_result, 'User_online');
        return $socket_result;
    }

    public function actionUserBlock()
    {  
        $request = Yii::$app->request;
        $user_target_id = (int)$request->post('user_target_id');
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        $check = BanUser::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id,])->one();
        if ($check) {                                                                      
            throw new \yii\web\HttpException('500','You are already blocked this user.'); 
        } else {
            $block = new BanUser();
            if ($request->post('reason')) {
                $block->reason = $request->post('reason');
            }
            $block->time = time();
            $block->user_source_id = \Yii::$app->user->id;
            $block->user_target_id = $user_target_id;
            if ($block->save()) {
                Yii::$app->cache->delete('bannedUsers'.\Yii::$app->user->id);
                return $block;
            } else {
                throw new \yii\web\HttpException('500','Cant block user');
            }
        }
    }

    public function actionUserUnblock()
    {  
        $request = Yii::$app->request;
        if($request->post('user_target_id') == 0){
            throw new \yii\web\HttpException('500','You cant.');
        }
        $user_target_id = (int)$request->post('user_target_id');
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        $check = BanUser::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id,])->one();
        if ($check) {                                                                      
           \Yii::$app
                ->db
                ->createCommand()
                ->delete('ban_user', ['user_source_id' => \Yii::$app->user->id, 'user_target_id'=>$user_target_id])
                ->execute();
                Yii::$app->cache->delete('bannedUsers'.\Yii::$app->user->id);
                return 'User was deleted from block list!';
        } else {
             throw new \yii\web\HttpException('500','You are not banned this user.'); 
        }
    }

    public function actionGetBlocked()
    {  
        return $check = BanUser::find()->where(['user_source_id'=>\Yii::$app->user->id])->all();
    }
   

    public function actionUserOffline()
    {   
        $time = time() - 60;
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        $user->last_activity = $time;
        $user->save();
        $offline = [];
        $temp_array = [
            'id'=>\Yii::$app->user->id,
            'last_activity'=>$time,
        ];
        array_push($offline, $temp_array); 
        $socket_result = [
            'user'=>$offline,
            'online'=>0,
        ];
        User::socket(0, $socket_result, 'User_offline');
        return $socket_result;
    }


    public function actionDeleteAccount()
    {   
        $id = \Yii::$app->user->id;
        User::deleteAccount($id);
        return 'Account deleted';
    }

    public function actionCheckUserStatus()
    {   
        $request = Yii::$app->request;
        return User::checkstatus($request);
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
            $av = 0;
            //throw new \yii\web\HttpException('500','username is not available.'); 
        } else {
            $av = 1;
        }
        $names = [
            'username1'=>User::generateUniqueUsername($_POST['username']),
            'username2'=>User::generateUniqueUsername($_POST['username']),
            'username3'=>User::generateUniqueUsername($_POST['username']),
        ];
        return [
                'username'=>$av,
                'available_usernames'=>$names,
            ];
    }


    public function actionUpdatePhoneSend()
    { 
        if(!$_POST['phone']){
            throw new \yii\web\HttpException('500','phone cannot be blank.'); 
        }
        //check unique
        $check = User::find()->where(['phone'=>str_replace(' ', '', $_POST['phone'])])->one();
        if($check){
            //throw new \yii\web\HttpException('500','This phone number is already in use.'); 
        }
        $code = new Tempcode();
        $code->user_id = \Yii::$app->user->id;
        $code->expires_at = time()+3600; 
        $code->type = 'phone';
        $digits = 4;
        $code->code = rand(pow(10, $digits-1), pow(10, $digits)-1);
        $code->data = str_replace(' ', '', $_POST['phone']);
        if($code->save()){
            $message = 'Your Pluzo code is: '.$code->code;
            User::Sms($code->data, $message);
            return 'Update phone code sent to '.str_replace(' ', '', $_POST['phone']).'!';
        }
        throw new \yii\web\HttpException('500','Error');
    }

    public function actionUpdatePhoneCode()
    { 
        if(!$_POST['code']){
            throw new \yii\web\HttpException('500','code cannot be blank.'); 
        }
        $code = Tempcode::find()
        ->where(['user_id'=>\Yii::$app->user->id, 'code'=>$_POST['code'], 'type'=>'phone'])
        ->andwhere(['>', 'expires_at', time()])
        ->one();
        if ($code) {
            $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
            $user->phone = $code->data;
            if($user->save()){
                \Yii::$app
                ->db
                ->createCommand()
                ->delete('tempcode', ['id' => $code->id])
                ->execute();
                return User::findOne(\Yii::$app->user->id);;
            }
        }
        throw new \yii\web\HttpException('500','Phone update code is incorrect'); 
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
                $message = 'Your Pluzo code is: '.$user->forgot_sms_code;

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
                $message = 'Your Pluzo code is: '.$user->verify_sms_code;
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

    //agora request 
    public function actionAgoraUserLeaveChannel()
    {   
        $data = file_get_contents("php://input");
        $array = json_decode($data, true);

        if($array['eventType'] == 104 OR $array['eventType'] == 106){
            $channel_id = $array['payload']['channelName'];
            $user_id = $array['payload']['uid'];

            StreamUser::deleteUser($user_id, $channel_id);
            $result = [
                'user'=>Stream::userForApi($user_id),
                'stream'=>$channel_id
            ];
            User::socket(0, $result, 'Stream_disconnect_user');
            \Yii::$app
                ->db
                ->createCommand()
                ->delete('stream_user', ['channel' => $channel_id, 'user_id'=>$user_id])
                ->execute();
        }
        return true;
    }

    public function actionDeleteImage()
    {  
        if(!$_POST['image_id']){
            throw new \yii\web\HttpException('500','image_id cannot be blank.'); 
        }
        $count = Images::find()->where(['user_id'=>\Yii::$app->user->id])->count();
        if($count < 2){
            throw new \yii\web\HttpException('500','You must have at least one image'); 
        }
        $im = Images::find()->where(['id'=>$_POST['image_id'], 'user_id'=>\Yii::$app->user->id])->one();
        if ($im) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('images', ['id' => $im->id])
            ->execute();
            User::setAvatar();

            User::s3delete($im->path);
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
                $message = 'Your Pluzo code is: '.$user->login_sms_code;
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

    public function actionUpdatePass()
    {   
        if(!$_POST['old_pass']){
            throw new \yii\web\HttpException('500','old_pass cannot be blank.'); 
        }
        if(!$_POST['new_pass']){
            throw new \yii\web\HttpException('500','new_pass cannot be blank.'); 
        }
        if(strlen($_POST['new_pass']) < 6){
            throw new \yii\web\HttpException('500','password length must be >= 6'); 
        }
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        if (Yii::$app->getSecurity()->validatePassword($_POST['old_pass'], $user->password_hash)) {
            $user->password_hash = Yii::$app->security->generatePasswordHash($_POST['new_pass']);
            if($user->save()){
                return User::findOne($user->id);
            } else {
                throw new \yii\web\HttpException('500','Error update password.');
            }
        } else {
            throw new \yii\web\HttpException('500','old_pass is incorrect.');
        }
    }

    public function actionUpdate()
    {
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        if (isset($_POST['latitude'])) { $user->latitude = $_POST['latitude']; }
        if (isset($_POST['longitude'])) { $user->longitude = $_POST['longitude']; }
        if (isset($_POST['bio'])) { $user->bio = $_POST['bio']; }
        if (count($_POST['badges']) > 0 ) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('badge', ['user_id' => \Yii::$app->user->id])
            ->execute();
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
        if(isset($_POST['remove_badges']) OR count($_POST['badges']) > 0){
            $badges = Badge::getBadge(\Yii::$app->user->id);
            $result = [
                'user'=>\Yii::$app->user->id,
                'badges'=>$badges,
            ];
            User::socket(0, $result, 'Update_badges');
        }   

        if (isset($_POST['push_id'])) {
            $user->push_id = $_POST['push_id'];
        }
        if (isset($_POST['first_login'])) {
            if ($_POST['first_login'] == 0) {
                $user->first_login = 0;
            }
            if ($_POST['first_login'] == 1) {
                $user->first_login = 1;
            }
        }
        if (isset($_POST['device'])) {
            if($_POST['device'] == PushHelper::IPHONE OR $_POST['device'] == PushHelper::ANDROID){
                $user->device = $_POST['device'];
            }
        }
        if (isset($_POST['premium'])) { $user->premium = $_POST['premium']; }
        if (isset($_POST['gender'])) {
            if($_POST['gender'] == 'other'){$gender = User::GENDER_OTHER;}
            if($_POST['gender'] == 'male'){$gender = User::GENDER_MALE;}
            if($_POST['gender'] == 'female'){$gender = User::GENDER_FEMALE;}
            $user->gender = $gender; 
        }
        if (isset($_POST['birthday'])) { $user->birthday = $_POST['birthday'];
            Like::checkBirthday($user->birthday);
        }
        if (isset($_POST['first_name'])) { $user->first_name = $_POST['first_name']; }
        if (isset($_POST['last_name'])) { $user->last_name = $_POST['last_name']; }
        if( count($_FILES['images'])>0 AND $_FILES['images']['tmp_name'] ) {
            User::savePhoto((array)$_FILES['images']);
            User::setAvatar();
        }
        if (isset($_POST['latitude']) AND isset($_POST['longitude'])) {
            $lat = $_POST['latitude'];
            $long = $_POST['longitude'];

            $user_swipe =  Like::getSwipeSetting();
            if ($user_swipe->current_location == 1) {
                $address = User::getAddress($lat, $long);
                $user->address = $address['country'];;
                $user->state = $address['state'];
                $user->city = $address['city'];
            }
        }
        ClientSetting::update_setting(Yii::$app->request);

        $user->save();
        $res = User::findOne($user->id);
        $socket = [
            'user'=>Stream::userForApi($user->id),
        ];
        User::socket(0, $socket, 'User_update');
        return $res;
    }   

    public function actionSignup()
    {   
        $model = new User();
        if( count($_FILES['images']['name']) > 3  ){
            throw new \yii\web\HttpException('500','You can send maximum 3 images');
        }
        if (!$_POST['password']) {
            throw new \yii\web\HttpException('500','password can not be blank');
        }
        $model->scenario = 'create';
        $model->auth_key = 'pluzo';
        $model->access_token = 'access_token'.time();
        $model->first_login = 1;
        $model->password_hash = Yii::$app->security->generatePasswordHash($_POST['password']);
        $model->username = $_POST['username'];
        $model->email = $_POST['username'];
        $model->birthday = $_POST['birthday'];
        Like::checkBirthday($model->birthday);
        if (isset($_POST['gender'])) { 
            if($_POST['gender'] == 'other'){$gender = User::GENDER_OTHER;}
            if($_POST['gender'] == 'male'){$gender = User::GENDER_MALE;}
            if($_POST['gender'] == 'female'){$gender = User::GENDER_FEMALE;}
            $model->gender = $gender; 
        } else {
            throw new \yii\web\HttpException('500','Gender is required! male, female, other');
        }

        $model->status = User::STATUS_NOT_ACTIVE;
        $model->first_name = $_POST['first_name'];
        $model->last_name = $_POST['last_name'];
        $model->phone = str_replace(' ', '', $_POST['phone']);
        $model->latitude = $_POST['latitude'];
        $model->longitude = $_POST['longitude'];
        if (isset($_POST['push_id'])) {
            $model->push_id = $_POST['push_id'];
        }
        if (isset($_POST['device'])) {
            if($_POST['device'] == PushHelper::IPHONE OR $_POST['device'] == PushHelper::ANDROID){
                $model->device = $_POST['device'];
            }
        }
        if (isset($_POST['latitude']) AND isset($_POST['longitude'])) {
            $lat = $_POST['latitude'];
            $long = $_POST['longitude'];
            $address = User::getAddress($lat, $long);
            $model->address = $address['country'];;
            $model->state = $address['state'];
            $model->city = $address['city'];
        }
        $model->premium = User::NOT_PREMIUM;
        if ($model->save()) {
            if( count($_FILES['images'])>0 AND $_FILES['images']['tmp_name'] ) {
                User::savePhotoRegister((array)$_FILES['images'], $model->id);
            }
            //create swipe setting
            if(isset($_POST['swipe_gender'])){
                if($_POST['swipe_gender'] == 'both'){ $gender = User::GENDER_OTHER;}
                if($_POST['swipe_gender'] == 'male'){ $gender = User::GENDER_MALE;}
                if($_POST['swipe_gender'] == 'female'){ $gender = User::GENDER_FEMALE;}
                if($gender == User::GENDER_OTHER OR $gender == User::GENDER_MALE OR $gender == User::GENDER_FEMALE){
                    Like::createSwipeSettingSignup($model->id, $gender, $model->latitude, $model->longitude, $model->birthday);
                }
            }
            //client setting
            ClientSetting::create($model->id);
            //friend pluzo team and indicator
            Friend::addPluzoTeam($model->id);            
            //send msg from Pluzo Team
            $msg = Chat::signupMsg($model->id);
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

    public function actionUserReport() {
        if(!$_POST['user_id']){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        if(!$_POST['reason']){
            throw new \yii\web\HttpException('500','reason cannot be blank.'); 
        }
        $msg = '';
        if (isset($_POST['msg'])) {
            $msg = $_POST['msg'];
        }
        return Stream::streamReport(Stream::REPORT_USER, $_POST['user_id'], $_POST['reason'], $msg, \Yii::$app->user->id);
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
