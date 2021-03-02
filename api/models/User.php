<?php
namespace api\models;

use common\models\query\UserQuery;
use Yii;
use yii\base\NotSupportedException;
use yii\behaviors\TimestampBehavior;
use yii\db\ActiveRecord;
use yii\web\IdentityInterface;
use common\models\Token;
use WebSocket\Client as WEBCLIENT;
use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;
use yii\imagine\Image;
use Twilio\Rest\Client as Twil;
use api\models\Party;
use api\models\Message;
use api\models\Advance;
use api\models\ClientSetting;
use api\models\BanUser;
use api\models\PremiumUse;
use common\components\queue\UploadImageAmazon;
use common\components\queue\Sms;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class User extends ActiveRecord
{
    const STATUS_NOT_ACTIVE = 0;
    const STATUS_ACTIVE = 1;
    const STATUS_BANNED = 3;
    const NOT_PREMIUM = 0;
    const PREMIUM = 1;
    const WEB_CLIENT_ADDRESS = 'ws://3.134.208.235:27800?user=0';
    const TWILIO_API_KEY1 = 'AC85e9e328e8bce93e332161d9342a9b2e';
    const TWILIO_API_KEY2 = '255041c5e9f5ee836fd1d73e46e5d464';
    const TWILIO_NUMBER_FROM = '+12056240327';
    const GOOGLE_MAP_KEY = 'AIzaSyDiern53s3oclBm52lQK0F-YWzLWCA_5BU';
    const SIGHTENGINE_API_USER = '687449323';
    const SIGHTENGINE_API_KEY = 'Mo4xtDj5rYnwfdPHheQU';

    const GENDER_OTHER = 0;
    const GENDER_MALE = 1;
    const GENDER_FEMALE = 2;
    
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

    public static function secondsToTime($sec){
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$sec");
        return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes %s seconds');
    }

    public static function nudityFilter($path)
    {
        $client = new SightengineClient(self::SIGHTENGINE_API_USER, self::SIGHTENGINE_API_KEY);
        $dir = \Yii::getAlias('@webroot') . '/uploads/'.$path;
        $output = $client->check(['nudity'])->set_file($dir);
        print_r($output);
        die();
    }

    public static function bannedUsers()
    {   
        $data = Yii::$app->cache->getOrSet('bannedUsers'.\Yii::$app->user->id, function () {
            $users = BanUser::find()->where(['user_source_id'=>\Yii::$app->user->id])->all();
            $banned = [];
            foreach ($users as $key => $value) {
                array_push($banned, $value['user_target_id']);
            }
            return $banned;
        });
        return $data;
    }

    public static function whoBannedMe()
    {   
        $users = BanUser::find()->where(['user_target_id'=>\Yii::$app->user->id])->all();
        $banned = [];
        foreach ($users as $key => $value) {
            array_push($banned, $value['user_source_id']);
        }
        return $banned;
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['username', 'unique', 'targetClass' => '\common\models\Client', 'message' => 'This username has already been taken.', 'on'=>'create'],
            ['phone', 'unique', 'targetClass' => '\common\models\Client', 'message' => 'This phone number has already been taken.', 'on'=>'create'],
            ['phone', 'unique', 'targetClass' => '\common\models\Client', 'message' => 'This phone number has already been taken.', 'on'=>'update'],
            [['username', 'email', 'phone'], 'required', 'on'=>'create'],
            ['status', 'integer'],
            [['password','gender', 'first_name', 'last_name', 'phone', 'username', 'email', 'state', 'city', 'first_login'], 'safe'],
            
        ];
    }
           
    
    public function fields()
    {
        return [
            'id' => 'id',
            'username' => 'username',   
            'token' => function(){ if (Yii::$app->controller->action->id == 'get-user-info') { return ''; } else { return $this->token; } },
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
            'push_id'=>'push_id',
            'device'=>'device',
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
            'advanced'=>function(){ 
                    return User::getAdvanced($this->id);
            },
            'user_setting'=>function(){ 
                    return ClientSetting::getSetting($this->id);
            },
            'first_login',
            
            'premium_info'=>function(){ 
                    return User::getPremiumInfo();
            },
        ];
    }

    public static function getAge($birthday){
        $difference = time() - $birthday;
        $age = floor($difference / 31556926);
        return $age;
    }
    
    public static function checkstatus($request){
        $user_id = $request->post('user_id');
        if(!$user_id){
            throw new \yii\web\HttpException('500','user_id cannot be blank.'); 
        }
        $online = 0;
        $action = 'Check status';
        $user = User::find()->where(['id'=>$user_id])->one();
        if ($user) {
            $dif = time() - $user->last_activity;
            if($dif < 120){
                $online = 1;
                $action = 'User_online';
                $socket_result = [
                    'user'=>$user_id,
                    'online'=>$online,
                    'time'=>time(),
                    'last_activity'=>$user->last_activity,
                ];
            } else {
                $online = 0;
                $action = 'User_offline';
                $socket_result = [
                    'user'=>$user_id,
                    'online'=>$online,
                    'time'=>time(),
                    'last_activity'=>$user->last_activity,
                ];
            }
            return $socket_result;
        } else {
            throw new \yii\web\HttpException('500','User not exist.'); 
        }

    }

    public function checkPremium($user_id){
        $premium = Advance::find()
        ->where(['user_id'=>$user_id, 'status'=>Advance::ITEM_AVAILABILITY, 'type'=>Advance::PLUZO_PLUS])
        ->andwhere(['<=', 'used_time', time()])
        ->andwhere(['>', 'expires_at', time()])
        ->one();
        if($premium){
            return 1;
        } else {
            return 0;
        }
    }

    public static function getPremiumInfo(){

        $premium = Advance::find()
        ->where(['user_id'=>\Yii::$app->user->id, 'status'=>Advance::ITEM_AVAILABILITY, 'type'=>Advance::PLUZO_PLUS])
        ->andwhere(['<=', 'used_time', time()])
        ->andwhere(['>', 'expires_at', time()])
        ->one();

        if (!$premium) {
            return [
                'premium' => 0
            ];
        }

        $start_date = $premium->used_time;
        $end_date = $premium->expires_at;

        //period and sub_period
        $duration = $end_date - $start_date;
        $month = 1;
        $sub_period = 1;
        $reset_date = null;
        $super_like_reset_date = null;
        $swipe_boost_used = 0;
        $live_boost_used = 0;
        $super_likes_used_today = 0;
        $super_like_reset_date = 0;

        if ($duration == Advance::DURATION_PLUZO_PLUS_1_MONTH) {
            $month = 1;
        }
        if ($duration == Advance::DURATION_PLUZO_PLUS_3_MONTH) {
            $month = 3;
        }
        if ($duration == Advance::DURATION_PLUZO_PLUS_12_MONTH) {
            $month = 12;
        }

    $now = time();
    //boost
    for ($i=1; $i <= $month; $i++) { 
        $n = $i - 1;
        $st_d = $start_date + $n*Advance::DURATION_PLUZO_PLUS_1_MONTH;
        $end_d = $st_d + Advance::DURATION_PLUZO_PLUS_1_MONTH;
        $select = '';
        if ($now >= $st_d AND $now < $end_d) {
            $sub_period = $i;
            $boost_reset_date = $end_d;

            $swipe_boost_used = PremiumUse::find()
            ->where(['user_id'=>\Yii::$app->user->id, 'type'=>Advance::BOOST,'boost_type'=>Advance::BOOST_TYPE_SWIPE])
            ->andwhere(['between', 'time', $st_d, $end_d])
            ->count();

            $live_boost_used = PremiumUse::find()
            ->where(['user_id'=>\Yii::$app->user->id, 'type'=>Advance::BOOST,'boost_type'=>Advance::BOOST_TYPE_LIVE])
            ->andwhere(['between', 'time', $st_d, $end_d])
            ->count();
            break;
        }
    }

    //super_like
    for ($i=1; $i <= $month*28; $i++) { 
        $n = $i - 1;
        $st_d = $start_date + $n*86400;
        $end_d = $st_d + 86400;
        $select = '';
        if ($now >= $st_d AND $now < $end_d) {
            
            $super_likes_used_today = PremiumUse::find()
            ->where(['user_id'=>\Yii::$app->user->id, 'type'=>Advance::SUPER_LIKE])
            ->andwhere(['between', 'time', $st_d, $end_d])
            ->count();

            $super_like_reset_date = $end_d;
            break;
        }
    }

        return [
            'premium' => 1,
            'start_date'=>(int)$start_date,
            'end_date'=>(int)$end_date,
            'total_month'=>$month,
            'current_month'=>$sub_period,
            'swipe_boost_used'=>(int)$swipe_boost_used,
            'live_boost_used'=>(int)$live_boost_used,
            'boost_reset_date'=>$boost_reset_date,
            'super_likes_used_today'=>(int)$super_likes_used_today,
            'super_like_reset_date'=>$super_like_reset_date,
        ];
        
    }

    public static function boostCountrecursion($used_time, $n){

        $dif = $used_time - Advance::BOOST_SWIPE_TIME;
        $check = Advance::find()
        ->where(['type'=>Advance::BOOST, 'boost_type'=>Advance::BOOST_TYPE_SWIPE, 'user_id'=>\Yii::$app->user->id, 'status'=>Advance::ITEM_USED, 'used_time'=>$dif])
            ->one();
        if ($check) {
            $n = $n + 1;
            return self::boostCountrecursion($check->used_time, $n);
        } else {
            return $n;
        }
        
    }
    
    public static function getAdvanced($user_id){
        $boosts = Advance::find()->where(['user_id'=>$user_id, 'type'=>1, 'status'=>Advance::ITEM_AVAILABILITY])->count();
        $super_likes = Advance::find()->where(['user_id'=>$user_id, 'type'=>2, 'status'=>Advance::ITEM_AVAILABILITY])->count();
        $rewinds = Advance::find()->where(['user_id'=>$user_id, 'type'=>3, 'status'=>Advance::ITEM_AVAILABILITY])->count();

        $time_diff = time() - Advance::BOOST_SWIPE_TIME;
        $swipe_boost = Advance::find()->where(['type'=>Advance::BOOST, 'boost_type'=>Advance::BOOST_TYPE_SWIPE, 'user_id'=>\Yii::$app->user->id, 'status'=>Advance::ITEM_USED])
        ->andwhere(['>=', 'used_time', $time_diff])
        ->orderBy('used_time DESC')->one();
        if($swipe_boost){
            $swipe_boost = $swipe_boost->used_time;
            $end_boost_swipe_time = $swipe_boost + Advance::BOOST_SWIPE_TIME;
            $boost_swipe_remaining_time = $end_boost_swipe_time - time();

            $count_swipe = self::boostCountrecursion($swipe_boost, 1);
            if($boost_swipe_remaining_time < 0){$boost_swipe_remaining_time = 0;}

        } else {
            $swipe_boost = NULL;
            $end_boost_swipe_time = NULL;
            $boost_swipe_remaining_time = 0;
            $count_swipe = 1;
        }

        $time_diff = time() - Advance::BOOST_LIVE_TIME;
        $live_boost = Advance::find()->where(['type'=>Advance::BOOST, 'boost_type'=>Advance::BOOST_TYPE_LIVE, 'user_id'=>\Yii::$app->user->id, 'status'=>Advance::ITEM_USED])
        ->andwhere(['>=', 'used_time', $time_diff])
        ->orderBy('used_time DESC')->one();
        if($live_boost){
            $live_boost = $live_boost->used_time;
            $end_boost_live_time = $live_boost + Advance::BOOST_LIVE_TIME;
            $boost_live_remaining_time = $end_boost_live_time - time(); 
            if($boost_live_remaining_time < 0){$boost_live_remaining_time = 0;}
        } else {
            $live_boost = NULL;
            $end_boost_live_time = NULL;
            $boost_live_remaining_time = 0;
        }

        $last_boost = [
            'swipe_boost'=>(int)$swipe_boost,
            'end_boost_swipe_time'=>(int)$end_boost_swipe_time,
            'count_swipe'=>$count_swipe,
            'boost_swipe_remaining_time'=>(int)$boost_swipe_remaining_time,
            'live_boost'=>(int)$live_boost,
            'end_boost_live_time'=>(int)$end_boost_live_time,
            'boost_live_remaining_time'=>(int)$boost_live_remaining_time,
        ];

        $info = User::getPremiumInfo();
        if ($info['premium'] == 1) {
            $boosts_left = 5 - $info['live_boost_used'] - $info['swipe_boost_used'];
            $boosts = $boosts + $boosts_left;
            $likes_left = 5 - $info['super_likes_used_today'];
            $super_likes = $super_likes + $likes_left;
            $rewinds = 'unlimited';
        }

        return [
            'last_boost_time'=>$last_boost,
            'boosts'=>$boosts,
            'super_likes'=>$super_likes,
            'rewinds'=>$rewinds,
        ];
    }
    
    public function friendCount($user_id){
        $connection = Yii::$app->getDb();
        $sql = 'SELECT COUNT(*) as count  FROM `friend` l1 INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id LEFT JOIN `client` ON `client`.`id` = l2.user_source_id WHERE l1.user_source_id = "'.$user_id.'"';
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        return $result[0]['count']+1;
    }


    public static function generateUniqueUsername($firstname){
        
        $i=0; 
        do {
            $new_username_full =  $firstname.mt_rand(1,99999); 
            $user = User::find()->where(['username'=>$availableUserName])->one();
            if($user){
                $isAvailable = false;
            } else {
                $isAvailable = true;
            }
        } while( !$isAvailable && $i++<9000);
        return $new_username_full;
    }

    public function savePhotoRegister($image, $user_id)
    {   
        $images = Images::find()->where(['user_id'=>$user_id])->orderBy(['sort' => SORT_DESC])->one();
        if($images){
            $n = $images->sort + 1;
        } else {
            $n = 0;
        }
        for ($i=0; $i < count($image['name']); $i++) { 
            $file_name = uniqid().'.jpg';   
            $temp_file_location = $image['tmp_name'][$i]; 
            User::s3UploadDirect('user/', $file_name, $temp_file_location);
            $im = new Images();
            $im->user_id = $user_id; 
            $im->avator = 0;
            $im->created_at = time();
            $im->path = env('AWS_S3_PLUZO').'user/'.$file_name;
            $im->sort = $n;
            $im->save();
            $n++;
        }

        $images = Images::find()->where(['user_id'=>$user_id])->orderBy(['sort' => SORT_ASC])->one();
        $user = User::find()->where(['id'=>$user_id])->one();
        if($images){
            $user->image = $images->path;
            $user->save();
        } else {
            $user->image = '';
            $user->save();
        }
    }

    public function savePhoto($image)
    {   
        $images = Images::find()->where(['user_id'=>\Yii::$app->user->id])->orderBy(['sort' => SORT_DESC])->one();
        if($images){
            $n = $images->sort + 1;
        } else {
            $n = 0;
        }
        for ($i=0; $i < count($image['name']); $i++) { 
            $file_name = uniqid().'.jpg';   
            $temp_file_location = $image['tmp_name'][$i]; 
            User::s3UploadDirect('user/', $file_name, $temp_file_location);
            $im = new Images();
            $im->user_id = \Yii::$app->user->id; 
            $im->avator = 0;
            $im->created_at = time();
            $im->path = env('AWS_S3_PLUZO').'user/'.$file_name;
            $im->sort = $n;
            $im->save();
            $n++;
        }
    }

    public function getExpiredat()
    { 
        $token = Token::find()
            ->andwhere(['user_id' => $this->id])
            ->andwhere(['>','expired_at',time()])
            ->orderBy('id DESC')
            ->one();
            return $token->expired_at;
    }

    
    public static function deleteAccount($id)
    {   
        $party = Party::find()->where(['user_id'=>$id])->all();
        if($party){
            foreach ($party as $key => $value) {
                \Yii::$app
                ->db
                ->createCommand()
                ->delete('message', ['chat_id' => $value['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('chat', ['id' => $value['chat_id']])
                ->execute();

                \Yii::$app
                ->db
                ->createCommand()
                ->delete('party', ['chat_id' => $value['chat_id']])
                ->execute();

                $partner = Party::find()->where(['chat_id' => $value['chat_id']])->andwhere(['<>', 'user_id', $id])->one();
                if ($partner) {
                    \Yii::$app
                    ->db
                    ->createCommand()
                    ->delete('party', ['id' => $partner->id])
                    ->execute();
                }
            }
        }

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('client', ['id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('badge', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('token', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('images', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('chat', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_source_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('friend', ['user_target_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('like', ['user_source_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('like', ['user_target_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('message', ['user_id' => $id])
            ->execute();

        
        \Yii::$app
            ->db
            ->createCommand()
            ->delete('party', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('stream_user', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('client_setting', ['user_id' => $id])
            ->execute();

        \Yii::$app
            ->db
            ->createCommand()
            ->delete('advance', ['user_id' => $id])
            ->execute();

            
    }


    public function getToken()
    {   
        
        $token = Token::find()
            ->andwhere(['user_id' => $this->id])
            ->andwhere(['>','expired_at',time()])
            ->orderBy('id DESC')
            ->one();
            return $token->token;
    }

    public function getPassword()
    {
        return $_POST['password'];
    }

    public function checkNumber($number)
    {   
        $SnSclient = new SnsClient([
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
        ]
        ]);

        try {
            $result = $SnSclient->checkIfPhoneNumberIsOptedOut([
                'phoneNumber' => $number,
            ]);
            var_dump($result);
            die(1);
        } catch (AwsException $e) {
            print_r($e->getMessage());
            // output error message if fails
            error_log($e->getMessage());
            die(2);
        }
    }

    public function getRetailer()
    {   
        return $this->hasOne(Retailer::className(), ['user_id' => 'id']);        
    }

    public function getImages()
    {   
        return $this->hasMany(Images::className(), ['user_id' => 'id'])->
        orderBy(['sort' => SORT_ASC]);       
    }

    public static function setAvatar()
    { 
        $images = Images::find()->where(['user_id'=>\Yii::$app->user->id])->orderBy(['sort' => SORT_ASC])->one();
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        if($images){
            $user->image = $images->path;
            $user->save();
        } else {
            $user->image = '';
            $user->save();
        }
    }

    public function searchUser($request)
    {
        if(!$request->post('search')){
            throw new \yii\web\HttpException('500','search cannot be blank.'); 
        }
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("Select ".User::userFields()." from `client` where `username` Like '%".$request->post('search')."%' OR `first_name` Like '%".$request->post('search')."%' OR `last_name` Like '%".$request->post('search')."%'");
        $result = $command->queryAll();
        $users = [];
        $us1 = User::bannedUsers();
        $us2 = User::whoBannedMe();
        foreach ($result as $key => $value) {

            //banned users
            if (in_array($value['id'], $us1)) {
                continue;
            }
            if (in_array($value['id'], $us2)) {
                continue;
            }

            $images = $command = $connection->createCommand("SELECT `images`.`id`, `images`.`path`  FROM `images` WHERE `user_id`=".$value['id']." ORDER BY  `sort` ASC");
            $result_images = $command->queryAll();
            $ar = [
                'id'=>$value['id'],
                'username'=>$value['username'],
                'phone'=>$value['phone'],
                'image'=>$value['image'],
                'gender'=>$value['gender'],
                'birthday'=>$value['birthday'],
                'age'=>User::getAge($value['birthday']),
                'status'=>$value['status'],
                'first_name'=>$value['first_name'],
                'last_name'=>$value['last_name'],
                'latitude'=>$value['latitude'],
                'longitude'=>$value['longitude'],
                'address'=>$value['address'],
                'city'=>$value['city'],
                'state'=>$value['state'],
                'last_activity'=>$value['last_activity'],
                'premium'=>User::checkPremium($value['id']),
                'images'=>$result_images,
                'friends'=>User::friendCount($value['id']),
                'badges'=>Badge::getBadge($value['id']),
                'first_login'=>$value['first_login'],
            ];
            array_push($users, $ar);
        }
        return $users;
    }

    public function userFields(){
        return '`client`.`id`, `client`.`username`, `client`.`phone`, `client`.`image`, `client`.`gender`, `client`.`birthday`, `client`.`status`, `client`.`first_name`, `client`.`last_name`, `client`.`latitude`, `client`.`longitude`, `client`.`address`, `client`.`city`, `client`.`state`, `client`.`last_activity`, `client`.`premium`, `client`.`first_login`';
    }

    public static function getPhoto($user_id)
    {
        $user = User::find()->where(['id'=>$user_id])->one();
        return $user->image;
    }

    public static function getFirstName($user_id)
    {
        $user = User::find()->where(['id'=>$user_id])->one();
        return $user->first_name;
    }

    public static function getCountry($user_id)
    {
        $user = User::find()->where(['id'=>$user_id])->one();
        return $user->address;
    }

    public function getLocationSwipe($lat, $long)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&sensor=false&key=".self::GOOGLE_MAP_KEY."&language=en";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        $curlData = curl_exec($curl);
        curl_close($curl);
        $address = json_decode($curlData, true);
        return $address['results'][0]['formatted_address'];

    }

    public function getAddress($lat, $long)
    {
        $url = "https://maps.googleapis.com/maps/api/geocode/json?latlng=$lat,$long&sensor=false&key=".self::GOOGLE_MAP_KEY."&language=en";
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_ENCODING, "");
        $curlData = curl_exec($curl);
        curl_close($curl);
        $address = json_decode($curlData, true);
        $country = '';
        $state = '';
        $city = '';
        for ($i=0; $i < count($address['results'][0]['address_components']); $i++) { 
            if ($address['results'][0]['address_components'][$i]['types'][0] == 'country') {
                $country = $address['results'][0]['address_components'][$i]['long_name'];
            }
            if ($address['results'][0]['address_components'][$i]['types'][0] == 'administrative_area_level_1') {
                $state = $address['results'][0]['address_components'][$i]['long_name'];
            }
            if ($address['results'][0]['address_components'][$i]['types'][0] == 'locality') {
                $city = $address['results'][0]['address_components'][$i]['long_name'];
            }
        }
        if($country != 'United States'){
            $state = NULL;
        }
        
        return [
            'country'=>$country,
            'state'=>$state,
            'city'=>$city,
        ];

    }

    public function Sms_aws($phone, $message)
    {    
        $SnSclient = new SnsClient([
        'region' => 'us-east-1',
        'version' => 'latest',
        'credentials' => [
            'key'    => env('AWS_KEY'),
            'secret' => env('AWS_SECRET'),
        ]
        ]);

        $phone = str_replace(' ', '', $phone);
        $message = $message;
        $result = $SnSclient->publish([
                'Message' => $message,
                'PhoneNumber' => $phone,
            ]);
       
    }
    
    public function Sms($phone, $message)
    {   
        if(isset($_POST['android']) AND $_POST['android'] == 1){
            $message = $message.' 

'.env('SMS_HASH');
        }

        Yii::$app->queue->push(new Sms([
            'phone'=>$phone,
            'message' => $message,
        ]));      
    }

    public static function s3Upload($catalog, $file_name, $temp_file_location){
            $putdata = fopen($temp_file_location, "r");
            $path = \Yii::getAlias('@webroot') . '/uploads/'. $file_name;

            $fp = fopen($path, "w");
            while ($data = fread($putdata, 1024))
            fwrite($fp, $data);       
            fclose($fp);
            fclose($putdata);
            Yii::$app->queue->push(new UploadImageAmazon([
                'catalog'=>$catalog,
                'file_name' => $file_name,
                'path'=>$path,
            ]));
    }


    public static function photoReduce($file_name){
        $dir = \Yii::getAlias('@webroot') . '/uploads/'.$file_name;
        if(filesize($dir) > 150000){
            Image::getImagine()->open($dir)->save($dir, ['jpeg_quality' => 100]);
        }
    }

    public static function s3UploadDirect($catalog, $file_name, $temp_file_location){

        $putdata = fopen($temp_file_location, "r");
            $filename = \Yii::getAlias('@webroot') . '/uploads/'. $file_name;
            $fp = fopen($filename, "w");

        while ($data = fread($putdata, 1024))
            fwrite($fp, $data);       
            fclose($fp);
            fclose($putdata);

        User::photoReduce($file_name);

            $s3Client = new S3Client([
                'region' => 'us-east-2',
                'version' => '2006-03-01',
                'credentials' => [
                        'key'    => env('AWS_KEY'),
                        'secret' => env('AWS_SECRET'),
                    ],
            ]);
            $temp_file_location = \Yii::getAlias('@webroot') . '/uploads/'.$file_name;
            $result = $s3Client->putObject(
                array(
                    'Bucket'=>'pluzo',
                    'Key'    => $catalog.$file_name,
                    'SourceFile' => $temp_file_location,
                    'ACL' => 'public-read',
                    'ContentType' => 'image',
                )
            );
            unlink($temp_file_location);
    }


    public static function s3UploadDirectFull($catalog, $file_name, $temp_file_location){

        $putdata = fopen($temp_file_location, "r");
            $filename = \Yii::getAlias('@webroot') . '/uploads/'. $file_name;
            $fp = fopen($filename, "w");

        while ($data = fread($putdata, 1024))
            fwrite($fp, $data);       
            fclose($fp);
            fclose($putdata);

            $s3Client = new S3Client([
                'region' => 'us-east-2',
                'version' => '2006-03-01',
                'credentials' => [
                        'key'    => env('AWS_KEY'),
                        'secret' => env('AWS_SECRET'),
                    ],
            ]);
            $temp_file_location = \Yii::getAlias('@webroot') . '/uploads/'.$file_name;
            $result = $s3Client->putObject(
                array(
                    'Bucket'=>'pluzo',
                    'Key'    => $catalog.$file_name,
                    'SourceFile' => $temp_file_location,
                    'ACL' => 'public-read',
                    'ContentType' => 'image',
                )
            );
            unlink($temp_file_location);
    }

    public static function s3delete($file_name){

        $file_name = explode('/', $file_name);
        $key = $file_name[3].'/'.$file_name[4];

        $s3Client = new S3Client([
            'region' => 'us-east-2',
            'version' => '2006-03-01',
            'credentials' => [
                    'key' => env('AWS_KEY'),
                    'secret' => env('AWS_SECRET'),
            ],
        ]);

        $result = $s3Client->deleteObject([
            'Bucket'=>'pluzo',
            'Key'=> $key,
        ]);

    }

    public static function socket($user, $data, $action){

        $client = new WEBCLIENT(self::WEB_CLIENT_ADDRESS);

        $messageData = [
            'user'=>(int)$user,
            'action'=>$action,
            'data'=>json_encode($data, true)
        ];

        $message = json_encode($messageData); 
        $client->send($message);
        $client->close();

    }
}   

