<?php

namespace api\models;

use Yii;
use api\models\Service;
use api\models\User;

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

    public static function useBoost($type){
        if($type != self::BOOST_TYPE_SWIPE AND $type != self::BOOST_TYPE_LIVE){
            throw new \yii\web\HttpException('500','Type can be 1(swipe) or 2(live) only');
        }

        if(User::checkPremium(\Yii::$app->user->id)){
            $check = Advance::find()->where(['type'=>self::BOOST, 'boost_type'=>$type, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_USED])->orderBy('used_time DESC')->one();
            if($check){
                $time_dif = time() - $check->used_time;
                $time_left = 86400 - $time_dif;
                if($time_dif < 86400){
                    throw new \yii\web\HttpException('500','You can use BOOST only 1 time of day! '.$time_left.' seconds left');
                }
            }
            $advance = new Advance();
            $advance->created_at = time();
            $advance->used_time = time();
            $advance->expires_at = time() + 2419200;
            $advance->payment_id = 'Pluzo Plus';
            $advance->type = self::BOOST;
            $advance->status = self::ITEM_USED;
            $advance->user_id = \Yii::$app->user->id;
            $advance->boost_type = $type;
            if($advance->save()){
                return 'Boost type='.$type.' was used!';
            }
        } else {
            if($obj = self::checkPossible(Service::BOOST)){
                $obj->used_time = time();
                $obj->status = self::ITEM_USED;
                $obj->boost_type = $type;
                if($obj->save()){
                    return 'Boost type='.$type.' was used!';
                }
            }
        }
        throw new \yii\web\HttpException('500','Some error! Call administrator'); 
    }

    public static function useSuper_like(){
        $check = Advance::find()->where(['type'=>self::SUPER_LIKE, 'user_id'=>\Yii::$app->user->id, 'status'=>self::ITEM_AVAILABILITY])->one();
        if($check){
            $check->used_time = time();
            $check->status = self::ITEM_USED;
            if($check->save()){
                return true;
            }
        }
        return false; 
    }

    public static function useReminder(){
        if($obj = self::checkPossible(Service::REMIND)){
            $obj->used_time = time();
            $obj->status = self::ITEM_USED;
            if($obj->save()){
                return 'Remind was used!';
            }
        }
        throw new \yii\web\HttpException('500','Some error! Call administrator'); 
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
                if($service_id == self::SERVICE_PLUZO_PLUS){ $time_duration = 2419200;}
                if($service_id == self::SERVICE_PLUZO_PLUS_3_MONTH){ $time_duration = 7257600;}
                if($service_id == self::SERVICE_PLUZO_PLUS_12_MONTH){ $time_duration = 29030400;}
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
        $boost = Advance::find()->where(['type'=>self::BOOST, 'boost_type'=>$boost_type, 'status'=>self::ITEM_USED])->orderBy(['used_time'=>SORT_ASC])->all();
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
