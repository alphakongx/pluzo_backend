<?php

namespace api\models;

use Yii;
use api\models\UserMsg;
use yii\helpers\ArrayHelper;
use api\models\Like;
use api\models\User;
use api\models\Friend;
use api\models\StreamBan;
use api\models\Advance;
use api\models\LiveSetting;

/**
 * This is the model class for table "stream".
 *
 * @property int $id
 * @property int $user_id
 * @property string|null $channel
 * @property string|null $created_at
 */
class Stream extends \yii\db\ActiveRecord
{   
    const REPORT_STREAM = 1;
    const REPORT_USER = 2;
    const INVITE_ONLY = 1;
    const AGORA_KEY = 'YmVkZDBjNmU0NmM5NGY2NmIzZTJmNWRjMzI0ZjlhYzc6NDFmZWZlNDQyZWJmNDlhZjg3OGJmZGZkMWNlMjQyMmY=';
  //const AGORA_KEY = 'YWQ0YjM1OGFkMzkyNDU3OWJkNmYwYTM0ZTZkOGY5MDg3NDEzNDA4NWUwN2M0MDFiYTM5NzU4NGUzZGNiNjYwYg==';



   const AGORA_CHANNEL = '8fb9cd7b72694baa9a048ee3dc4633d7';
 

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream';
    }

    public static function getSearch($request)
    {  
        $stream = Stream::find()
        ->where(['like', 'name', '%'.$request.'%', false])
        ->andWhere(['not in', 'user_id', User::bannedUsers()])
        ->andWhere(['not in', 'user_id', User::whoBannedMe()])
        ->all();
        return $stream;
    }

    public static function getStreamName($channel)
    {  
        $stream = Stream::find()->where(['channel'=>$channel])->one();
        if($stream){
            return $stream->name;
        } else {
            return NULL;
        }
    }

    public static function newPps()
    {   
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `client`.`id` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id);
        $result = $command->queryAll();
        $ar = [0];
        foreach ($result as $key => $value) {
            array_push($ar, $value['id']);
        }

        //hide users you sent friend request
        $friend_request = Friend::find()->select('user_target_id')->where(['user_source_id'=>\Yii::$app->user->id])->asArray()->all();
        $sent_friend = [0];
        foreach ($friend_request as $key => $value) {
            array_push($sent_friend, $value['user_target_id']);
        }

        return SearchUserPpl::find()->where(['<>','id', \Yii::$app->user->id,])
        ->andWhere(['status'=>1])
        ->andWhere(['not in', 'id', $ar])
        ->andWhere(['not in', 'id', $sent_friend])
        ->andWhere(['not in', 'id', User::bannedUsers()])
        ->andWhere(['not in', 'id', User::whoBannedMe()])
        ->orderBy(['created_at' => SORT_DESC])
        ->limit(40)
        ->all();
    }
    
    public static function streamReport($type, $channel_name, $reason, $msg, $user_id){

        if ($type == self::REPORT_STREAM) {
            $stream = Stream::find()->where(['channel'=>$channel_name])->one();
            if(!$stream){
                throw new \yii\web\HttpException('500','Stream not exist'); 
            }
        }

        if ($type == self::REPORT_USER) {
            $stream = User::find()->where(['id'=>$channel_name])->one();
            if(!$stream){
                throw new \yii\web\HttpException('500','User not exist'); 
            }
        }
        
        $check = Report::find()->where(['user_id'=>$user_id, 'channel'=>$stream->id, 'type'=>$type])->one();
        if($check){
            throw new \yii\web\HttpException('500','You already sent report'); 
        }
        $report = new Report();
        $report->user_id = $user_id;
        $report->type = $type;
        $report->channel = $stream->id;
        $report->reason = $reason;
        $report->msg = $msg;
        $report->time = time();
        if($report->save()){
            return $report;
        } else {
            print_r($report->errors);
            die();
            throw new \yii\web\HttpException('500','Error save'); 
        }
    }

    public static function isLive($channel_id){
        $agora_stream = Stream::getChannelList();
        $agora = 0;
        foreach ($agora_stream as $key => $value) {
            if ($value['channel_name'] == $channel_id) {
                $agora = 1;
            }
        }
        if ($agora) {
            $stream = Stream::find()->where(['channel'=>$channel_id])->one(); 
            if ($stream) {
                return true;
            }
        }
        return false;
    }

       public static function getStream(){
        $agora_stream = Stream::getChannelList();
        $friends_stream = '';
        $all_stream = '';
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

        //sorted by user count
        $sql_top = 'SELECT *, `stream`.`id` as `search_id` , COUNT(`stream_user`.`channel`) as `user_count` FROM `stream_user`
            LEFT JOIN `stream` ON `stream`.`channel` = `stream_user`.`channel`
            GROUP BY `stream_user`.`channel`
            ORDER BY `user_count` DESC
            LIMIT 20';

        $command = $connection->createCommand($sql_top);
        $result = $command->queryAll();  
        $sorted_top = [];
        foreach ($result as $key => $value) {
            if($value['search_id']){
                array_push($sorted_top, $value['search_id']);
            }
        }

        //boost users
        $boost = Advance::getBoostUsers(Advance::BOOST_TYPE_LIVE);

        if (count($agora_stream) > 0) {
            $ar = [];
            for ($i=0; $i < count($agora_stream); $i++) { 
                array_push($ar, $agora_stream[$i]['channel_name']);
            }

            //broadcaster address
            $host_country = LiveSetting::getLiveSetting();

            if ($host_country->country == 'Worldwide') {
                 
                $all_stream = Stream::find()
                ->where(['in', 'channel', $ar])
                ->andWhere(['not in', 'user_id', User::bannedUsers()])
                ->andWhere(['not in', 'user_id', User::whoBannedMe()])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->orderBy([new \yii\db\Expression('FIELD (stream.user_id, ' . implode(',', $boost) . ')')])
                ->all();

                if(count($sorted_top) > 0){
                    $trending_list = Stream::find()
                    ->where(['in', 'channel', $ar])
                    ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                    ->andWhere(['not in', 'user_id', User::bannedUsers()])
                    ->andWhere(['not in', 'user_id', User::whoBannedMe()])
                    ->orderBy([new \yii\db\Expression('FIELD (id, ' . implode(',', $sorted_top) . ')')])
                    ->limit(10)
                    ->all();
                                   
                } else {
                    $trending_list = [];
                }
                $friends_stream = Stream::find()->where(['in', 'channel', $ar])
                ->andwhere(['in', 'user_id', $friends])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->andWhere(['not in', 'user_id', User::bannedUsers()])
                ->andWhere(['not in', 'user_id', User::whoBannedMe()])
                ->orderBy([new \yii\db\Expression('FIELD (user_id, ' . implode(',', $boost) . ')')])
                ->all(); 
            } elseif($host_country->country == 'United States') {

                $all_stream = Stream::find()
                ->leftJoin('client', 'stream.user_id = client.id ')
                ->where(['in', 'channel', $ar])
                ->andWhere(['client.address'=>$host_country->country])
                ->andWhere(['client.state'=>$host_country->state])
                ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->orderBy([new \yii\db\Expression('FIELD (stream.user_id, ' . implode(',', $boost) . ')')])
                ->all();

                if(count($sorted_top) > 0){
                    $trending_list = Stream::find()
                    ->leftJoin('client', 'stream.user_id = client.id ')
                    ->where(['in', 'channel', $ar])
                    ->andWhere(['client.address'=>$host_country->country])
                    ->andWhere(['client.state'=>$host_country->state])
                    ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                    ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                    ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                    ->orderBy([new \yii\db\Expression('FIELD (stream.id, ' . implode(',', $sorted_top) . ')')])
                    ->limit(10)
                    ->all();
                                   
                } else {
                    $trending_list = [];
                }
                $friends_stream = Stream::find()
                ->leftJoin('client', 'stream.user_id = client.id ')
                ->where(['in', 'channel', $ar])
                ->andWhere(['client.address'=>$host_country->country])
                ->andWhere(['client.state'=>$host_country->state])
                ->andwhere(['in', 'stream.user_id', $friends])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                ->orderBy([new \yii\db\Expression('FIELD (stream.user_id, ' . implode(',', $boost) . ')')])
                ->all(); 

            } else {
                $all_stream = Stream::find()
                ->leftJoin('client', 'stream.user_id = client.id ')
                ->where(['in', 'channel', $ar])
                ->andWhere(['client.address'=>$host_country->country])
                ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->orderBy([new \yii\db\Expression('FIELD (stream.user_id, ' . implode(',', $boost) . ')')])
                ->all();

                if(count($sorted_top) > 0){
                    $trending_list = Stream::find()
                    ->leftJoin('client', 'stream.user_id = client.id ')
                    ->where(['in', 'channel', $ar])
                    ->andWhere(['client.address'=>$host_country->country])
                    ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                    ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                    ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                    ->orderBy([new \yii\db\Expression('FIELD (stream.id, ' . implode(',', $sorted_top) . ')')])
                    ->limit(10)
                    ->all();
                                   
                } else {
                    $trending_list = [];
                }
                $friends_stream = Stream::find()
                ->leftJoin('client', 'stream.user_id = client.id ')
                ->where(['in', 'channel', $ar])
                ->andWhere(['client.address'=>$host_country->country])
                ->andwhere(['in', 'stream.user_id', $friends])
                ->andWhere(['<>','invite_only', self::INVITE_ONLY])
                ->andWhere(['not in', 'stream.user_id', User::bannedUsers()])
                ->andWhere(['not in', 'stream.user_id', User::whoBannedMe()])
                ->orderBy([new \yii\db\Expression('FIELD (stream.user_id, ' . implode(',', $boost) . ')')])
                ->all(); 
            }

        } else {
            $all_stream = [];
            $trending_list = [];
            $friends_stream = [];
        }
        return [
            'friends_stream' => $friends_stream,
            'trending_list' => $trending_list,
            'all_stream' => $all_stream
        ];
    }
    public static function getChannelList(){

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '.Stream::AGORA_KEY
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, 'https://api.agora.io/dev/v1/channel/'.self::AGORA_CHANNEL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $data = curl_exec($ch);
        $data = json_decode($data, true);
        if($data['success'] == 1){
            return $data['data']['channels'];
        }
        return false;
        //return $data;
    }

    public static function streamInfo($channel){
        $stream = Stream::find()->where(['channel'=>$channel])->one();
            $stream = ArrayHelper::toArray($stream, [
                'api\models\UserMsg' => [
                    'id',
                    'channel',    
                    'category',
                    'invite_only',
                    'user' => 'user', 
                    'count'=>function(){ 
                        return StreamUser::find()->where(['channel'=>$channel])->count();
                    },  
                    'info' => 'info',
                    'boost_end_time' => function(){ 
                        return Stream::boostEndTime($channel);
                    },  
                ],
            ]);    
        return $stream;
    }

    public static function boostEndTime($id){
        $end_time = 0;
        $time_diff = time() - Advance::BOOST_LIVE_TIME;
        $check = Advance::find()->where(['type'=>Advance::BOOST, 'boost_type'=>Advance::BOOST_TYPE_LIVE, 'status'=>Advance::ITEM_USED, 'channel_id'=>$id])
        ->andwhere(['>=', 'used_time', $time_diff])
        ->orderBy('used_time DESC')
        ->one();
        if ($check) {
            return $check->used_time + Advance::BOOST_LIVE_TIME;
        } else {
            return 0;
        }  
    }

    public static function userForApi($id){
        $user = UserMsg::find()->where(['id'=>$id])->one();
            $user = ArrayHelper::toArray($user, [
                'api\models\UserMsg' => [
                    '_id' => 'id',
                    'username',    
                    'first_name',
                    'last_name',  
                    //'phone',
                    'status',
                    'gender',
                    'image',
                    'age'=>function($user){ 
                        return User::getAge($user->birthday);
                    },
                    'address',
                    'city',
                    'state',
                    'bio',
                    'last_activity'=>'last_activity',
                    'images',
                    'premium'=>function($user){ 
                        return User::checkPremium($user->id);
                    },
                    
                    'badges'=>function($user){ 
                        return Badge::getBadge($user->id);
                    },
                    'likes'=>function($user){ 
                        return Like::getLike($user->id);
                    },
                    'advanced'=>function($user){ 
                        return User::getAdvanced($user->id);
                    },
                    'user_setting'=>function($user){ 
                        return ClientSetting::getSetting($user->id);
                    },
                    //'first_login',
                    'premium_info'=>function(){ 
                        return User::getPremiumInfo();
                    },
                    'hide_location',
                    'hide_city',
                ],
            ]);    
        return $user;
    }

    public static function getUserChannelList($channel){

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '.Stream::AGORA_KEY
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, 'https://api.agora.io/dev/v1/channel/user/'.self::AGORA_CHANNEL.'/'.$channel);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);
        $data = curl_exec($ch);
        $data = json_decode($data, true);

        if($data['data']['channel_exist'] == 1){
            $broadcasters = $data['data']['broadcasters'];
            $audience = $data['data']['audience'];
            $broadcasters = UserMsg::find()->where(['IN', 'id', $broadcasters])->all();
            $audience = UserMsg::find()->where(['IN', 'id', $audience])->all();
            return [
                'broadcasters'=>$broadcasters,
                'audience'=>$audience,
            ];
        } else {
            throw new \yii\web\HttpException('500','Channel not exist');
        }        
    }

    public static function getUsers(){
        return Stream::find()->all();
    }

    public function fields()
    {
        return [
            'id' => 'id',
            'channel' => 'channel', 
            'category' => 'category', 
            'invite_only' => 'invite_only', 
            'name' => 'name', 
            'user' => 'user', 
            'count' => 'count',  
            'info' => 'info',
            'ban_list' => function(){
                return StreamBan::find()->where(['channel_id'=>$this->channel])->all();
            },
            'boost_end_time' => function(){ 
                return Stream::boostEndTime($this->channel);
            }, 
        ];
    }

    public function getUser()
    {   
        return $this->hasOne(UserMsg::className(), ['id' => 'user_id']);        
    }

    public function getCount()
    {   
        return StreamUser::find()->where(['channel'=>$this->channel])->count();        
    }

    public function getInfo()
    {   
        $country_list = [];
        $photos = [];
        $names = [];
        $users = StreamUser::find()->where(['channel'=>$this->channel])->all();
        foreach ($users as $key => $value) {
            $country = User::getCountry($value['user_id']);
            if($country){
                if (in_array($country, $country_list)) {
                } else {
                    array_push($country_list, $country);
                }
            }
            if ($value['type'] == 1) {
                $photo = User::getPhoto($value['user_id']);
                if($photo){
                    array_push($photos, $photo);
                }
                
                $first_name = User::getFirstName($value['user_id']);
                if($first_name){
                    array_push($names, $first_name);
                }
            }
        }
        return ['country_list'=>$country_list, 'streamers_images'=>$photos, 'names'=>$names];
    }
}
