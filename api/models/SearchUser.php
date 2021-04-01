<?php
namespace api\models;

use common\models\query\UserQuery;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use common\models\Token;
use api\models\SearchUserPpl;
use api\models\Like;
use api\models\User;

class SearchUser extends ActiveRecord
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
            ['username', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This username has already been taken.', 'on'=>'create'],
            ['phone', 'unique', 'targetClass' => '\common\models\User', 'message' => 'This phone number has already been taken.', 'on'=>'create'],
            [['username', 'email', 'phone'], 'required', 'on'=>'create'],
            ['status', 'integer'],
            [['password','gender', 'first_name', 'last_name', 'phone', 'username', 'email'], 'safe'],
            
        ];
    }
           
    public function fields()
    {
        return [
            'id' => 'id',
            'name' => 'username',  
            'first_name' => 'first_name',
            'last_name' => 'last_name',  
            //'phone' => 'phone',
            //'status' => 'status',
            'gender'=>'gender',
            'avatar'=>'image',
            'age'=>function(){ 
                return User::getAge($this->birthday);
            }, 
            
            'address'=>'address',
            'city'=>'city',
            'state'=>'state',
            'premium'=>function(){ 
                return User::checkPremium($this->id);
            },
            'bio'=>'bio',
            'last_activity'=>'last_activity',

            'images'=>'images',
            
            'badges'=>function(){ 
                return Badge::getBadge($this->id);
            },
            //'first_login',
            'hide_location'=>'hide_location',
            'hide_city'=>'hide_city',
        ];
    }

    public function getSearch($request)
    {   
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT `client`.`id` FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id);
        $result = $command->queryAll();
        $ar = [];
        foreach ($result as $key => $value) {
            array_push($ar, $value['id']);
        }
        $result = SearchUserPpl::find()->where(['like', 'username', $request])
        ->orwhere(['like', 'first_name', $request])
        ->orwhere(['like', 'last_name', $request])
        ->andWhere(['<>','id', \Yii::$app->user->id])
        ->andWhere(['not in', 'id', User::bannedUsers()])
        ->andWhere(['not in', 'id', User::whoBannedMe()])
        ->andWhere(['not in', 'id', $ar])
         ->all();

        $users = [];
        foreach ($result as $key => $value) {


            $images = $command = $connection->createCommand("SELECT `images`.`id`, `images`.`path`  FROM `images` WHERE `user_id`=".$value['id']." ORDER BY  `sort` ASC");
            $result_images = $command->queryAll();
            $ar = [
                'id'=>$value['id'],
                'username'=>$value['username'],
                
                'image'=>$value['image'],
                'gender'=>$value['gender'],
                
                'age'=>User::getAge($value['birthday']),
                
                'first_name'=>$value['first_name'],
                'last_name'=>$value['last_name'],
                
                'address'=>$value['address'],
                'city'=>$value['city'],
                'state'=>$value['state'],
                'hide_location'=>$value['hide_location'],
                'hide_city'=>$value['hide_city'],
               
                'premium'=>User::checkPremium($value['id']),
                'images'=>$result_images,
                'badges'=>Badge::getBadge($value['id']),
                
            ];
            array_push($users, $ar);
        }
        return $users;
    }

    public function getImages()
    {   
        return $this->hasMany(Images::className(), ['user_id' => 'id'])->
        orderBy(['sort' => SORT_ASC]);       
    }
}   