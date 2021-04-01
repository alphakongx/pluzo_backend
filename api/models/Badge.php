<?php

namespace api\models;

use Yii;
use api\models\User;

/**
 * This is the model class for table "images".
 *
 * @property int $id
 * @property int $user_id
 * @property int|null $avator
 * @property string $created_at
 * @property string $path
 * @property int|null $sort
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
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'badge_id' => 'Badge',
        ];
    }

    public function fields()
    {
        return [
            //'id' => 'id',
            //'user_id' => 'user_id',  
            'badge_id' => 'badge_id',
        ];
    }

    public function addBadge($request)
    {      
        $badge_id = (int)$request->post('badge_id');
        if(!$badge_id){
            throw new \yii\web\HttpException('500','badge_id cannot be blank.'); 
        }
        $premium = User::checkPremium(\Yii::$app->user->id);
        if ($badge_id > 6 AND $premium == 0) {
            throw new \yii\web\HttpException('500','badge > 6 only for pluzo+'); 
        }

        $badge = Badge::find()->where(['badge_id'=>$badge_id, 'user_id'=>\Yii::$app->user->id])->one();
        if($badge){
        } else {
            $badge = new Badge();
            $badge->user_id = \Yii::$app->user->id;
            $badge->badge_id = $badge_id;
            $badge->save();
        }
        return $badge;
    }

    public static function getBadge($id)
    { 
        $badge = Badge::find()->where(['user_id'=>$id])->all();
        $ar = [];
        $premium = User::checkPremium($id);
        foreach ($badge as $key => $value) {
            if ($value['badge_id'] > 6) {
                if ($premium == 0) {
                    continue;
                }
            }
            array_push($ar, $value['badge_id']);
        }
        return $ar;
    }

    public function deleteBadge()
    { 
        $badge_id = (int)$request->post('badge_id');
        if(!$badge_id){
            throw new \yii\web\HttpException('500','badge_id cannot be blank.'); 
        }
        $badge = Badge::find()->where(['badge_id'=>$badge_id, 'user_id'=>\Yii::$app->user->id])->one();
        if ($badge) {
            \Yii::$app
            ->db
            ->createCommand()
            ->delete('badge', ['id' => $badge->id])
            ->execute();
            return ['Badge was deleted!'];
        } else {
            throw new \yii\web\HttpException('500', 'Badge '.$badge.' not found!');
        }
    }
}