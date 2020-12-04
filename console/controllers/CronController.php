<?php

namespace console\controllers;

use Yii;
use yii\console\Controller;
use api\models\User;

class CronController extends Controller
{
	public function actionIndex($message = 'hello world')
    {
        echo $message . "\n";
    }

    //* * * * * /usr/bin/php /var/www/html/console/yii cron/check-online-users
    public function actionCheckOnlineUsers()
    {	
    	$time = time();
    	$dif = $time - 60;
    	$users = User::find()
    	->where(['status'=>1])
    	->andwhere(['>=','last_activity', $dif])
    	->all();
    	$online_users = [];
    	foreach ($users as $key => $value) {
            $temp_array = [
                'id'=>$value['id'],
            ];
            array_push($online_users, $temp_array);			
    	}
                $socket_result = [
                    'user'=>$online_users,
                    'online'=>1,
                ];
            User::socket(0, $socket_result, 'User_online');

        $end = $time - 60;
        $start = $end - 68400;
        $users = User::find()
        ->where(['status'=>1])
        ->andwhere(['between', 'last_activity', $start, $end])
        ->all();
        $offline = [];
        foreach ($users as $key => $value) {
            $temp_array = [
                'id'=>$value['id'],
                'last_activity'=>$value['last_activity'],
            ];
            array_push($offline, $temp_array);            
        }
                $socket_result = [
                    'user'=>$offline,
                    'online'=>0,
                ];
            User::socket(0, $socket_result, 'User_offline');
    }

}
?>