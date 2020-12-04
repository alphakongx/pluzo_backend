<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "badge".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $badge_id
 */
class Badge extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'badge';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'badge_id'], 'integer'],
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
            'badge_id' => 'Badge ID',
        ];
    }
}
