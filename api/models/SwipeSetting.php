<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "swipe_setting".
 *
 * @property int $id
 * @property int|null $user_id
 * @property int|null $global
 * @property string|null $latitude
 * @property string|null $longitude
 * @property string|null $location
 * @property int|null $age_from
 * @property int|null $age_to
 * @property string|null $distance
 */
class SwipeSetting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'swipe_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'global', 'age_from', 'age_to'], 'safe'],
            [['latitude', 'longitude', 'location', 'distance'], 'safe'],
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
            'global' => 'Global',
            'latitude' => 'Latitude',
            'longitude' => 'Longitude',
            'location' => 'Location',
            'age_from' => 'Age From',
            'age_to' => 'Age To',
            'distance' => 'Distance',
        ];
    }
    
    public function fields()
    {
        return [
            'location' => 'location', 
            'latitude' => 'latitude', 
            'longitude' => 'longitude', 
            'distance' => 'distance', 
            'gender' => 'gender', 
            'age_from' => 'age_from', 
            'age_to' => 'age_to',  
            'global' => 'global',
        ];
    }
}
