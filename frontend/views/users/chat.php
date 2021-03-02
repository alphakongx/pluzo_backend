<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

$this->title = 'Chat with user #' . $id;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = 'Chat';
?>
<div class="clearfix" style="margin-bottom: 10px">
                <a href="/users/index" class="btnindex btn-sm btn-info float-left">Back</a>
              </div>
<div class="message-update">


    <div class="message-form">
<div class="card direct-chat direct-chat-primary">
              <div class="card-header ui-sortable-handle" style="cursor: move;">
                <h3 class="card-title">Chat</h3>

                <div class="card-tools">
                  <span data-toggle="tooltip" title="3 New Messages" class="badge badge-primary"><?php echo $total;?></span>
                  <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                  </button>
                
                 
                </div>
              </div>
<div class="direct-chat-messages" style="height:auto">
<?php

foreach ($message as $key => $value) {
  if($value->user_id != 0){
    if($client['image']){
      $image = $client['image'];
    } else {
      $image = 'https://ptetutorials.com/images/user-profile.png';
    }
echo '<div class="direct-chat-msg">
                    <div class="direct-chat-infos clearfix">
                      <span class="direct-chat-name float-right">user# '.$value['user_id'].'</span>
                      <span class="direct-chat-timestamp float-left">'.date("Y-m-d H:i", $value['created_at']).'</span>
                    </div>
                    <img class="direct-chat-img" src="'.$image.'" alt="Message User Image">
                    <div class="direct-chat-text">
                      '.$value['text'].'
                    </div>
                  </div>';
  } else {
echo '<div class="direct-chat-msg right">
                    <div class="direct-chat-infos clearfix">
                      <span class="direct-chat-name float-left">Pluzo Team</span>
                      <span class="direct-chat-timestamp float-right">'.date("Y-m-d H:i", $value['created_at']).'</span>
                    </div>
                    <img class="direct-chat-img" src="https://dashboard.pluzo.com/0WLfykrhlA5Xsp1vcNZdx8pt_1xGC555.png" alt="Message User Image">
                    <div class="direct-chat-text">
                      '.$value['text'].'
                    </div>
                  </div>';


  }
}
?>
</div>

<div class="card-footer">
                <?php $form = ActiveForm::begin(); ?>
                  <div class="input-group">
<?= Html::activeTextInput($model, 'text', ['placeholder' => 'Type Message ...', 'class' => 'form-control']); ?>
   
                    <span class="input-group-append">

                      <?= Html::submitButton('Send', ['class' => 'btn btn-primary']) ?>
                    </span>
                  </div>
                <?php ActiveForm::end(); ?>
              </div>
              <!-- /.card-footer-->
            </div>
 


  


</div>

</div>






