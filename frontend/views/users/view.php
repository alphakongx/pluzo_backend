<?php

use yii\helpers\Html;
use yii\widgets\DetailView;
use api\models\Images;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = 'user# '.$model->id.' images';
\yii\web\YiiAsset::register($this);
?>
<div class="user-view">





    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',

            [
                'attribute'=>'images',
                'format' => 'html',
                'value'=>function ($data) {
                    $image = Images::find()->where(['user_id'=>$data->id])->all();
                    $res = '';
                    foreach ($image as $key => $value) {
                        $res = $res.'<img class="img-circle" width="300" src="'.$value['path'].'">';
                    }
                    return $res;
                },
                //'format' => ['image',['width'=>'100','height'=>'100']],
            ],
            /*'username',
            
            'status',
            
            'first_name',
            'last_name',
            'phone',
            'gender',
            'image',
            
            'birthday',*/
        ],
    ]) ?>

</div>
