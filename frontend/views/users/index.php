<?php

use yii\helpers\Html;
use api\models\UserMsg;
use api\models\Friend;
use api\models\StreamUser;
use api\models\Images;
use api\models\Like;
use api\models\Message;
use api\models\Party;
use api\models\Stream;
use common\models\Analit;
use common\models\Client;
use yii\helpers\Url;
use kartik\grid\GridView;

$this->registerAssetBundle(yii\web\JqueryAsset::className(), \yii\web\View::POS_HEAD);
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Users';
\yii\web\YiiAsset::register($this);
?>
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
<script src="http://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js" type="text/javascript"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.css">
<script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert/1.1.3/sweetalert.min.js"></script>

<div class="user-index">

    <p>
        <a href="/users/index" class="btn btn-info">Clear filters</a>
        </p>
    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        'columns' => [
            [
                'attribute'=>'id',
                'contentOptions' => ['style' => 'width:80px;'],
                'format' => 'raw',
                'value' => function ($data) {
                    //total time on app
                    $t_t = 0;
                    $total_time = Analit::find()->where(['user_id'=>$data->id])->all();
                    foreach ($total_time as $key => $value) {
                        $t_t = $t_t + $value['during'];
                    }
                    $total_time = gmdate("H:i:s", $t_t);
                    //# of friends
                    $fr_numb = $data->count_friend;
                    //# of lives
                    $lives = Stream::find()->where(['user_id'=>$data->id, 'stop'=>1])->count();
                    //ratio
                    $u_l = Like::find()->where(['user_source_id'=>$data->id])->andwhere(['IN','like', [1,2]])->count();
                    $u_d = Like::find()->where(['user_source_id'=>$data->id])->andwhere(['IN','like', [0]])->count();
                    $total_likes_dis = $u_l + $u_d;
                    $ratio = $u_l.'/'.$u_d.'/'.$total_likes_dis;
                    //# of conversations
                    $convers = Party::find()->where(['user_id'=>$data->id])->count();

                    $popup = '<a href="#" onclick="popup_open(\''.$data->first_name.' '.$data->last_name.'\','.$data->id.',\''.$total_time.'\',\''.$fr_numb.'\',\''.$lives.'\',\''.$ratio.'\',\''.$convers.'\'); return false">'.$data->id.'</a>';
                            return $popup;
                }
            ],
            [
                'attribute'=>'username',
                'contentOptions' => ['style' => 'width:10px;'],
                'value' => function ($data) {
                    return Html::a($data->username, Url::to(['info', 'id' => $data->id]));
                },
                'format' => 'raw',
            ],
            [
                'attribute'=>'first_name',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute' => 'gender',
                'format' => 'html',
                'value' => function($data) { 
                    if($data->gender == 1){ return 'Male';}
                    if($data->gender == 2){ return 'Female';}
                },
                'filter'=>array("1"=>"Male","2"=>"Female"),
                'contentOptions' => ['style' => 'min-width:120px;'],
            ],
            [   
                'attribute' => 'address',
                'value' => function ($data) {
                    $full = '';
                    if(isset($data->address)){
                        $full = $data->address;
                    }
                    if(isset($data->city)){
                        $full = $full.', '.$data->city;
                    }
                    if(isset($data->state)){
                        $full = $full.', '.$data->state;
                    }
                    return $full;
                },
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute' => 'image',
                'contentOptions' => ['style' => 'width:60px;'],
                'filter' => false,
                'header' => 'Profile',
                'format' => 'html',
                'value' => function($data) { 
                    return Html::a(Html::img($data->image, ['width'=>'45', 'height'=>'45', 'class'=>'img-circle']), Url::to(['view', 'id' => $data->id]));
                },
            ],
            [
                'attribute'=>'birthday',
                'contentOptions' => ['style' => 'width:100px;'],
                'filter' => false,
                'value' => function($data) { 
                    return date('Y-m-d',$data->birthday);
                },
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute'=>'phone',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute'=>'count_friend',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute'=>'count_swipes',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute'=>'created_at',
                'contentOptions' => ['style' => 'width:100px;'],
                'filter' => false,
                'value' => function($data) { 
                    return date('Y-m-d',$data->created_at);
                },
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute' => 'Track',
                'value' => function ($data) {
                    $find = Analit::find()->where(['user_id'=>$data->id])->count();
                    if ($find) {
                        return Html::a('<i class="fas fa-chart-pie"></i>', Url::to(['analit/index', 'id' => $data->id]));
                    } else {
                        return '';
                    }
                    
                },
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:10px;'],
            ],
            [
                'attribute' => 'premium',
                'value' => function ($data) {
                    if ($data->premium) {
                        $result = '<span class="badge badge-success">Yes</span>';
                    } else {
                        $result = '<span class="badge badge-secondary">No</span>';
                    }
                    return $result;
                },
                'filter'=>array("1"=>"Yes","0"=>"No"),
                'format' => 'raw',
                'contentOptions' => ['style' => 'min-width:100px;'],
            ],
            [
                'class' => \common\widgets\ActionColumn::class,
                'template' => '{update}{delete}',
            ],
        ],
    ]); ?>
</div>
<div class="modal fade" id="modal-popup">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h4 class="modal-title">User info</h4>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">

<table id="w0" class="table table-striped table-bordered detail-view"><tbody>
    <tr><th>Name</th><td id="q1"></td></tr>
    <tr><th>Username</th><td id="q2"></td></tr>
    <tr><th>Total time on app</th><td id="q3"></td></tr>
    <tr><th># of friends</th><td id="q4"></td></tr>
    <tr><th># of lives</th><td id="q5"></td></tr>
    <tr><th>Ratio</th><td id="q6"></td></tr>
    <tr><th># of conversations</th><td id="q7"></td></tr>
</tbody></table>
            </div>
          </div>
        </div>
      </div>

<script type="text/javascript">
    function popup_open($q1, $q2, $q3, $q4, $q5, $q6, $q7){
        $('#q1').html($q1);
        $('#q2').html($q2);
        $('#q3').html($q3);
        $('#q4').html($q4);
        $('#q5').html($q5);
        $('#q6').html($q6);
        $('#q7').html($q7);
        $('#modal-popup').modal('show');
    }
</script>