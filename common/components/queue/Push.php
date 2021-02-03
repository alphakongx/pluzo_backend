<?php

namespace common\components\queue;
use yii\base\BaseObject;
use common\models\Test;
use api\models\User;
use Yii;
use api\components\PushHelper;
use api\models\Stream;

class Push BaseObject implements \yii\queue\JobInterface
{   
    public $action;
    public $user_from;
    public $user_to;
    
    public function execute($queue)
    {    
        if($action == 'friends'){
            $user_to = User::find()->where(['id'=>$this->user_to])->one();
            $user_from = User::find()->where(['id'=>$this->user_from])->one();

            $message = 'You and '.$user_from->first_name.' are now friends.';
            $data = array("action" => "friends", "user_model" => Stream::userForApi($this->user_from));
            PushHelper::send_push($user_to, $message, $data);
        }
    }
}

?>