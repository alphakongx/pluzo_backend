<?php
namespace api\models;

use common\models\query\UserQuery;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use common\models\Token;
use api\models\Like;
use api\models\User;

class UserMsg extends ActiveRecord
{
    const STATUS_NOT_ACTIVE = 0;
    const STATUS_ACTIVE = 1;
    public $password;


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%client}}';
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            TimestampBehavior::className(),
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'unique', 'targetClass' => '\common\models\Client', 'message' => 'This username has already been taken.', 'on'=>'create'],
            ['phone', 'unique', 'targetClass' => '\common\models\Client', 'message' => 'This phone number has already been taken.', 'on'=>'create'],
            [['username', 'email', 'phone'], 'required', 'on'=>'create'],
            ['status', 'integer'],
            [['password','gender', 'first_name', 'last_name', 'phone', 'username', 'email'], 'safe'],
            
        ];
    }
           
    public function fields()
    {
        return [
            '_id' => 'id',
            'name' => 'username',  
            'first_name' => 'first_name',
            'last_name' => 'last_name',  
            'phone' => 'phone',
            'status' => 'status',
            'gender'=>'gender',
            'avatar'=>'image',
            'birthday'=>'birthday',
            'age'=>function(){ 
                return User::getAge($this->birthday);
            },  
            'image'=>'image',
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
            'first_login',

        ];
    }

    public function getSearch($request)
    {
        return UserMsg::find()->where(['like', 'username', $request])
        ->orwhere(['like', 'first_name', $request])
        ->orwhere(['like', 'last_name', $request])
         ->all();
        
    }
    public function getImages()
    {   
        return $this->hasMany(Images::className(), ['user_id' => 'id'])->
        orderBy(['sort' => SORT_ASC]);               
    }


   
}   
