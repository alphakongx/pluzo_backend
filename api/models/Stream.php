<?php

namespace api\models;

use Yii;

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
    const AGORA_KEY = 'YmVkZDBjNmU0NmM5NGY2NmIzZTJmNWRjMzI0ZjlhYzc6NDFmZWZlNDQyZWJmNDlhZjg3OGJmZGZkMWNlMjQyMmY=';
    //ZDYyODNiNjcyZTRmNDE0NWE5OTgwOWU0Yjg0ZDkxNWM6ZjViMmMzOWFlMzUxNDk4MGExY2E2NzMyNDYyNzVlMDI=
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream';
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
        if (count($agora_stream) > 0) {
            $ar = [];
            for ($i=0; $i < count($agora_stream); $i++) { 
                array_push($ar, $agora_stream[$i]['channel_name']);
            }
            $all_stream = Stream::find()->where(['in', 'channel', $ar])->all();
            $friends_stream = Stream::find()->where(['in', 'channel', $ar])->andwhere(['in', 'user_id', $friends])->all();
        } else {
            //$all_stream = Stream::find()->all();
            //$friends_stream = Stream::find()->where(['in', 'user_id', $friends])->all();
            $all_stream = [];
            $friends_stream = [];
        }
        return [
            'friends_stream' => $friends_stream,
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
        curl_setopt($ch, CURLOPT_URL, 'https://api.agora.io/dev/v1/channel/8fb9cd7b72694baa9a048ee3dc4633d7');
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

    public static function getUserChannelList($channel){

        $headers = array(
            'Content-Type: application/json',
            'Authorization: Basic '.Stream::AGORA_KEY
        );

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, 'https://api.agora.io/dev/v1/channel/user/8fb9cd7b72694baa9a048ee3dc4633d7/'.$channel);
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
            'name' => 'name', 
            'user' => 'user', 
            'count' => 'count',  
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
}
