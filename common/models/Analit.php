<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "analit".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $page
 * @property string|null $time
 * @property string|null $time_start
 * @property string|null $time_end
 * @property string|null $during
 */
class Analit extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'analit';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id'], 'safe'],
            [['leave', 'page', 'time', 'time_start', 'time_end', 'during'], 'safe'],
        ];
    }

    public function fields()
    {
        return [
            'user_id' => 'user_id',
            'during' => 'during',
            'leave' => 'leave',
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
            'page' => 'Page',
            'time' => 'Time',
            'time_start' => 'Time Start',
            'time_end' => 'Time End',
            'during' => 'During',
        ];
    }
}
