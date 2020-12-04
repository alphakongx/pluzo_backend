<?php

namespace api\models;

use Yii;
use api\models\UserMsg;
use api\models\User;
use api\models\Stream;
use api\models\Like;
use api\models\Chat;
use api\models\Indicator;
use api\models\MessageHide;

/**
 * This is the model class for table "friend".
 *
 * @property int $id
 * @property int $user_source_id
 * @property int $user_target_id
 * @property string|null $created_at
 */
class Friend extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'friend';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_source_id', 'user_target_id'], 'required'],
            [['user_source_id', 'user_target_id', 'show'], 'integer'],
            [['created_at'], 'safe'],
        ];
    }

    public static function addPluzoTeam($id)
    {  
        $new = new Indicator();
        $new->time = $time;
        $new->user_current_id = 0;
        $new->user_target_id = $id;
        $new->type = Indicator::__TYPE_LIKE__;
        $new->status = Indicator::__NEW_STATUS__;
        $new->save();

        $new = new Indicator();
        $new->time = $time;
        $new->user_current_id = $id;
        $new->user_target_id = 0;
        $new->type = Indicator::__TYPE_LIKE__;
        $new->status = Indicator::__NEW_STATUS__;
        $new->save();
    }

    public function getSearch($request)
    {   
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT ".User::userFields()." FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id." AND `client`.`username` Like '%".$request."%'");
        $result = $command->queryAll();
        $friend = [];
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

            $images = $command = $connection->createCommand("SELECT `images`.`id`, `images`.`path`  FROM `images` WHERE `user_id`=".$value['id']." ORDER BY  `sort` ASC");
            $result_images = $command->queryAll();
            $ar = [
                'id'=>$value['id'],
                'username'=>$value['username'],
                'phone'=>$value['phone'],
                'image'=>$value['image'],
                'gender'=>$value['gender'],
                'birthday'=>$value['birthday'],
                'status'=>$value['status'],
                'first_name'=>$value['first_name'],
                'last_name'=>$value['last_name'],
                'latitude'=>$value['latitude'],
                'longitude'=>$value['longitude'],
                'address'=>$value['address'],
                'city'=>$value['city'],
                'state'=>$value['state'],
                'last_activity'=>$value['last_activity'],
                'premium'=>User::checkPremium($value['id']),
                'images'=>$result_images,
                'friends'=>User::friendCount($value['id']),
                'badges'=>Badge::getBadge($value['id']),
                'first_login'=>$value['first_login'],
                //'likes'=>Like::getLike($value['id']),
            ];
            array_push($friend, $ar);
        }
        return $friend;
    }

    
    public function readFlag($user_id)
    {
        $ind = Indicator::find()->where(['user_current_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_id, 'status'=>Indicator::__NEW_STATUS__])->one();
        if($ind){
            $ind->status = Indicator::__READ_STATUS__;
            if($ind->save()){
                return $ind;
            } else {
                throw new \yii\web\HttpException('500','error update'); 
            }
        } else {
            throw new \yii\web\HttpException('500','Flag not exist.'); 
        }
    }

    public function friendRemove($request)
    {   
        $user_target_id = $request->post('user_target_id');
        if(!isset($user_target_id)){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }


        if($user_target_id == 0){
            $chat_id = Chat::getChatUser($user_target_id);
            if ($chat_id['chat_id']) {

                $hideMsg = MessageHide::find()->where(['chat_id'=>$chat_id['chat_id'], 'user_id'=>\Yii::$app->user->id])->one();
                if($hideMsg){
                    $hideMsg->time = time();
                } else {
                    $hideMsg = new MessageHide();
                    $hideMsg->time = time();
                    $hideMsg->chat_id = $chat_id['chat_id'];
                    $hideMsg->user_id = \Yii::$app->user->id;
                }
                $hideMsg->save();
                return 'Chat with Pluzo Team deleted!';
            } else {
                throw new \yii\web\HttpException('500','You not have chat with this user');
            }
        }

        if($user_target_id > 0){
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_source_id' => \Yii::$app->user->id, 'user_target_id' => $user_target_id])
            ->execute();
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_source_id' => $user_target_id, 'user_target_id' => \Yii::$app->user->id])
            ->execute();

            //delete party, chat, messages
            $chat_id_with_user = Chat::getChatUser($user_target_id);
            if($chat_id_with_user['chat_id']){
                \Yii::$app
                ->db
                ->createCommand()
                ->delete('chat', ['id' => $chat_id_with_user['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('party', ['chat_id' => $chat_id_with_user['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('message', ['chat_id' => $chat_id_with_user['chat_id']])
                ->execute();
            }

            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user_target_id'=>Stream::userForApi($user_target_id),
                'friend_info'=>self::isFriend($user_target_id),
            ];
            User::socket($user_target_id, $result, 'Friend_remove');
            return $result;
        }
    }   

    public function isFriend($user_target_id)
    {   
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }

        $you_request_to_user = Friend::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        $user_request_to_you = Friend::find()->where(['user_target_id'=>\Yii::$app->user->id, 'user_source_id'=>$user_target_id])->one();
        $friend = 0;
        $hint = '';
        if(!$user_request_to_you->id AND !$you_request_to_user->id){
            $friend = 1;
            $hint = 'Not request from user and to user';
        }
        if($user_request_to_you->id AND !$you_request_to_user->id){
            $friend = 2;
            $hint = 'User sent request to you and waiting answer';
        }
        if($you_request_to_user->id AND !$user_request_to_you->id){
            $friend = 3;
            $hint = 'You sent request to user and waiting answer';
        }
        if($you_request_to_user->id AND $user_request_to_you->id){
            $friend = 4;
            $hint = 'Cross request = friends';
        }
        return ['friend' => $friend, 'hint'=>$hint];
    }

    public function friendRequestsToMeReject($request)
    { 
        $user_target_id = (int)$request->post('user_target_id');
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        $friend = Friend::find()->where(['user_source_id'=>$user_target_id, 'user_target_id'=>\Yii::$app->user->id])->one();
        if ($friend) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['id' => $friend->id])
            ->execute();
            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user_target_id'=>Stream::userForApi($user_target_id),
                'friend_info'=>self::isFriend($user_target_id),
            ];
            User::socket($user_target_id, $result, 'Friend_cancel_to_me_request');
            return $result;
        } else {
            throw new \yii\web\HttpException('500', 'Request for '.$user_target_id.' not found!');
        }
    }
    
    
    public function friendRequestsReject($request)
    { 
        $user_target_id = (int)$request->post('user_target_id');
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        $friend = Friend::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        if ($friend) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['id' => $friend->id])
            ->execute();
            $result = [
                'host'=>Stream::userForApi(\Yii::$app->user->id),
                'user_target_id'=>Stream::userForApi($user_target_id),
                'friend_info'=>self::isFriend($user_target_id),
            ];
            User::socket($user_target_id, $result, 'Friend_cancel_request');
            return $result;
        } else {
            throw new \yii\web\HttpException('500', 'Request for '.$user_target_id.' not found!');
        }
    }

    public static function likeOverlapFriends($user_target_id)
    {
        $friend = Friend::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        $time = time();
        if(!$friend){
            $friend = new Friend();
            $friend->user_source_id = \Yii::$app->user->id;
            $friend->user_target_id = $user_target_id;
            $friend->created_at = $time;
            $friend->show = 1;
            $friend->save();
        }

        $friend2 = Friend::find()->where(['user_source_id'=>$user_target_id, 'user_target_id'=>\Yii::$app->user->id])->one();
        if(!$friend2){
            $friend2 = new Friend();
            $friend2->user_source_id = $user_target_id;
            $friend2->user_target_id = \Yii::$app->user->id;
            $friend2->created_at = $time;
            $friend2->show = 1;
            $friend2->save();
        }

            $result = [
                    'host'=> Stream::userForApi(\Yii::$app->user->id),
                    'user_target_id'=> Stream::userForApi($user_target_id),
            ];
            User::socket($user_target_id, $result, 'Friend_overlap'); 

    }

    public function addFriend($user_target_id)
    { 
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        if($user_target_id == \Yii::$app->user->id){
            throw new \yii\web\HttpException('500','user_target_id can not be your ID'); 
        }
        $check = User::find()->where(['id'=>$user_target_id])->one();
        if(!isset($check)){
            throw new \yii\web\HttpException('500','User not exist');
        }

        $time = time();
        $friend = Friend::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        if($friend){
            throw new \yii\web\HttpException('500','Request already exist');
        } else {
            $friend = new Friend();
            $friend->user_source_id = \Yii::$app->user->id;
            $friend->user_target_id = $user_target_id;
            $friend->created_at = $time;
            $friend->show = 1;
            $friend->save();
        }

        $host = Stream::userForApi(\Yii::$app->user->id);
        //check like, super_like
        $indicator = 0;
        $check_like = Like::checkLike($user_target_id);
        $friend_check = Friend::find()->where(['user_source_id'=>$user_target_id, 'user_target_id'=>\Yii::$app->user->id])->one();
        if($check_like AND !$friend_check){
            $friend = new Friend();
            $friend->user_source_id = $user_target_id;
            $friend->user_target_id = \Yii::$app->user->id;
            $friend->created_at = $time;
            $friend->show = 1;
            $friend->save();
            if($friend->save()){
                $result = [
                    'host'=>$host,
                    'user_target_id'=>Stream::userForApi($user_target_id),
                ];
                User::socket($user_target_id, $result, 'Friend_overlap'); 
                $indicator = 1;      
            }
        }
        
        $result = [
            'host'=>$host,
            'user_target_id'=>$user_target_id,
            'friend_info'=>self::isFriend($user_target_id),
        ];
        User::socket($user_target_id, $result, 'Friend_add');

        if($friend_check){
            $result = [
                    'host'=>$host,
                    'user_target_id'=>Stream::userForApi($user_target_id),
            ];
            User::socket($user_target_id, $result, 'Friend_overlap'); 
        }
        if($friend_check OR $indicator == 1){
            Indicator::checkIndicatorExist(\Yii::$app->user->id, $user_target_id, Indicator::__TYPE_LIKE__, Like::LIKE);
        }
        return $result;
    }

    public function friendRequestsMy()
    {   
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `client`.`id` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id);
        $result = $command->queryAll();
        $ar = [];
        foreach ($result as $key => $value) {
            array_push($ar, $value['id']);
        }
        return Friend::find()->with(['user'])->where(['user_source_id'=>\Yii::$app->user->id])
        ->andWhere(['not in', 'user_target_id', $ar])
        ->orderBy('id DESC')->all();
    }

    public function friendRequestsToMe()
    {   
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `client`.`id` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id);
        $result = $command->queryAll();
        $ar = [];
        foreach ($result as $key => $value) {
            array_push($ar, $value['id']);
        }
        $req =  Friend::find()->select(['user_source_id'])->where(['user_target_id'=>\Yii::$app->user->id])
        ->andWhere(['not in', 'user_source_id', $ar])
        ->orderBy('id DESC')->asArray()->all();
        $ar = [];
        foreach ($req as $key => $value) {
            array_push($ar, $value['user_source_id']);
        }
        return UserMsg::find()->where(['in', 'id', $ar])->all();

    }

    public function getFriend($id)
    {   
        $flag =  Indicator::find()->where(['user_current_id'=>\Yii::$app->user->id, 'status'=>Indicator::__NEW_STATUS__])->all();
        $flag_id = [];
        foreach ($flag as $key => $value) {
            array_push($flag_id, $value['user_target_id']);
        }
        if(count($flag_id)){
            $flag_id = implode(',', $flag_id);
            $order_by = 'ORDER BY field(l2.user_source_id,'.$flag_id.') DESC, l2.created_at DESC;';
        } else {
            $order_by = 'ORDER BY l2.created_at DESC';
        }

        $connection = Yii::$app->getDb();
        $sql = "SELECT ".User::userFields().", l2.created_at FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".$id." ".$order_by;

        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        $friend = [];
        foreach ($result as $key => $value) {

            //banned users
            if (in_array($value['id'], User::bannedUsers())) {
                continue;
            }
            if (in_array($value['id'], User::whoBannedMe())) {
                continue;
            }

            $images = $command = $connection->createCommand("SELECT `images`.`id`, `images`.`path`  FROM `images` WHERE `user_id`=".$value['id']." ORDER BY  `sort` ASC");
            $result_images = $command->queryAll();
            $ar = [
                'id'=>$value['id'],
                'username'=>$value['username'],
                'phone'=>$value['phone'],
                'image'=>$value['image'],
                'gender'=>$value['gender'],
                'birthday'=>$value['birthday'],
                'status'=>$value['status'],
                'first_name'=>$value['first_name'],
                'last_name'=>$value['last_name'],
                'latitude'=>$value['latitude'],
                'longitude'=>$value['longitude'],
                'address'=>$value['address'],
                'city'=>$value['city'],
                'state'=>$value['state'],
                'last_activity'=>$value['last_activity'],
                'premium'=>User::checkPremium($value['id']),
                'images'=>$result_images,
                'friends'=>User::friendCount($value['id']),
                'badges'=>Badge::getBadge($value['id']),
                'flag'=>Friend::flag($value['id']),
                'first_login'=>$value['first_login'],
                //'likes'=>Like::getLike($value['id']),
            ];
            array_push($friend, $ar);
        }
        $ar = [
                    'id'=>0,
                    'username'=>'Pluzo Team',
                    'flag'=>Friend::flag(0),
                ];
                array_push($friend, $ar);
        return $friend;
    }

    public function flag($user_target_id){
        $ind = Indicator::find()->where(['user_current_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        if($ind){
            if ($ind->type == Indicator::__TYPE_LIKE__ AND $ind->status == Indicator::__READ_STATUS__) {return 0;}
            if ($ind->type == Indicator::__TYPE_LIKE__ AND $ind->status == Indicator::__NEW_STATUS__) {return 1;}
            if ($ind->type == Indicator::__TYPE_SUPERLIKE__ AND $ind->status == Indicator::__READ_STATUS__) {return 2;}
            if ($ind->type == Indicator::__TYPE_SUPERLIKE__ AND $ind->status == Indicator::__NEW_STATUS__) {return 3;}
        } else {
            return 0;
        }
    }


    public function addFriendUsername($request)
    {
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
        $friend = Friend::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user->id])->one();
        if($friend){
        } else {
            $friend = new Friend();
            $friend->user_source_id = \Yii::$app->user->id;
            $friend->user_target_id = $user->id;
            $friend->created_at = time();
            $friend->show = 1;
            $friend->save();
        }
        $result = [
            'host'=>Stream::userForApi(\Yii::$app->user->id),
            'user_target_id'=>$user->id,
            'friend_info'=>self::isFriend($user->id),
        ];
        User::socket($user->id, $result, 'Friend_add');
        return $result;
    }



    public function fields()
    {
        return [
            //'id' => 'id',
            'user_source_id' => 'user_source_id',
            'user_target_id' => 'user_target_id', 
            'user_info' => 'user',
        ];
    }

    public function getUser()
    {   
        return $this->hasOne(UserMsg::className(), ['id' => 'user_target_id']);        
    }



}
