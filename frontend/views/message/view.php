<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use api\models\Message;

$this->title = 'Chat with user #' . $client1['id'] . ' and user #' . $client2['id'];

?>
<div class="message-update">
<p>
  <a href="/message/users" class="btn btn-info">Back</a>
</p>


    <div class="message-form">
<?php
$character_count = 0;
$character_avarage = 0;
$total_messages = 0;
$id = $_GET['id'];

$message1 = Message::find()->where(['type'=>'message', 'chat_id'=>$id])->all();
                            foreach ($message1 as $key => $value) {
                                if (strlen($value['text']) > 0){
                                    $character_count = $character_count + strlen($value['text']);
                                    $total_messages++;
                                }
                            }
                            if($character_count > 0){
                                $character_avarage = round($character_count/$total_messages, 2);
                            }

echo '<b>Сharacter count</b> = <span class="badge badge-secondary">'.$character_count.'</span>
<br><b>Character avarage</b> = <span class="badge badge-warning">'.$character_avarage.'</span>
<br><b>Total messages</b> = <span class="badge badge-info">'.$total_messages.'</span>';
?>


<div class="card direct-chat direct-chat-primary">
              <div class="card-header ui-sortable-handle" style="cursor: move;">
                <h3 class="card-title">Chat</h3>

                <div class="card-tools">
                  <!--<span data-toggle="tooltip" title="3 New Messages" class="badge badge-primary"><?php 
                  echo $total;?></span>-->
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                
                 
                </div>
              </div>
<div class="direct-chat-messages" style="height:auto">
<?php
if ($client1['id'] == 0) {
    $image1 = 'https://dashboard.pluzo.com/0WLfykrhlA5Xsp1vcNZdx8pt_1xGC555.png';
} else {
    if($client1['image']){
      $image1 = $client1['image'];
    } else {
      $image1 = 'https://ptetutorials.com/images/user-profile.png';
    }
}

if ($client2['id'] == 0) {
    $image2 = 'https://dashboard.pluzo.com/0WLfykrhlA5Xsp1vcNZdx8pt_1xGC555.png';
} else {
    if($client2['image']){
      $image2 = $client1['image'];
    } else {
      $image2 = 'https://ptetutorials.com/images/user-profile.png';
    }
}


foreach ($message as $key => $value) {
  if (strlen($value['text']) > 0 AND $value['type'] == 'message') {
    if($value->user_id == $client1['id']){
      
      echo '<div class="direct-chat-msg">
                      <div class="direct-chat-infos clearfix">
                        <span class="direct-chat-name float-right">'.$client1['username'].'</span>
                        <span class="direct-chat-timestamp float-left">'.date("Y-m-d H:i", $value['created_at']).'</span>
                      </div>
                      <a href="/users/index?UserSearch[id]='.$value->user_id.'"><img class="direct-chat-img" src="'.$image1.'"></a>
                      <div class="direct-chat-text">
                        '.$value['text'].'
                      </div>
                    </div>';
    } else {
      echo '<div class="direct-chat-msg right">
                      <div class="direct-chat-infos clearfix">
                        <span class="direct-chat-name float-left">'.$client2['username'].'</span>
                        <span class="direct-chat-timestamp float-right">'.date("Y-m-d H:i", $value['created_at']).'</span>
                      </div>
                      <a href="/users/index?UserSearch[id]='.$value->user_id.'"><img class="direct-chat-img" src="'.$image2.'"></a>
                      <div class="direct-chat-text">
                        '.$value['text'].'
                      </div>
                    </div>';



    }
  }
}
?>
</div>


              <!-- /.card-footer-->
            </div>
 


  


</div>

</div>


