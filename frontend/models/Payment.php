<?php

namespace frontend\models;

use Yii;

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
            [['user_id', 'status', 'service_id'], 'integer'],
            [['amount'], 'number'],
            [['time', 'payment_method', 'transaction_id'], 'string', 'max' => 255],
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
            'time' => 'Time',
            'payment_method' => 'Payment Method',
            'transaction_id' => 'Transaction ID',
            'status' => 'Status',
            'service_id' => 'Service ID',
            'amount' => 'Amount',
        ];
    }
}
