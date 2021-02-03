<?php
namespace api\models;

use common\models\query\UserQuery;
use Yii;
use yii\base\NotSupportedException;
use yii\db\ActiveRecord;
use common\models\Token;
use yii\helpers\ArrayHelper;
use api\models\User;

class UserSwipe extends ActiveRecord
{

    public static function tableName()
    {
        return '{{%client}}';
    }

    public function fields()
    {
        return [
            'id' => 'id',
            'username' => 'username',   
            'first_name' => 'first_name',
            'last_name' => 'last_name',  
            'phone' => 'phone',
            'status' => 'status',
            'gender'=>'gender',
            'image'=>'image',
            'birthday'=>'birthday',
            'age'=>function(){ 
                return User::getAge($this->birthday);
            },  
            'latitude'=>'latitude',
            'longitude'=>'longitude',
            'address'=>'address',
            'city'=>'city',
            'state'=>'state',
            'last_activity'=>'last_activity',
            'premium'=>function(){ 
                return User::checkPremium($this->id);
            },            
            'bio'=>'bio',
            'images'=>'images',
            'friends'=>function(){ 
                return User::friendCount($this->id);
            },
            'badges'=>function(){ 
                return Badge::getBadge($this->id);
            },
            'likes'=>function(){ 
                return Like::getLike($this->id);
            },
            'first_login',
        ];
    }

    public function getImages()
    {   
        return $this->hasMany(Images::className(), ['user_id' => 'id'])->
        orderBy(['sort' => SORT_ASC]);       
    }
} 