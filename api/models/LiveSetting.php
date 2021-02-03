<?php

namespace api\models;

use Yii;

/**
 * This is the model class for table "live_setting".
 *
 * @property int $id
 * @property int|null $user_id
 * @property string|null $country
 * @property int|null $filter
 */
class LiveSetting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'live_setting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_id', 'filter', 'state'], 'safe'],
            [['country'], 'safe'],
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
            'country' => 'Country',
            'filter' => 'Filter',
        ];
    }

    public function fields()
    {
        return [
            'user_id' => 'user_id',
            'country' => 'country',
            'state' => 'state',
            'filter' => 'filter',
        ];
    }

    public static function createLiveSetting(){
        $cr = new LiveSetting();
        $cr->user_id = \Yii::$app->user->id;
        $cr->country = 'Worldwide';
        $cr->filter = 0;
        return $cr;
    }

    public static function getLiveSetting(){
        $ls = LiveSetting::find()->where(['user_id'=>\Yii::$app->user->id])->one();
        if ($ls) {
            return $ls;
        } else {
            return self::createLiveSetting();
        }
    }

    public static function setLiveSetting($request){
        $filter = $request->post('filter');
        $country = $request->post('country');
        $state = $request->post('state');
        if (!isset($filter)) {
            throw new \yii\web\HttpException('500','filter cannnot be blank'); 
        }
        if (!$country) {
            throw new \yii\web\HttpException('500','country cannnot be blank'); 
        }
        if ($filter < 0 OR $filter > 2) {
            throw new \yii\web\HttpException('500','filter can be only 0 - friends, 1 - participants, 2 - distance'); 
        }
        if ($country == 'United States') {
            if (!$state) {
                throw new \yii\web\HttpException('500','state cannnot be blank for country United States'); 
            }
        } else {
            $state = null;
        }

        $ls = LiveSetting::find()->where(['user_id'=>\Yii::$app->user->id])->one();
        if ($ls) {
            $ls->filter = $filter;
            if (isset($country)) {
                $ls->country = $country;
            } else {
                $ls->country = 'Worldwide';
            }
            $ls->state = $state;
        } else {
            $ls = new LiveSetting();
            $ls->user_id = \Yii::$app->user->id;
            if (isset($country)) {
                $ls->country = $country;
            } else {
                $ls->country = 'Worldwide';
            }
            $ls->filter = $filter;
            $ls->state = $state;
        }
        $ls->save();
        return $ls;
    }
}
