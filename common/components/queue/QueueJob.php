<?php

namespace common\components\queue;
use api\models\User;
use yii\base\BaseObject;
use common\models\Test;
use Yii;

class QueueJob extends BaseObject implements \yii\queue\JobInterface
{	
	public $user_id;
    
    public function execute($queue)
    {	
    	$user = User::find()->where(['id'=>$this->user_id])->one();
    	$test = new Test();
    	$test->time = time();
    	$test->text = $user->username;
    	$test->save();
    }
}

?>