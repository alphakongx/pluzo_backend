<?php

use yii\helpers\Html;
use api\models\UserMsg;
use api\models\Friend;
use api\models\StreamUser;
use api\models\Images;
use api\models\Like;
use common\models\Analit;
use common\models\Client;
use yii\helpers\Url;
use kartik\grid\GridView;
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
//$this->params['breadcrumbs'][] = $this->title;
?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<div class="user-index">

    <p>
        <a href="/users/index" class="btn btn-info">Back</a>
    </p>
                    <?php
                    $id = $_GET['id'];
                    $data = Client::find()->where(['id'=>$id])->one();
                    //total time on app
                    $t_t = 0;
                    //number of times the app was opened
                    $t_o = 'no data';
                    //number of live streams a user starts
                    $s_s = 'no data';
                    //number of live streams a user joins
                    $s_j = StreamUser::find()->where(['user_id'=>$id])->count();
                    //number of pictures in a users profile
                    $n_p = Images::find()->where(['user_id'=>$id])->count();
                    //number of character in a user’s bio
                    $u_b = strlen($data->bio);
                    //A users ratio (like/dislike number)
                    $u_l = Like::find()->where(['user_source_id'=>$id])->andwhere(['IN','like', [1,2]])->count();
                    $u_d = Like::find()->where(['user_source_id'=>$id])->andwhere(['IN','like', [0]])->count();
                    //number of times a user clicks on Pluzo+ but doesn’t buy
                    $n_b = 'no data';

                    $total_time = Analit::find()->where(['user_id'=>$id])->all();
                    foreach ($total_time as $key => $value) {
                        $t_t = $t_t + $value['during'];
                    }
                    $t_t = gmdate("H:i:s", $t_t);

                    $result = 'Total time on app: <span class="badge badge-info">'.$t_t.'</span> sec<br>
                    Number of times the app was opened: <span class="badge badge-info">'.$t_o.'</span><br>
                    Number of live streams a user starts: <span class="badge badge-info">'.$s_s.'</span><br>
                    Number of live streams a user joins: <span class="badge badge-info">'.$s_j.'</span><br>
                    Number of pictures in a users profile: <span class="badge badge-info">'.$n_p.'</span><br>
                    Number of character in a user’s bio: <span class="badge badge-info">'.$u_b.'</span><br>
                    A users ratio (like/dislike number): <span class="badge badge-info">'.$u_l.'/'.$u_d.'</span><br>
                    number of times a user clicks on Pluzo+ but doesn’t buy: <span class="badge badge-info">'.$n_b.'</span><br>
                    ';
                    echo $result;

?>
</div>