<?php

namespace api\models;

use Yii;
use api\models\Service;
use api\models\User;
use api\models\Stream;
use api\models\PremiumUse;
use api\models\Like;

/**
 * This is the model class for table "advance".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $created_at
 * @property string|null $expires_at
 * @property string|null $payment_id
 * @property int|null $status
 * @property int|null $type
 */
class Advance extends \yii\db\ActiveRecord
{   
    const BOOST = 1;
    const SUPER_LIKE = 2;
    const REMIND = 3;
    const PLUZO_PLUS = 4;
    const SERVICE_PLUZO_PLUS = 10;
    const SERVICE_PLUZO_PLUS_3_MONTH = 11;
    const SERVICE_PLUZO_PLUS_12_MONTH = 12;
    const ITEM_AVAILABILITY = 1; 
    const ITEM_USED = 2;

    const BOOST_TYPE_SWIPE = 1;
    const BOOST_TYPE_LIVE = 2;
    const BOOST_SWIPE_TIME = 600;
    const BOOST_LIVE_TIME = 300;

    const DURATION_PLUZO_PLUS_1_MONTH = 2419200;
    const DURATION_PLUZO_PLUS_3_MONTH = 7257600;
    const DURATION_PLUZO_PLUS_12_MONTH = 29030400;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'advance';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'status', 'type'], 'integer'],
            [['created_at', 'expires_at', 'payment_id', 'used_time'], 'safe'],
        ];
    }

    public static function runService($type){
        if ($type < 1 OR $type > 3) {
            throw new \yii\web\HttpException('500','Type must be '.Service::BOOST.' or '.Service::SUPER_LIKE.' or '.Service::REMIND); 
        }
        switch ($type) {
            case Service::BOOST:
                return self::useBoost();
                break;
            case Service::SUPER_LIKE:
                return self::useSuper_like();
                break;
            case Service::REMIND:
                return self::useReminder();
                break;
        }
    }

    public static function checkBoostLimit($type, $used_time){
        if ($type == self::BOOST_TYPE_SWIPE) {
            $start_time = $used_time + self::BOOST_SWIPE_TIME;
            return $start_time;
        }

        if ($type == self::BOOST_TYPE_LIVE) {
            $start_time = $used_time + self::BOOST_LIVE_TIME;
            return $start_time;
        }
    }

    //use premium
    public static function addPremiumLimit($type, $time){
        $channel_id = Yii::$app->request->post('channel_id');
        $advance = new Advance();
        $advance->created_at = time();
        $advance->used_time = $time;
        $advance->expires_at = $time + 2419200;
        $advance->payment_id = 'Pluzo Plus';
        $advance->type = self::BOOST;
        $advance->status = self::ITEM_USED;
        $advance->user_id = \Yii::$app->user->id;
        $advance->boost_type = $type;
        $advance->channel_id = $channel_id;
        if($advance->save()){
            $p_use = new PremiumUse();
            $p_use->time = time();;
            $p_use->user_id = \Yii::$app->user->id;
            $p_use->type = self::BOOST;
            $p_use->boost_type = $type;
            $advance->channel_id = $channel_id;
            $p_use->premium_id = $advance->id;
            $p_use->save();

            if($type == Advance::BOOST_TYPE_LIVE){
                self::sendSocketLiveBoost($channel_id);
            }
            $socket = [
                'user'=>Stream::userForApi(\Yii::$app->user->id),
            ];
            User::socket(0, $socket, 'User_update');
            return 'Boost type='.$type.' was used!';
        }
    }

    public static function useBoost($type){
        if($type != self::BOOST_TYPE_SWIPE AND $type != self::BOOST_TYPE_LIVE){
            throw new \yii\web\HttpException('500','Type can be 1(swipe) or 2(live) only');
        }
        $channel_id = Yii::$app->request->post('channel_id');
        //check limit
        $used_time = time();
        if($type == 1){ 
            $time_diff = time() - Advance::BOOST_SWIPE_TIME;
            $check = Advance::find()->where(['type'=>self::BOOST, 'boost_type'=>$type, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_USED])
            ->andwhere(['>=', 'used_time', $time_diff])
            ->orderBy('used_time DESC')
            ->one();
            if ($check) {
                $used_time = self::checkBoostLimit($type, $check->used_time);
            }
        }
        if($type == 2){ 
            $time_diff = time() - Advance::BOOST_LIVE_TIME;
            $check = Advance::find()->where(['type'=>self::BOOST, 'boost_type'=>$type, 'channel_id'=>$channel_id, 'status'=>self::ITEM_USED])
            ->andwhere(['>=', 'used_time', $time_diff])
            ->orderBy('used_time DESC')
            ->one();
            if ($check) {
                $used_time = self::checkBoostLimit($type, $check->used_time);
            }
        }
        //check premium
        $info = User::getPremiumInfo();
        if ($info['premium'] == 1) {
            $pr_limit = $info['swipe_boost_used'] + $info['live_boost_used'];
            if ($type == self::BOOST_TYPE_SWIPE) {
                if($pr_limit >= 5){
                    //use bought
                    if($obj = self::checkPossibleBoost()){
                        $obj->used_time = $used_time;
                        $obj->status = self::ITEM_USED;
                        $obj->boost_type = $type;
                        if($obj->save()){
                            $socket = [
                                'user'=>Stream::userForApi(\Yii::$app->user->id),
                            ];
                            User::socket(0, $socket, 'User_update');
                            return 'Boost type='.$type.' was used!';
                        }
                    } else {
                        $next_upd = $info['boost_reset_date'] - time();
                        throw new \yii\web\HttpException('500','Pluzo+ users get only 5 swipe boosts per month! Next update after '.User::secondsToTime($next_upd).'! Also you can buy more boosts now!');
                    }
                } else {
                    return self::addPremiumLimit($type, $used_time);
                }
            }

            if ($type == self::BOOST_TYPE_LIVE) {
                if($pr_limit >= 5){
                    //use bought
                    if($obj = self::checkPossibleBoost()){
                        $obj->used_time = $used_time;
                        $obj->status = self::ITEM_USED;
                        $obj->boost_type = $type;
                        $obj->channel_id = $channel_id;
                        if($obj->save()){
                            if($type == Advance::BOOST_TYPE_LIVE){
                                self::sendSocketLiveBoost($channel_id);
                            }
                            $socket = [
                                'user'=>Stream::userForApi(\Yii::$app->user->id),
                            ];
                            User::socket(0, $socket, 'User_update');
                            return 'Boost type='.$type.' was used!';
                        }
                    } else {
                        $next_upd = $info['boost_reset_date'] - time();
                        throw new \yii\web\HttpException('500','Pluzo+ users get only 5 live boosts per month! Next update after '.User::secondsToTime($next_upd).'! Also you can buy more boosts now!');
                    }
                } else {
                    return self::addPremiumLimit($type, $used_time);
                }
            }   
        } else {

            if($obj = self::checkPossible(Service::BOOST)){
                $obj->used_time = $used_time;
                $obj->status = self::ITEM_USED;
                $obj->boost_type = $type;
                $obj->channel_id = $channel_id;
                if($obj->save()){
                    if($type == Advance::BOOST_TYPE_LIVE){
                        self::sendSocketLiveBoost($channel_id);
                    }
                    $socket = [
                        'user'=>Stream::userForApi(\Yii::$app->user->id),
                    ];
                    User::socket(0, $socket, 'User_update');
                    return 'Boost type='.$type.' was used!';
                }
            }
        }
        throw new \yii\web\HttpException('500','Some error! Call administrator'); 
    }

    public static function sendSocketLiveBoost($channel_id){
        $socket = [
            'stream'=>Stream::streamInfo($channel_id),
        ];
        User::socket(0, $socket, 'Start_update');
    }

    public static function useSuper_like(){
        $check = Advance::find()->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_AVAILABILITY])->one();
        if($check){
            $check->used_time = time();
            $check->status = self::ITEM_USED;
            if($check->save()){
                $socket = [
                    'user'=>Stream::userForApi(\Yii::$app->user->id),
                ];
                User::socket(0, $socket, 'User_update');
                return true;
            }
        }
        return false; 
    }

    public static function useReminder($user_target_id){

        //if premium
        $info = User::getPremiumInfo();
        if ($info['premium'] == 1) {
                $like = Like::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
                if($like)
                {   
                    \Yii::$app
                        ->db
                        ->createCommand()
                        ->delete('like', ['user_source_id' => \Yii::$app->user->id, 'user_target_id'=>$user_target_id])
                        ->execute(); 

                    if($like->like == Like::SUPER_LIKE){
                        $info = User::getPremiumInfo();
                            $check = PremiumUse::find()
                            ->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id])
                            ->orderBy(['time'=>SORT_DESC])->one();
                            if ($check->id) {
                                \Yii::$app
                                ->db
                                ->createCommand()
                                ->delete('premium_use', ['id' => $check->id])
                                ->execute(); 
                            } else {
                                $check = Advance::find()
                                ->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_USED])
                                ->orderBy(['used_time'=>SORT_DESC])->one();
                                if($check)
                                {
                                    $check->used_time = NULL;
                                    $check->status = self::ITEM_AVAILABILITY;
                                    $check->save();
                                }
                            }
                    } 
                } else {
                    throw new \yii\web\HttpException('500','Like or dislike not exist!');
                }
                $socket = [
                    'user'=>Stream::userForApi(\Yii::$app->user->id),
                ];
                User::socket(0, $socket, 'User_update');
                return 'Rewind was used!';
        }
        if($obj = self::checkPossible(Service::REMIND)){
            $obj->used_time = time();
            $obj->status = self::ITEM_USED;
            if($obj->save()){
                $like = Like::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
                if($like)
                {   
                    \Yii::$app
                        ->db
                        ->createCommand()
                        ->delete('like', ['user_source_id' => \Yii::$app->user->id, 'user_target_id'=>$user_target_id])
                        ->execute(); 
                    if($like->like == Like::SUPER_LIKE){
                        //check premium
                        $info = User::getPremiumInfo();
                        if ($info['premium'] == 1) {
                            $check = PremiumUse::find()
                            ->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id])
                            ->orderBy(['time'=>SORT_DESC])->one();
                            if ($check->id) {
                                \Yii::$app
                                ->db
                                ->createCommand()
                                ->delete('premium_use', ['id' => $check->id])
                                ->execute(); 
                            } else {
                                $check = Advance::find()
                                ->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_USED])
                                ->orderBy(['used_time'=>SORT_DESC])->one();
                                if($check)
                                {
                                    $check->used_time = NULL;
                                    $check->status = self::ITEM_AVAILABILITY;
                                    $check->save();
                                }
                            }
                        } else {
                            $check = Advance::find()
                            ->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_USED])
                            ->orderBy(['used_time'=>SORT_DESC])->one();
                            if($check)
                            {
                                $check->used_time = NULL;
                                $check->status = self::ITEM_AVAILABILITY;
                                $check->save();
                            }
                        }
                    } 
                } else {
                    throw new \yii\web\HttpException('500','Like or dislike not exist!');
                }
                $socket = [
                    'user'=>Stream::userForApi(\Yii::$app->user->id),
                ];
                User::socket(0, $socket, 'User_update');
                return 'Rewind was used!';
            }
        }
        throw new \yii\web\HttpException('500','Some error! Call administrator'); 
    }

    public static function checkPossibleBoost(){
        $check = Advance::find()->where(['type'=>Service::BOOST, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_AVAILABILITY])->one();
        if($check){
            return $check;
        } else {
            return false;
        }
    }

    public static function checkPossible($type){
        $check = Advance::find()->where(['type'=>$type, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_AVAILABILITY])->one();
        if($check){
            return $check;
        } else {
            throw new \yii\web\HttpException('500','You not have this opportunities! You can buy more'); 
        }
    }

    public static function updateItem($pay_id, $service_id, $time)
    {   
        //pluzo plus
        if ($service_id == self::SERVICE_PLUZO_PLUS OR $service_id == self::SERVICE_PLUZO_PLUS_3_MONTH OR $service_id == self::SERVICE_PLUZO_PLUS_12_MONTH) {
            $find = Advance::find()->where(['user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_AVAILABILITY, 'type'=>self::PLUZO_PLUS])->orderBy(['expires_at'=>SORT_DESC])->one();
            if ($find) {
                $used_time = $find->expires_at;
            } else {
                $used_time = $time;
            }   
                if($service_id == self::SERVICE_PLUZO_PLUS){ $time_duration = self::DURATION_PLUZO_PLUS_1_MONTH;}
                if($service_id == self::SERVICE_PLUZO_PLUS_3_MONTH){ $time_duration = self::DURATION_PLUZO_PLUS_3_MONTH;}
                if($service_id == self::SERVICE_PLUZO_PLUS_12_MONTH){ $time_duration = self::DURATION_PLUZO_PLUS_12_MONTH;}
                    $advance = new Advance();
                    $advance->created_at = $time;
                    $advance->used_time = $used_time;
                    $advance->expires_at = $used_time + $time_duration;
                    $advance->payment_id = $pay_id;
                    $advance->type = self::PLUZO_PLUS;
                    $advance->status = self::ITEM_AVAILABILITY;
                    $advance->user_id = \Yii::$app->user->id;
                    $advance->save();
        } else {
            $service = Service::find()->where(['id'=>$service_id])->one();
            if($service){
                for ($i=0; $i < $service->count; $i++) { 
                    $advance = new Advance();
                    $advance->created_at = $time;
                    $advance->expires_at = $advance->created_at + $service->during;
                    $advance->payment_id = $pay_id;
                    $advance->type = $service->type;
                    $advance->status = self::ITEM_AVAILABILITY;
                    $advance->user_id = \Yii::$app->user->id;
                    $advance->save();
                }
            }
        }
    }

    public static function getBoostUsers($boost_type){
        if ($boost_type == self::BOOST_TYPE_SWIPE) {
            $time_dif = time() - self::BOOST_SWIPE_TIME;
        }
        if ($boost_type == self::BOOST_TYPE_LIVE) {
            $time_dif = time() - self::BOOST_LIVE_TIME;
        }
        
        $boost = Advance::find()
        ->where(['type'=>self::BOOST, 'boost_type'=>$boost_type, 'status'=>self::ITEM_USED])
        ->andwhere(['>','used_time', $time_dif])
        ->orderBy(['used_time'=>SORT_ASC])
        ->all();
        $array = [0];
        foreach ($boost as $key => $value) {
            array_push($array, $value['user_id']);
        }

        return $array;
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
            'expires_at' => 'Expires At',
            'payment_id' => 'Payment ID',
            'status' => 'Status',
            'type' => 'Type',
        ];
    }
}
