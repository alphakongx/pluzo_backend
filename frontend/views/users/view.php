<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use api\models\Images;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = 'user# '.$model->id.' images';
\yii\web\YiiAsset::register($this);
$this->registerAssetBundle(yii\web\JqueryAsset::className(), \yii\web\View::POS_HEAD);
?>

<div class="clearfix" style="margin-bottom: 10px">
                <a href="/users/index" class="btnindex btn-sm btn-info float-left">Back</a>
              </div>
<div class="user-view">

<script type="text/javascript" src="<?php echo Yii::getAlias('@web');?>/fancy/lib/jquery.mousewheel-3.0.6.pack.js"></script>

<!-- Add fancyBox -->
<link rel="stylesheet" href="<?php echo Yii::getAlias('@web');?>/fancy/source/jquery.fancybox.css?v=2.1.7" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo Yii::getAlias('@web');?>/fancy/source/jquery.fancybox.pack.js?v=2.1.7"></script>
<!-- Optionally add helpers - button, thumbnail and/or media -->
<link rel="stylesheet" href="<?php echo Yii::getAlias('@web');?>/fancy/source/helpers/jquery.fancybox-buttons.css?v=1.0.5" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo Yii::getAlias('@web');?>/fancy/source/helpers/jquery.fancybox-buttons.js?v=1.0.5"></script>
<script type="text/javascript" src="<?php echo Yii::getAlias('@web');?>/fancy/source/helpers/jquery.fancybox-media.js?v=1.0.6"></script>

<link rel="stylesheet" href="<?php echo Yii::getAlias('@web');?>/fancy/source/helpers/jquery.fancybox-thumbs.css?v=1.0.7" type="text/css" media="screen" />
<script type="text/javascript" src="<?php echo Yii::getAlias('@web');?>/fancy/source/helpers/jquery.fancybox-thumbs.js?v=1.0.7"></script>
<script type="text/javascript">
    function delete1(id){
      if(confirm("Delete this image?")){
        $.ajax('img-delete', {
            complete: function (data) {
              },
            success: function(r){
                if(r == 1){
                    location.reload()
                }
              },
              data: {id: id},
              type: "post",
            });
        } 
    }
</script>
<div class="row">
<?php
$image = Images::find()->where(['user_id'=>$model->id])->all();
foreach ($image as $key => $value) {
echo '
<div class="sm-12">
<a class="fancybox" rel="group" href="'.$value['path'].'"><img class="img-thumbnail rounded float-left" src="'.$value['path'].'" alt="" width="250" /></a>
<div style="text-align:center"><a href="#" onclick="delete1('.$value['id'].'); return false"><span class="badge badge-danger">Delete</span></a></div>
</div>
';
}
?>
</div>
<script type="text/javascript">
    $(document).ready(function() {
        $(".fancybox").fancybox();
    });
</script>