<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model common\models\User */

$this->title = 'user# '.$model->id;
$this->params['breadcrumbs'][] = ['label' => 'Users', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="user-view">





    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'username',
            
            'status',
            
            'first_name',
            'last_name',
            'phone',
            'gender',
            'image',
            
            'birthday',
        ],
    ]) ?>

</div>
