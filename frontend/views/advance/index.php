<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use api\models\User;
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\AdvanceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Advances';
?>
<div class="advance-index">

<script type="text/javascript">
    function select_user(id){
        if(id == 0){
            window.location.href = "/advance/index";
        } else {
            window.location.href = "/advance/index?AdvanceSearch[user_id]="+id;
        }
    }
</script>

<p> 
    <?php
    $user = User::find()->where(['status'=>1])->all();
?>
    <label class="control-label" for="user_id">User</label>
    <select name="user_id" id="user_id" class="form-control" onchange="select_user(this.value)">
    <?php
    echo '<option value="0">All</option>';
    foreach ($user as $key => $value) {
        echo '<option value="'.$value['id'].'" ';
        if (isset($_GET['AdvanceSearch']['user_id'])) {
            if($value['id'] == $_GET['AdvanceSearch']['user_id']){
                echo 'selected ';
            }
        }
        echo '>'.$value['username'].'</option>';
    }
    ?>
    </select>
</p>




    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
                       [
            'attribute' => 'User',
            'value' => function ($data) {
                $username = User::find()->where(['id'=>$data->user_id])->one();
                if($username){
                    $username = ' <span class="badge badge-primary">'.$username->username.'</span>';
                } else {
                    $username = ' <span class="badge badge-danger">user removed</span>';
                }
                return Html::a(Html::encode('User').' '.$data->user_id.' '.$username, Url::to(['users/index', 'UserSearch[id]' => $data->user_id]));
            },
            'format' => 'raw',
        ],
        [
            'attribute' => 'Advance',
            'value' => function ($data) {
                $result = User::getAdvanced($data->user_id);
                $lb = $result['last_boost_time'];

                /*
                swipe_boost: <span class="badge badge-info">'.$lb['end_boost_swipe_time'].'</span><br>
                end_boost_swipe_time: <span class="badge badge-info">'.$lb['end_boost_swipe_time'].'</span><br>
                count_swipe: <span class="badge badge-info">'.$lb['count_swipe'].'</span><br>
                boost_swipe_remaining_time: <span class="badge badge-info">'.$lb['boost_swipe_remaining_time'].'</span><br>
                live_boost: <span class="badge badge-info">'.$lb['live_boost'].'</span><br>
                end_boost_live_time: <span class="badge badge-info">'.$lb['end_boost_live_time'].'</span><br>
                boost_live_remaining_time: <span class="badge badge-info">'.$lb['boost_live_remaining_time'].'</span><br>
                */
                $text = 'boosts: <span class="badge badge-success">'.$result['boosts'].'</span><br>
                super_likes: <span class="badge badge-success">'.$result['super_likes'].'</span><br>
                rewinds: <span class="badge badge-success">'.$result['rewinds'].'</span>';

                return $text;
            },
            'format' => 'raw',
        ],

        
            /*['attribute' => 'created_at', 'format' => ['date', 'php:d-m-Y H:i:s']],
            ['attribute' => 'used_time', 'format' => ['date', 'php:d-m-Y H:i:s']],
            ['attribute' => 'expires_at', 'format' => ['date', 'php:d-m-Y H:i:s']],
       
            'payment_id',*/
            //'status',
            //'type',


        ],
    ]); ?>


</div>
