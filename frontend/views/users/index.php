<?php

use yii\helpers\Html;
use api\models\UserMsg;
use api\models\Friend;
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



    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        //'showPageSummary' => true,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            [
                'attribute'=>'id',
                'contentOptions' => ['style' => 'width:10px;'],
                'filter' => false,
                'header'=>'ID',
            ],


            [
                'attribute'=>'username',
                'contentOptions' => ['style' => 'width:10px;'],
                
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
                'filter' => false,
                'contentOptions' => ['style' => 'width:10px;'],
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
                'filter' => false,
                'contentOptions' => ['style' => 'width:10px;'],
            ],


          
            
           

            

            [
                'attribute' => 'image',
                'contentOptions' => ['style' => 'width:60px;'],
                'filter' => false,
                'header' => 'Profile',
                'format' => 'html',
                'value' => function($data) { 
                    return Html::a(Html::img($data->image, ['width'=>'45', 'class'=>'img-circle']), Url::to(['view', 'id' => $data->id]));
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
                //'header' => 'Friends',
            
            ],

            [
                'attribute'=>'count_swipes',
                'contentOptions' => ['style' => 'width:10px;'],
                'header' => 'Swipes',
            
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
            


           /* [
                'attribute' => 'Chat',
                'value' => function ($data) {
                    return Html::a('<i class="material-icons">chat</i>', Url::to(['chat', 'id' => $data->id]));
                },
                'format' => 'raw',
                'contentOptions' => ['style' => 'width:10px;'],
            ],*/




            //'auth_key',
            //'access_token',
            //'password_hash',
            //'oauth_client',
            //'oauth_client_user_id',
            //'email:email',
            /*[
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
            ],
            //'address',
            'created_at:date',

            [
            'attribute' => 'Chat',
            'value' => function ($data) {
                return Html::a(Html::encode('Chat'), Url::to(['chat', 'id' => $data->id]));
            },
            'format' => 'raw',
        ],
            
            [
                'attribute' => 'status',
                'filter'=>false,
                'format' => 'html',
                'value' => function($data) { 
                    if($data->status == Client::USER_ACTIVE){ return 'Active';}
                    if($data->status == Client::USER_NOT_ACTIVE){ return 'Not active';}
                    if($data->status == Client::USER_BANNED){ return 'Banned';}
                },
            ],
            'birthday:date',
            'first_name',
            'last_name',
            'phone',
            [
                'attribute' => 'gender',
                'format' => 'html',
                'value' => function($data) { 
                    if($data->gender == 1){ return 'Male';}
                    if($data->gender == 2){ return 'Female';}
                },
            ],

            [
                'attribute' => 'image',
                'format' => 'html',
                'value' => function($data) { return Html::img($data->image, ['width'=>'100']); },
            ],
            [
                'attribute' => 'badges',
                'format' => 'html',
                'value' => function($data) { 
                    $badges = '';
                    foreach ($data->badge as $key => $value) {
                        $badges = $value['badge_id'].', '.$badges;
                    } 
                    return $badges;
                },
            ],
            [
                'attribute' => 'friend_count',
                'format' => 'html',
                'value' => function($data) { 
                    $friend_count = 0;
                    $sent_requst = 0;
                    $received_request = 0;
                    $connection = Yii::$app->getDb();
                    $command = $connection->createCommand("SELECT COUNT(*) as count FROM `friend` l1 
                                INNER JOIN `friend` l2 ON l1.user_source_id = l2.user_target_id AND l2.user_source_id = l1.user_target_id 
                                LEFT JOIN `client` ON `client`.`id` = l2.user_source_id
                                WHERE l1.user_source_id = ".$data->id);
                    $result = $command->queryAll();
                    $friend_count = $result[0]['count'];
                    return '<strong>Friends count:</strong> '.$friend_count;

                    //return '<strong>Friends count:</strong> '.$friend_count.'<br><strong>Sent requst:</strong> '.$sent_requst.'<br><strong>Received Request:</strong> '.$received_request;
                },
            ],*/
            //'forgot_sms_code',
            //'forgot_sms_code_exp',
            //'login_sms_code',
            //'login_sms_code_exp',
            //'reset_pass_code',
            //'verify_sms_code',
            //'birthday',

           

                [
                    'class' => \common\widgets\ActionColumn::class,
                    'template' => '{update}{delete}',
                    

                ],
        ],
    ]); ?>


</div>
