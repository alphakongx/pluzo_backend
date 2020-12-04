<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model frontend\models\Advance */

$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Advances', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
\yii\web\YiiAsset::register($this);
?>
<div class="advance-view">



    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'user_id',
            'created_at',
            'used_time',
            'expires_at',
            'payment_id',
            'status',
            'type',
        ],
    ]) ?>

</div>
