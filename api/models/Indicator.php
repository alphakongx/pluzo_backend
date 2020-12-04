<?php

namespace api\models;

use Yii;
use api\models\Like;

/**
 * This is the model class for table "indicator".
 *
 * @property int $id
 * @property int|null $user_current_id
 * @property int|null $user_target_id
 * @property string|null $time
 * @property int|null $type
 * @property int|null $status
 */
class Indicator extends \yii\db\ActiveRecord
{
    const __NEW_STATUS__ = 0;
    const __READ_STATUS__ = 1;
    const __TYPE_LIKE__ = 0;
    const __TYPE_SUPERLIKE__ = 1;
    const __TYPE_SUPERLIKE_NEWFRIEND__ = 2;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'indicator';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_current_id', 'user_target_id', 'type', 'status'], 'safe'],
            [['time'], 'safe'],
        ];
    }

    public static function checkIndicatorExist($user_current_id, $user_target_id, $type, $like_to){
        $ind = Indicator::find()->where(['user_current_id'=>$user_current_id, 'user_target_id'=>$user_target_id])->one();
        if(!$ind){
            $time = time();
            $new = new Indicator();
            $new->time = $time;
            $new->user_current_id = $user_current_id;
            $new->user_target_id = $user_target_id;
            if ($like_to == Like::LIKE) {
                $new->type = self::__TYPE_LIKE__;;
            } else {
                $new->type = self::__TYPE_SUPERLIKE__;;
            }
            $new->status = self::__NEW_STATUS__;
            $new->save();

            $new = new Indicator();
            $new->time = $time;
            $new->user_current_id = $user_target_id;
            $new->user_target_id = $user_current_id;
            $new->type = $type;
            $new->status = self::__NEW_STATUS__;
            $new->save();
        }
    }

    public function checkLike($user_current_id, $user_target_id, $is_like)
    {   
        $liked = Like::find()->where(['user_source_id'=>$user_target_id, 'user_target_id'=>$user_current_id])->andwhere(['in', 'like', [Like::LIKE,Like::SUPER_LIKE]])->one();
        if($liked){
            if ($liked->like == Like::SUPER_LIKE) {
                $type = self::__TYPE_SUPERLIKE__;
            } else {
                $type = self::__TYPE_LIKE__;
            }
            Indicator::checkIndicatorExist($user_current_id, $user_target_id, $type, $is_like);
        }
    }
    

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_current_id' => 'User Current ID',
            'user_target_id' => 'User Target ID',
            'time' => 'Time',
            'type' => 'Type',
            'status' => 'Status',
        ];
    }
}
