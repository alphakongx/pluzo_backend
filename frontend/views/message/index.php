<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use api\models\User;
use api\models\Party;
use api\models\Message;
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\MessageSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Messages';
?>
<script src='https://kit.fontawesome.com/a076d05399.js' crossorigin='anonymous'></script>
<script type="text/javascript">
    function select_user(id){
        if(id == 0){
            window.location.href = "/message/index";
        } else {
            window.location.href = "/message/index?MessageSearch[find_user]="+id;
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
        if (isset($_GET['MessageSearch']['find_user'])) {
            if($value['id'] == $_GET['MessageSearch']['find_user']){
                echo 'selected ';
            }
        }
        echo '>'.$value['username'].'</option>';
    }
    ?>
    </select>
</p>


<div class="message-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn',
            'contentOptions' => ['style' => 'width:10px;']
            ],

            [
                'attribute'=>'chat_id',
                'value' => function ($data) {
                    $chat = Party::find()->where(['chat_id'=>$data->chat_id])->all();
                    if (isset($chat[0]->user_id)) {
                        $user1 = $chat[0]->user_id;
                        if($user1 == 0){
                            $user1 = '<img class="img-circle" width="30" height="30" src="https://dashboard.pluzo.com/0WLfykrhlA5Xsp1vcNZdx8pt_1xGC555.png" alt="Pluzo team">&nbsp;Pluzo team';
                        } else {
                            //character_count, character_avarage, total_messages
                            $character_count = 0;
                            $character_avarage = 0;
                            $total_messages = 0;
                            $message1 = Message::find()->where(['type'=>'message', 'user_id'=>$chat[0]->user_id, 'chat_id'=>$data->chat_id])->all();
                            foreach ($message1 as $key => $value) {
                                if (strlen($value['text']) > 0){
                                    $character_count = $character_count + strlen($value['text']);
                                    $total_messages++;
                                }
                            }
                            if($character_count > 0){
                                $character_avarage = round($character_count/$total_messages, 2);
                            }
                            
                            $user = User::find()->where(['id'=>$chat[0]->user_id])->one();
                            $user1 = '<img class="img-circle" width="30" height="30" src="'.$user->image.'" alt="'.$user->username.'">&nbsp;'.$user->username.'&nbsp; C_C = <span class="badge badge-secondary">'.$character_count.'</span>&nbsp; C_A = <span class="badge badge-warning">'.$character_avarage.'</span>&nbsp; T_M = <span class="badge badge-info">'.$total_messages.'</span>';
                        }
                    } else {
                        $user1 = '';
                    }
                    
                    if (isset($chat[1]->user_id)) {
                        $user2 = $chat[1]->user_id;
                        if($user2 == 0){
                            $user2 = '<img class="img-circle" width="30" height="30" src="https://dashboard.pluzo.com/0WLfykrhlA5Xsp1vcNZdx8pt_1xGC555.png" alt="Pluzo team">&nbsp;Pluzo team';
                        } else {
                            //character_count, character_avarage, total_messages
                            $character_count = 0;
                            $character_avarage = 0;
                            $total_messages = 0;
                            $message1 = Message::find()->where(['type'=>'message', 'user_id'=>$chat[1]->user_id, 'chat_id'=>$data->chat_id])->all();
                            foreach ($message1 as $key => $value) {
                                if (strlen($value['text']) > 0){
                                    $character_count = $character_count + strlen($value['text']);
                                    $total_messages++;
                                }
                            }
                            if($character_count > 0){
                                $character_avarage = round($character_count/$total_messages, 2);
                            }

                            $user = User::find()->where(['id'=>$chat[1]->user_id])->one();
                            if (isset($user->image)) {
                                $user2 = '<img class="img-circle" width="30" height="30" src="'.$user->image.'" alt="'.$user->username.'">&nbsp;'.$user->username.'&nbsp; C_C = <span class="badge badge-secondary">'.$character_count.'</span>&nbsp; C_A = <span class="badge badge-warning">'.$character_avarage.'</span>&nbsp; T_M = <span class="badge badge-info">'.$total_messages.'</span>';
                            } else {
                                $user2 = 'Deleted_user';
                            }
                            
                        }
                    } else {
                        $user2 = '';
                    }
                    
                    $url = 'Chat #'.$data->chat_id.' ';
                    return Html::a($url, Url::to(['view', 'id' => $data->chat_id])).'('.$user1.'&nbsp;&nbsp;&nbsp;<i class="fas fa-sync"></i>&nbsp;&nbsp;&nbsp;'.$user2.')';
                },
                'format' => 'raw',
            ],
        ],
    ]); ?>


</div>
