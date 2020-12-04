<?php

namespace frontend\models;

use Yii;

/**
 * This is the model class for table "advance".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $created_at
 * @property string|null $used_time
 * @property string|null $expires_at
 * @property string|null $payment_id
 * @property int|null $status
 * @property int|null $type
 */
class Advance extends \yii\db\ActiveRecord
{
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
            [['created_at', 'used_time', 'expires_at', 'payment_id'], 'string', 'max' => 255],
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
            'created_at' => 'Created At',
            'used_time' => 'Used Time',
            'expires_at' => 'Expires At',
            'payment_id' => 'Payment ID',
            'status' => 'Status',
            'type' => 'Type',
        ];
    }
}
