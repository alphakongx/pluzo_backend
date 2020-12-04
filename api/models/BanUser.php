<?php

namespace api\models;

use Yii;
use api\models\Stream;

/**
 * This is the model class for table "ban_user".
 *
 * @property int $id
 * @property int $user_source_id
 * @property int $user_target_id
 * @property string|null $reason
 * @property string|null $time
 */
class BanUser extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'ban_user';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_source_id', 'user_target_id'], 'required'],
            [['user_source_id', 'user_target_id'], 'integer'],
            [['reason', 'time'], 'safe'],
        ];
    }

    public function fields()
    {
        return [
            'user_source_id' => 'user_source_id',
            'user_target_id' => 'users', 
            'time' => 'time',
            'reason' => 'reason',
        ];
    }

    public function getUsers()
    {
        return Stream::userForApi($this->user_target_id);
    }


}
