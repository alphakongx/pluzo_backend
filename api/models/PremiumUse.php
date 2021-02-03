<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "premium_use".
 *
 * @property int $id
 * @property int $user_id
 * @property int $type
 * @property string|null $boost_type
 * @property string|null $time
 * @property int $premium_id
 */
class PremiumUse extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'premium_use';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'premium_id'], 'safe'],
            [['user_id', 'type', 'premium_id'], 'safe'],
            [['boost_type', 'time'], 'safe',],
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
            'type' => 'Type',
            'boost_type' => 'Boost Type',
            'time' => 'Time',
            'premium_id' => 'Premium ID',
        ];
    }
}
