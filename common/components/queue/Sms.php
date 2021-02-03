<?php

namespace common\components\queue;
use yii\base\BaseObject;
use common\models\Test;
use Yii;
use Twilio\Rest\Client as Twil;

class Sms extends BaseObject implements \yii\queue\JobInterface
{   
    public $phone;
    public $message;
    const TWILIO_API_KEY1 = 'AC85e9e328e8bce93e332161d9342a9b2e';
    const TWILIO_API_KEY2 = '255041c5e9f5ee836fd1d73e46e5d464';
    const TWILIO_NUMBER_FROM = '+12056240327';
    
    public function execute($queue)
    {    
        $client = new Twil(self::TWILIO_API_KEY1, self::TWILIO_API_KEY2);
        $result = $client->messages->create(
            $this->phone,
            array(
                'from' => self::TWILIO_NUMBER_FROM,
                'body' => $this->message
            )
        ); 
    }
}

?>
