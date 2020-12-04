<?php

namespace api\models;

use Yii;
use api\models\UserMsg;
use yii\helpers\ArrayHelper;
use api\models\Like;
use api\models\User;
use api\models\Friend;
use api\models\StreamBan;

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

        return SearchUserPpl::find()->where(['<>','id', \Yii::$app->user->id])
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
            throw new \yii\web\HttpException('500','Error save'); 
        }
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
            LIMIT 10';

        $command = $connection->createCommand($sql_top);
        $result = $command->queryAll();  
        $sorted_top = [];
        foreach ($result as $key => $value) {
            if($value['search_id']){
                array_push($sorted_top, $value['search_id']);
            }
        }
        if (count($agora_stream) > 0) {
            $ar = [];
            for ($i=0; $i < count($agora_stream); $i++) { 
                array_push($ar, $agora_stream[$i]['channel_name']);
            }
            
            $all_stream = Stream::find()->where(['in', 'channel', $ar])
            ->andWhere(['not in', 'user_id', User::bannedUsers()])
            ->andWhere(['not in', 'user_id', User::whoBannedMe()])
            ->andWhere(['<>','invite_only', self::INVITE_ONLY])->all();

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
            ->all(); 

        } else {
            //$all_stream = Stream::find()->all();
            //$friends_stream = Stream::find()->where(['in', 'user_id', $friends])->all();
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
                ],
            ]);    
        return $stream;
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
                    'city',
                    'state',
                    'last_activity',
                    'bio',
                    'images',
                    'premium'=>function($user){ 
                        return User::checkPremium($user->id);
                    },
                    'friends'=>function($user){ 
                        return User::friendCount($user->id);
                    },
                    'badges'=>function($user){ 
                        return Badge::getBadge($user->id);
                    },
                    'likes'=>function($user){ 
                        return Like::getLike($user->id);
                    },
                    'first_login',
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
