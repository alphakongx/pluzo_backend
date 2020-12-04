<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\AdvanceSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Advances';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="advance-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Create Advance', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            //['class' => 'yii\grid\SerialColumn'],

            //'id',
                       [
            'attribute' => 'User',
            'value' => function ($data) {
                return Html::a(Html::encode('User').' '.$data->user_id, Url::to(['users/view', 'id' => $data->user_id]));
            },
            'format' => 'raw',
        ],
            ['attribute' => 'created_at', 'format' => ['date', 'php:d-m-Y H:i:s']],
            ['attribute' => 'used_time', 'format' => ['date', 'php:d-m-Y H:i:s']],
            ['attribute' => 'expires_at', 'format' => ['date', 'php:d-m-Y H:i:s']],
       
            'payment_id',
            //'status',
            //'type',

[
                    'class' => \common\widgets\ActionColumn::class,
                    'template' => '{view}',
                    

                ],
        ],
    ]); ?>


</div>
