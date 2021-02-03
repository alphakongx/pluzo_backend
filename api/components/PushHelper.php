<?php

namespace api\components;
use api\models\User;
use Yii;

class PushHelper
{   
    const ANDROID = 2;
    const IPHONE = 1;
   
    public static function hello($name) {
        return "Hello $name";
    }

    public static function test() {
        return "test";
    }


    public static function send_push($user, $message, $data)
    {   
        if (!$user->device) {
            return;
        }
        if (!$user->push_id) {
            return;
        }
        $app_id = 'b183cfca-4929-462d-b50d-d28ded4347a2'; 
        $token = 'ZjcxZjY2OTAtNzJjZi00MDc3LWI3NjAtYWFkZGUzMGU4NTBl';              
        
        $content = array(
            "en" => $message
        );

        $fields = array(
            'app_id' => $app_id,
            //'include_player_ids' => array("a76dbc5a-d867-4e40-8585-ae3ebc3ad9f5"),
            'include_player_ids' => array($user->push_id),
            'data' => $data,
            //'url' => 'http://www.google.com',
            'contents' => $content
        );

        $fields = json_encode($fields);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json; charset=utf-8',
                                                   'Authorization: '.$token));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);
        //print_r($response);
        //die();
    }    
}
