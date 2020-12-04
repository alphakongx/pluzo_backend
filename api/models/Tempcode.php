<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "tempcode".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $expires_at
 * @property string|null $type
 * @property string|null $code
 */
class Tempcode extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'tempcode';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'integer'],
            [['expires_at', 'type', 'code', 'data'], 'safe'],
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
            'expires_at' => 'Expires At',
            'type' => 'Type',
            'code' => 'Code',
            'data' => 'Data',
        ];
    }
}
