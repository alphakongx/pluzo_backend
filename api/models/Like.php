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
use api\models\LikeOpt;
use api\components\PushHelper;

/**
 * This is the model class for table "like".
 *
 * @property int $id
 * @property int $user_source_id
 * @property int $user_target_id
 * @property int $like
 * @property string|null $created_at
 */
class Like extends \yii\db\ActiveRecord
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

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['user_source_id', 'user_target_id', 'like'], 'required'],
            [['user_source_id', 'user_target_id', 'like'], 'integer'],
            
        ];
    }

    public static function checkBirthday($birthday)
    {  
        $difference = time() - $birthday;
        $age = floor($difference / 31556926);
        if($age < 13){
            throw new \yii\web\HttpException('500','User age < 13');
        }
        return $age;
    }

    public static function createSwipeSettingSignup($id, $gender, $latitude, $longitude, $birthday)
    {   
        $user_age = self::checkBirthday($birthday);
        if($user_age < 18 ){
            $age_from = 13;
            $age_to = 18;
        } else {
            $age_from = 18;
            $age_to = 25;
        }
        $create = new SwipeSetting();
        $create->user_id = $id;
        $create->age_from = $age_from;
        $create->age_to = $age_to;
        $create->global = 1;
        $create->gender = $gender;
        $create->current_location = 1;
        $create->distance = self::DISTANCE_DEFAULT;
        if(isset($latitude) AND isset($longitude)){
            $create->latitude = $latitude;
            $create->longitude = $longitude;
            $create->location = User::getLocationSwipe($latitude, $longitude);
        }
        if($create->save()){
            return true;
        } 
    }

    public function createSwipeSetting()
    {   
        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        $user_age = self::checkBirthday($user->birthday);
        if($user_age < 18 ){
            $age_from = 13;
            $age_to = 18;
        } else {
            $age_from = 18;
            $age_to = 25;
        }
        if($user->address == 'United States'){
            $country = $user->address;
            $state = $user->state;
        } else {
            $country = $user->address;
            $state = null;
        }
        if ($user->city) {
            $city = $user->city;
        } else {
            $city = null;
        }

        $create = new SwipeSetting();
        $create->user_id = \Yii::$app->user->id;
        $create->age_from = $age_from;
        $create->age_to = $age_to;
        $create->global = 1;
        $create->current_location = 1;
        $create->country = $country;
        $create->state = $state;
        $create->city = $city;
        $create->gender = self::GENDER_DEFAULT;
        $create->distance = self::DISTANCE_DEFAULT;
        if(isset($user->latitude) AND isset($user->longitude)){
            $create->latitude = $user->latitude;
            $create->longitude = $user->longitude;
            $create->location = User::getLocationSwipe($create->latitude, $create->longitude);
        }
        if($create->save()){
            Yii::$app->cache->set('swipe_settings'.\Yii::$app->user->id, $create);
            return $create;
        } else {
            throw new \yii\web\HttpException('500','Error save get save'); 
        }
    }

    public function getSwipeSetting()
    {   
        $data = Yii::$app->cache->getOrSet('swipe_settings'.\Yii::$app->user->id, function () {
            $check = SwipeSetting::find()->where(['user_id'=>\Yii::$app->user->id])->one();
            if($check){
                return $check;
            } else {
                return Like::createSwipeSetting();
            }  
        });
        return $data;
    }
    
    public function setSwipeSetting($request)
    {   
        $gender = $request->post('gender');
        $latitude = $request->post('latitude');
        $longitude = $request->post('longitude');
        $global = $request->post('global');
        $age_from = $request->post('age_from');
        $age_to = $request->post('age_to');
        $distance = $request->post('distance');
        $hide = $request->post('hide');

        $user = User::find()->where(['id'=>\Yii::$app->user->id])->one();

        if (isset($gender)) {
            if ($gender != 0 AND $gender != 1 AND $gender != 2) {
                throw new \yii\web\HttpException('500','gender can be 0 or 1 or 2');
            }
        }
        $cur_loc = $request->post('current_location');
        if (isset($cur_loc)) {
            if ($cur_loc != 0 AND $cur_loc != 1) {
                throw new \yii\web\HttpException('500','current_location can be 0 or 1');
            }
            if ($request->post('current_location') == 1) {
                if($user->address == 'United States'){
                    $country = $user->address;
                    $state = $user->state;
                } else {
                    $country = $user->address;
                    $state = null;
                }
                if ($user->city) {
                    $city = $user->city;
                } else {
                    $city = null;
                }
            } else {
                $country = $request->post('country');
                $state = $request->post('state');
                $city = $request->post('city');
                if($country == 'United States'){
                    if (!$state) {
                       throw new \yii\web\HttpException('500','"state" cannot be blank for country "United States"');
                    }
                } else {
                    $state = null;
                }
                if (!$city) {
                    $city = null;
                }   

                if (!$country) {
                    throw new \yii\web\HttpException('500','country cannot be blank for current_location = 0');
                }
                
                $user->address = $country;
                $user->state = $state;
                $user->city = $city;
                $user->save();
                $socket = [
                    'user'=>Stream::userForApi($user->id),
                ];
                User::socket(0, $socket, 'User_update');
            }
        }
        
        $host_age = self::checkBirthday($user->birthday);
        if($host_age < 18 ){
            if(isset($age_from)){
                if($age_from < 13){
                    throw new \yii\web\HttpException('500','For your age you can set swipe setting only 13 - 17');
                }
                if($age_from > 17){
                    throw new \yii\web\HttpException('500','For your age you can set swipe setting only 13 - 17');
                }
            }
            if(isset($age_to)){
                if($age_to > 17){
                    throw new \yii\web\HttpException('500','For your age you can set swipe setting only 13 - 17');
                }
            }
        }
        if ($host_age >= 18) {
            if(isset($age_from)){
                if($age_from < 18){
                    throw new \yii\web\HttpException('500','For your age you can set swipe setting only more than 17');
                }
            }
        }
        $check = SwipeSetting::find()->where(['user_id'=>\Yii::$app->user->id])->one();
        if($check){
            if (isset($hide)) { $check->hide = (int)$hide; }
            if (isset($gender)) { $check->gender = $gender; }
            if (isset($latitude)) { $check->latitude = $latitude; }
            if (isset($longitude)) { $check->longitude = $longitude; }
            if (isset($cur_loc)) { 
                $check->current_location = $cur_loc;
                $check->country = $country;
                $check->state = $state;
                $check->city = $city;
            }
            
            if (isset($global)) { $check->global = (int)$global; }
            if (isset($age_from) AND $age_from > 0) { $check->age_from = (int)$age_from; }
            if (isset($age_to) AND $age_to > 0) { $check->age_to = (int)$age_to; }
            if (isset($distance) AND $distance > 0) { $check->distance = (int)$distance; }
            if($check->save()){
                Yii::$app->cache->set('swipe_settings'.\Yii::$app->user->id, $check);
                return $check;
            } else {
                throw new \yii\web\HttpException('500','Error save update'); 
            }
        } else {
            return Like::createSwipeSetting();
        }
    }

    public function sendLikeAll($request)
    {   
        $result = [];
        $is_like = (int)$request->post('is_like');
        $user_target_id = (array)$request->post('user_target_id');
        if (count($user_target_id) < 1) {
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        if(!isset($is_like)){
            throw new \yii\web\HttpException('500','is_like cannot be blank.'); 
        }
        foreach ($user_target_id as $key => $value) {
            $like = Like::sendLike((int)$value, $is_like);
            array_push($result, $like);
        }

        return
        [
            'result' => $result,
            'like_info' => Like::getLikeInfo(),
        ];
    }



    public function getLikedUsers()
    {   
        $us = User::find()->where(['id'=>\Yii::$app->user->id])->one();
        $your_likes = Like::find()->select('user_target_id')->where(['user_source_id'=>\Yii::$app->user->id])->asArray()->all();
        $your_likes_array = [0];
        foreach ($your_likes as $key => $value) {
            array_push($your_likes_array, $value['user_target_id']);
        }

        $friends = [0];
        $connection = Yii::$app->getDb();
        $sql = "SELECT ".User::userFields()." FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $key => $value) {
            array_push($friends, $value['id']);
        }

        $all = Like::find()
        ->where(['user_target_id'=>\Yii::$app->user->id])
        ->andwhere(['in', 'like', [self::LIKE,self::SUPER_LIKE]])
        ->andWhere(['not in', 'user_source_id', $your_likes_array])
        ->andWhere(['not in', 'user_source_id', $friends])
        ->orderby(['created_at'=>SORT_DESC])
        ->all();
        $users = [];
        foreach ($all as $key => $value) {
            $r = UserGetLike::find()->where(['id'=>$value['user_source_id']])->one();
            $r = [
                    'like'=>$value['like'],
                    'user'=>$r
                ];
            array_push($users, $r);
        }
        return $users;
    }

    public function sendLike($user_target_id, $is_like)
    {    
        if(!$user_target_id){
            throw new \yii\web\HttpException('500','user_target_id cannot be blank.'); 
        }
        if(!isset($is_like)){
            throw new \yii\web\HttpException('500','is_like cannot be blank.'); 
        }
        if($user_target_id == \Yii::$app->user->id){
             throw new \yii\web\HttpException('500','user_target_id can not be your ID'); 
        }
        $match = 0;
        $like = Like::find()->where(['user_source_id'=>\Yii::$app->user->id, 'user_target_id'=>$user_target_id])->one();
        if($like){
            throw new \yii\web\HttpException('500','Like already sent'); 
        } else {
            //super_like
            if ($is_like == 2) {
                //check premium
                $info = User::getPremiumInfo();
                if ($info['premium'] == 1) {
                    if($info['super_likes_used_today'] >= 5){
                        //use bought
                        if(Advance::runService(Advance::SUPER_LIKE)){
                        } else {
                            $next_upd = $info['super_like_reset_date'] - time();
                            throw new \yii\web\HttpException('500','Pluzo+ users get only 5 super likes per day! Next update after '.User::secondsToTime($next_upd).'! Also you can buy more super likes now!');
                        }
                    } else {
                        $p_use = new PremiumUse();
                        $p_use->time = time();
                        $p_use->user_id = \Yii::$app->user->id;
                        $p_use->type = Advance::SUPER_LIKE;
                        $p_use->boost_type = $type;
                        $p_use->premium_id = 0;
                        $socket = [
                            'user'=>Stream::userForApi(\Yii::$app->user->id),
                        ];
                        User::socket(0, $socket, 'User_update');
                        $p_use->save();
                    }
                } else {
                    if(Advance::runService(Advance::SUPER_LIKE)){} else {
                        throw new \yii\web\HttpException('500','Need buy more super likes!');
                    }
                }
            }

            $like = new Like();
            $like->user_source_id = \Yii::$app->user->id;
            $like->user_target_id = $user_target_id;
            $like->created_at = time();
            $like->like = $is_like;
            if($like->save()){
                //friends if overlap likes
                if($is_like == self::LIKE OR $is_like == self::SUPER_LIKE){
                    $like2 = Like::find()->where(['user_source_id'=>$user_target_id, 'user_target_id'=>\Yii::$app->user->id])->andwhere(['in','like', [self::LIKE,self::SUPER_LIKE]])
                    ->one();
                    if($like2){
                        Friend::likeOverlapFriends($user_target_id);
                        $match = 1;
                    }
                }
            }
        }
        if($is_like == self::LIKE OR $is_like == self::SUPER_LIKE){
            Indicator::checkLike(\Yii::$app->user->id, $user_target_id, $is_like);
        }
        $target_result = Stream::userForApi($user_target_id);
        $socket = [
            'user'=>$target_result,
        ];


        //if 3 likes - send push
        self::sendLikePush($user_target_id);

        User::socket(0, $socket, 'User_update');
        return [
            'like_match'=>$match,
            'host'=>User::find()->where(['id'=>\Yii::$app->user->id])->one(),
            'user_target_id'=>$target_result,
            'like'=>$like
        ];
    }

    public static function sendLikePush($user_target_id){
        $user_target = User::find()->where(['id'=>$user_target_id])->one();
        if ($user_target->likes >= 3 ) {
            $message = 'You have 3 new likes! Tap to see who.';
            $data = array("action" => "likes");
            //PushHelper::send_push($user_target, $message, $data);
            $user_target->likes == 0;
        } else {
            $user_target->likes = $user_target->likes + 1;
        }
        $user_target->save();
    }
    

    public static function checkLike($user_id){
        $check = Like::find()->where(['user_source_id'=>$user_id, 'user_target_id'=>\Yii::$app->user->id])
        ->andwhere(['in','like', [self::LIKE,self::SUPER_LIKE]])
        ->one();
        if($check){
            return true;
        } else {
            return false;
        }
    }

    public static function getLike($user_id)
    {
        $dis = 0;
        $like = 0;
        $super = 0;

        $your_likes = Like::find()->select('user_target_id')->where(['user_source_id'=>$user_id])->asArray()->all();
        $your_likes_array = [0];
        foreach ($your_likes as $key => $value) {
            array_push($your_likes_array, $value['user_target_id']);
        }

        //friends
        $friends = [0];
        $connection = Yii::$app->getDb();
        $sql = "SELECT ".User::userFields()." FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".$user_id;
        $command = $connection->createCommand($sql);
        $result = $command->queryAll();
        foreach ($result as $key => $value) {
            array_push($friends, $value['id']);
        }

        $likes = Like::find()
        ->where(['user_target_id'=>$user_id])
        ->andWhere(['not in', 'user_source_id', $your_likes_array])
        ->andWhere(['not in', 'user_source_id', $friends])
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
        $sum = $like + $super;
        return [
            'dislike' => $dis,
            'like' => $like,
            'superlike' => $super,
            'like_sum' => $sum,
        ];
    }

    public function getLikeInfo()
    {
        $dis = 0;
        $like = 0;
        $super = 0;

        $your_likes = Like::find()->select('user_target_id')->where(['user_source_id'=>\Yii::$app->user->id])->asArray()->all();
        $your_likes_array = [0];
        foreach ($your_likes as $key => $value) {
            array_push($your_likes_array, $value['user_target_id']);
        }

        $likes = Like::find()
        ->where(['user_target_id'=>\Yii::$app->user->id])
        ->andWhere(['not in', 'user_source_id', $your_likes_array])
        ->all();
        
        //$likes = Like::find()->where(['user_target_id'=>\Yii::$app->user->id])->all();
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

    public function getMatch()
    {
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT l2.user_source_id, ".User::userFields()." FROM `like` l1 
            INNER JOIN `like` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id." AND (l1.like = 1 OR l2.like = 1 OR l2.like = 2 OR l2.like = 2)  AND (l1.like <> 0 AND l2.like <> 0)");
        $result = $command->queryAll();
        return 
        [            
            'result' => $result,
            'like_info' => Like::getLikeInfo(),
        ]; 
    }

    public function swipeSearch($request)
    {   
        if(!$request->post('latitude')){
            throw new \yii\web\HttpException('500','latitude cannot be blank.'); 
        }
        if(!$request->post('longitude')){
            throw new \yii\web\HttpException('500','longitude cannot be blank.'); 
        }
        $gender = $request->post('gender');
        if(!isset($gender)) {
            throw new \yii\web\HttpException('500','gender cannot be blank.'); 
        }

        if(!$request->post('age_from')){
            $age_from = self::AGE_FROM_DEFAULT; 
        } else {
            $age_from = $request->post('age_from');
        }
        if(!$request->post('age_to')){
            $age_to = self::AGE_TO_DEFAULT;
        } else {
            $age_to = $request->post('age_to');
        }
        $lat = $request->post('latitude');
        $lon = $request->post('longitude');
        $gender = $request->post('gender');
        $id = \Yii::$app->user->id;
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand("SELECT *, YEAR(CURRENT_TIMESTAMP)-FROM_UNIXTIME(`birthday`, '%Y') AS `age`, ".DistanceHelper::distance($lat, $lon)." AS `distance` FROM client WHERE `gender`=".$gender." HAVING `distance` < 1000 AND `age`>=".$age_from." AND `age`<=".$age_to);      

        $result = $command->queryAll();

        $ar1 = [];
        foreach ($result as $key => $value) {
            array_push($ar1, $value['id']);
        }

        $distance = [];
        foreach ($result as $key => $value) {
            array_push($distance, [$value['id']=>$value['distance']]);
        }

        $like = Like::find()->select('user_target_id')->where(['user_source_id'=>\Yii::$app->user->id])->asArray()->all();
        $ar2 = [];
        foreach ($like as $key => $value) {
            array_push($ar2, $value['user_target_id']);
        }
        $swipe = User::find()
        ->where(['<>','id', \Yii::$app->user->id])
        ->andWhere(['in', 'id', $ar1])
        ->andWhere(['not in', 'id', $ar2])
        ->orderBy([
        'id' => SORT_DESC     
        ])
        ->all();  
        return 
        [    
            'swipe' => $swipe,
            'distance'=>$distance,
            'like_info' => Like::getLikeInfo(),
        ];
    }

    public function swipe()
    {   
        $setting = Like::getSwipeSetting();
        $user = Yii::$app->user->identity;
        $my_age = date_diff(date_create(date('Y-m-d', $user->birthday)), date_create('today'))->y;
        $lat = NULL;
        $lon = NULL;
        
        if($setting->distance){ $distance = $setting->distance; } else { $distance=self::DISTANCE_DEFAULT; }
        if($setting->gender){ $gender = $setting->gender; } else { $gender=self::GENDER_DEFAULT; }
        if (isset($user->latitude) AND isset($user->longitude)) {
            $lat = $user->latitude;
            $lon = $user->longitude;
        } else {
            $lat = 0;
            $lon = 0;
        }
        if($setting->age_from){ $age_from = $setting->age_from; } else { $age_from=self::AGE_FROM_DEFAULT; }
        if($setting->age_to){ $age_to = $setting->age_to; } else { $age_to=self::AGE_TO_DEFAULT; }
        if($gender == self::GENDER_DEFAULT){
            $gender_query = '`client`.`gender` IN ( 0, 1, 2 )';
        } else {
            $gender_query = '`client`.`gender`='.$gender.' AND (`swipe_setting`.`gender` = 0 OR `swipe_setting`.`gender` = '.$user->gender.')';
        }
        if ($setting->current_location == 1) {
            
            $dist = ' AND `dist` < "'.$distance.'" ORDER BY `dist` ASC';
            if ($setting->global == 1) {
                $dist = ' ORDER BY `dist` ASC';
            }
            $sql = 'SELECT *, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birthday), NOW()) AS `age_m`, '.DistanceHelper::distance($lat, $lon).' AS `dist`, `client`.`id` as `client_id`
            FROM client 
            LEFT JOIN `swipe_setting` ON `client`.`id` = `swipe_setting`.`user_id`
            WHERE '.$gender_query.' AND `swipe_setting`.`age_from` <= '.$my_age.' AND `swipe_setting`.`age_to` >= '.$my_age.'  AND `swipe_setting`.`hide` = 0 AND `client`.`status` = '.User::STATUS_ACTIVE.'
            HAVING `age_m`>='.$age_from.' AND `age_m`<="'.$age_to.'"'.$dist; 
        } else {
            $sort_address = 'AND `client`.`address` = "'.$setting->country.'"';
            if ($setting->global == 1) {
                $sort_address = '';
            }
            $sql = 'SELECT *, TIMESTAMPDIFF(YEAR, FROM_UNIXTIME(birthday), NOW()) AS `age_m`, `client`.`id` as `client_id`
            FROM client 
            LEFT JOIN `swipe_setting` ON `client`.`id` = `swipe_setting`.`user_id`
            WHERE '.$gender_query.' AND `swipe_setting`.`age_from` <= '.$my_age.' '.$sort_address.' AND `swipe_setting`.`age_to` >= '.$my_age.'  AND `swipe_setting`.`hide` = 0  AND `client`.`status` = '.User::STATUS_ACTIVE.' HAVING `age_m`>='.$age_from.' AND `age_m`<="'.$age_to.'" ORDER BY field(`client`.`address`,"'.$user->address.'") DESC';
        }
        
        $connection = Yii::$app->getDb();
        $command = $connection->createCommand($sql);      
        $result = $command->queryAll();
        if(count($result) == 0){
            return 
            [    
                'swipe' => [],
                'like_info' => Like::getLikeInfo(),
            ];
        }
        $sql_result = [];
        foreach ($result as $key => $value) {
            array_push($sql_result, $value['client_id']);
        }
        //boost users
        $boost = Advance::getBoostUsers(Advance::BOOST_TYPE_SWIPE);
        //sorted by liked you
        $start = self::getLikeYouForSwipe($boost);
        $start = array_merge($start,$boost);
        //friends
        $friends = [];
        $connection = Yii::$app->getDb();
        $sql = "SELECT ".User::userFields()." FROM `friend` l1 
            INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
            LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
            WHERE l1.user_source_id = ".\Yii::$app->user->id;
        $command = $connection->createCommand($sql);
        $result1 = $command->queryAll();
        foreach ($result1 as $key => $value) {
            array_push($friends, $value['id']);
        }
        $distance = [];
        foreach ($result as $key => $value) {
            array_push($distance, [$value['client_id']=>$value['dist']]);
        }
        //hide users you like/dislike
        $like = Like::find()->select('user_target_id')->where(['user_source_id'=>\Yii::$app->user->id])->asArray()->all();
        $like_unlike_users = [];
        foreach ($like as $key => $value) {
            array_push($like_unlike_users, $value['user_target_id']);
        }
        //hide removed friends
        $fr_users = Yii::$app->cache->getOrSet('removed_friends'.\Yii::$app->user->id, function () {
            $fr = FriendRemoved::find()->select('user_target_id')->where(['user_id'=>\Yii::$app->user->id])->asArray()->all();
            $fr_users = [];
            foreach ($fr as $key => $value) {
                array_push($fr_users, $value['user_target_id']);
            }
            return $fr_users;
        });

        $swipe = UserSwipe::find()
        ->where(['<>','id', \Yii::$app->user->id])
        ->andWhere(['in', 'id', $sql_result])
        ->andWhere(['not in', 'id', $like_unlike_users])
        ->andWhere(['not in', 'id', $friends])
        ->andWhere(['not in', 'id', $fr_users])
        ->andWhere(['not in', 'id', User::bannedUsers()])
        ->andWhere(['not in', 'id', User::whoBannedMe()])
        ->orderBy([new \yii\db\Expression('FIELD (id, ' . implode(',', $start) . ') DESC'), 'last_activity'=>SORT_DESC])
        ->limit(50)
        ->all();  

        return 
        [    
            'swipe' => $swipe,
            'distance'=>$distance,
            'like_info' => LikeOpt::getLikeInfo($like_unlike_users),
        ];
    }

    public static function getLikeYouForSwipe($boost){
        $like = Like::find()->select('user_source_id')->where(['user_target_id'=>\Yii::$app->user->id])
        ->andWhere(['in', 'like', [self::LIKE,self::SUPER_LIKE]])
        ->andWhere(['not in', 'user_source_id', $boost])
        ->asArray()
        ->all();
        $array = [0];
        foreach ($like as $key => $value) {
            array_push($array, $value['user_source_id']);
        }
        return $array;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_source_id' => 'User Source ID',
            'user_target_id' => 'User Target ID',
            'like' => 'Like',
            'created_at' => 'Created At',
        ];
    }
}
