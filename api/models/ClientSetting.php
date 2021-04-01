<?php

namespace api\models;

use Yii;
use api\models\User;

/**
 * This is the model class for table "client_setting".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $push_new_friend
 * @property int|null $push_friend_request
 * @property int|null $push_live
 * @property int|null $push_message
 */
class ClientSetting extends \yii\db\ActiveRecord
{   
    const __PUSH_DEFAULT__ = 1;
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'client_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'push_new_friend', 'push_friend_request', 'push_live', 'push_message', 'push_likes'], 'safe'],
        ];
    }
    
    public static function getSetting($user_id){
        $setting_user = ClientSetting::find()->where(['user_id'=>$user_id])->one();
        if(!$setting_user){
            $setting_user = self::create($user_id);
        }
        return $setting_user;
    }

    public static function update_setting($request){
        $setting_user = ClientSetting::find()->where(['user_id'=>\Yii::$app->user->id])->one();
        if(!$setting_user){
            $setting_user = self::create(\Yii::$app->user->id);
        }
        $push_new_friend = $request->post('push_new_friend');
        $push_friend_request = $request->post('push_friend_request');
        $push_live = $request->post('push_live');
        $push_message = $request->post('push_message');
        $push_likes = $request->post('push_likes');
        
        if (isset($push_new_friend)) {
            $setting_user->push_new_friend = $push_new_friend;
        }
        if (isset($push_friend_request)) {
            $setting_user->push_friend_request = $push_friend_request;
        }
        if (isset($push_live)) {
            $setting_user->push_live = $push_live;
        }
        if (isset($push_message)) {
            $setting_user->push_message = $push_message;
        }
        if (isset($push_likes)) {
            $setting_user->push_likes = $push_likes;
        }
        $setting_user->save();
    }

    public static function create($user_id){
        $setting = new ClientSetting();
        $setting->user_id = $user_id;
        $setting->push_new_friend = self::__PUSH_DEFAULT__;
        $setting->push_friend_request = self::__PUSH_DEFAULT__;
        $setting->push_live = self::__PUSH_DEFAULT__;
        $setting->push_message = self::__PUSH_DEFAULT__;
        $setting->push_likes = self::__PUSH_DEFAULT__;
        $setting->save();
        return $setting;
    }

    public function fields()
    {
        return [
            'push_new_friend',
            'push_friend_request',
            'push_live',
            'push_message',
            'push_likes',
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
            'push_new_friend' => 'Push New Friend',
            'push_friend_request' => 'Push Friend Request',
            'push_live' => 'Push Live',
            'push_message' => 'Push Message',
            'push_likes' => 'Push Likes',
        ];
    }
}
