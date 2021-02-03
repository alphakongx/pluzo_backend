<?php

namespace api\models;

use Yii;
use yii\helpers\ArrayHelper;
use api\models\Advance;

/**
 * This is the model class for table "payment".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $time
 * @property string|null $payment_method
 * @property string|null $transaction_id
 * @property int|null $status
 * @property int|null $service_id
 * @property float|null $amount
 */
class Payment extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'payment';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            ['receipt', 'safe'],
            [['user_id', 'status', 'service_id'], 'integer'],
            [['amount'], 'number', 'message' => 'Amount must be number ex: 6.99!', 'on'=>'pay'],
            [['time', 'payment_method', 'transaction_id'], 'safe'],
            //['transaction_id', 'unique', 'targetClass' => '\api\models\Payment', 'message' => 'This transaction_id has already exist!', 'on'=>'pay'],
        ];
    }

    
    public static function pay_temp($request)
    {   
        if(!$request->post('service_id')){
            throw new \yii\web\HttpException('500','service_id cannot be blank.'); 
        }
        if($request->post('service_id') > 12 OR $request->post('service_id') < 1){
            throw new \yii\web\HttpException('500','Not have service with this id.'); 
        }
        if ($request->post('service_id') >= 10 AND $request->post('service_id') <= 12) {
            if(!$request->post('receipt')) {
                throw new \yii\web\HttpException('500','receipt cannot be blank.'); 
            }
        }
        $time = time();
        $pay = new Payment();
        $pay->scenario = 'pay';
        $pay->status = 1;
        $pay->receipt = $request->post('receipt');
        $pay->time = $time;
        $pay->user_id = \Yii::$app->user->id;
        $pay->amount = $request->post('amount');
        $pay->payment_method = 'iae';
        $pay->transaction_id = $pay->user_id.$time;
        $pay->service_id = $request->post('service_id');
        if($pay->save()){
            Advance::updateItem($pay->id, $pay->service_id, $time);
            return [
                'payment'=>Payment::find()->where(['id'=>$pay->id])->one(),
                'user'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
            ];
        } else {
            $errors = implode ( " " , \yii\helpers\ArrayHelper::getColumn ( $pay->errors , 0 , false ) );
            throw new \yii\web\HttpException('500',$errors); 
        }
    }

    public static function pay($request)
    {
        if(!$request->post('service_id')){
            throw new \yii\web\HttpException('500','service_id cannot be blank.'); 
        }
        if($request->post('service_id') > 12 OR $request->post('service_id') < 1){
            throw new \yii\web\HttpException('500','Not have service with this id.'); 
        }
        if(!$request->post('amount')){
            throw new \yii\web\HttpException('500','amount cannot be blank.'); 
        }
        if(!$request->post('payment_method')){
            throw new \yii\web\HttpException('500','payment_method cannot be blank.'); 
        }
        if(!$request->post('transaction_id')){
            throw new \yii\web\HttpException('500','transaction_id cannot be blank.'); 
        }
        $time = time();
        $pay = new Payment();
        $pay->scenario = 'pay';
        $pay->status = 1;
        $pay->time = $time;
        $pay->user_id = \Yii::$app->user->id;
        $pay->amount = $request->post('amount');
        $pay->payment_method = $request->post('payment_method');
        $pay->transaction_id = $request->post('transaction_id');
        $pay->service_id = $request->post('service_id');
        if($pay->save()){
            Advance::updateItem($pay->id, $pay->service_id, $time);
            return Payment::find()->where(['id'=>$pay->id])->one();
        } else {
            $errors = implode ( " " , \yii\helpers\ArrayHelper::getColumn ( $pay->errors , 0 , false ) );
            throw new \yii\web\HttpException('500',$errors); 
        }
    }



    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'time' => 'Time',
            'payment_method' => 'Payment Method',
            'transaction_id' => 'Transaction ID',
            'status' => 'Status',
            'service_id' => 'Service ID',
            'amount' => 'Amount',
        ];
    }

    public function fields()
    {
        return [
            //'id',
            'user_id',
            //'time' => 'Time',
            'payment_method',
            'transaction_id',
            //'status' => 'Status',
            'service_id',
            'amount',
        ];
    }
}
