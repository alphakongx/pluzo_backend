<?php

namespace api\models;

use Yii;
use api\models\StreamAsk;

/**
 * This is the model class for table "stream_ask".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $created_at
 * @property int $status
 * @property string|null $channel_id
 */
class StreamAsk extends \yii\db\ActiveRecord
{   
    const __REQUEST_SENT__ = 1;
    const __REQUEST_ACCEPT__ = 2;
    const __REQUEST_REFUSED__ = 3;
    const __REQUEST_DISCONNECT__ = 4;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'stream_ask';
    }

    public static function checkIfHost($channel_id){
        $check = Stream::find()->where(['channel_id'=>$channel_id, 'user_id'=>\Yii::$app->user->id])->one();
        if (!$check) {
                throw new \yii\web\HttpException('500','You are not HOST of this stream'); 
        }
    }
    

    public static function checkAsk($action, $channel_id, $user_id){
        /*$check = StreamAsk::find()->where(['channel_id'=>$channel_id, 'user_id'=>$user_id])->andwhere(['in', 'status', [self::__REQUEST_SENT__,self::__REQUEST_ACCEPT__]])->one();
        if($check){
            if ($check->status == self::__REQUEST_SENT__) {
                throw new \yii\web\HttpException('500','You already sent request'); 
            }
            if ($check->status == self::__REQUEST_ACCEPT__) {
                throw new \yii\web\HttpException('500','User already join as broadcaster'); 
            }    
            if ($check->status == self::__REQUEST_REFUSED__) {
                throw new \yii\web\HttpException('500','You were denied'); 
            }           
        } else {*/
            if($action == 'Stream_user_ask_join'){
                $stream = Stream::find()->where(['channel'=>$channel_id])->one();
                if ($stream) {
                    $host = $stream->user_id;
                } else {
                    throw new \yii\web\HttpException('500','Channel not exist'); 
                }
            } else {
                $host = \Yii::$app->user->id;
            }
            /*$create = new StreamAsk();
            $create->created_at=time();
            $create->status = self::__REQUEST_SENT__;
            $create->user_id = $user_id;
            $create->channel_id = $channel_id;
            if($create->save()){*/
                $result = [
                    'host'=>Stream::userForApi($host),
                    'user'=>Stream::userForApi($user_id),
                    'stream'=>$channel_id,
                ];
                User::socket($user_id, $result, $action);
                return $result;               
            /*} else {
                throw new \yii\web\HttpException('500','Error save StreamAsk'); 
            }*/
        
    }

    public static function changeStatus($channel_id, $user_id, $status){
        $check = StreamAsk::find()->where(['channel_id'=>$channel_id, 'user_id'=>$user_id])->one();
        if($check){
            if ($check->status != self::__REQUEST_SENT__) {
                throw new \yii\web\HttpException('500','User not have request for join as broadcaster'); 
            }
            $check->status = $status;
            $check->save();
        } else {
            throw new \yii\web\HttpException('500','Request not exist'); 
        }
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'status'], 'integer'],
            [['created_at', 'channel_id'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'created_at' => 'Created At',
            'status' => 'Status',
            'channel_id' => 'Channel ID',
        ];
    }
}
