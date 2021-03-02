<?php

namespace api\models;

use Yii;
use api\models\Like;
use api\models\UserMsg;
use api\models\User;
use api\models\UserGetLike;
use api\models\SwipeSetting;
use api\components\DistanceHelper;
use api\models\Indicator;
use api\models\Friend;
use api\models\Stream;
use api\models\Advance;

/**
 * This is the model class for table "like".
 *
 * @property int $id
 * @property int $user_source_id
 * @property int $user_target_id
 * @property int $like
 * @property string|null $created_at
 */
class LikeOpt extends \yii\db\ActiveRecord
{  
	const DISLIKE = 0;
    const LIKE = 1;
    const SUPER_LIKE = 2;
    const AGE_FROM_DEFAULT = 13;
    const AGE_TO_DEFAULT = 25;
    const GENDER_DEFAULT = 0;
    const DISTANCE_DEFAULT = 100;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'like';
    }

	public function getLikeInfo($your_likes_array)
    {
        $dis = 0;
        $like = 0;
        $super = 0;

        $likes = Like::find()
        ->where(['user_target_id'=>\Yii::$app->user->id])
        ->andWhere(['not in', 'user_source_id', $your_likes_array])
        ->all();
        foreach ($likes as $key => $value) {
            if ($value['like'] == self::DISLIKE) {
                $dis++;
            }
            if ($value['like'] == self::LIKE) {
                $like++;
            }
            if ($value['like'] == self::SUPER_LIKE) {
                $super++;
            }
        }
        return [
            'dislike' => $dis,
            'like' => $like,
            'superlike' => $super
        ];
    }
}