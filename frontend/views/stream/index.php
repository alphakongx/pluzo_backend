<?php

use yii\helpers\Html;
use yii\grid\GridView;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\StreamSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Streams';
?>
<div class="stream-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            
            'user_id',
            'channel',
            ['attribute' => 'created_at', 'format' => ['date', 'php:d-m-Y H:i:s']],
            'category',
            'name',
            'invite_only',
            'stop',
        ],
    ]); ?>


</div>
