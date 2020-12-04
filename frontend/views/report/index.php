<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\ReportSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Reports';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="report-index">

    <h1><?= Html::encode($this->title) ?></h1>


    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
            'attribute' => 'User',
            'value' => function ($data) {
                return Html::a(Html::encode('User').' '.$data->user_id, Url::to(['users/view', 'id' => $data->user_id]));
            },
            'format' => 'raw',
        ],
            [
                'attribute' => 'Type',
                'value' => function ($data) {
                    if($data->type == 1){
                        return "Stream report";
                    }
                    if($data->type == 2){
                        return "User report";
                    }
                },
                'format' => 'raw',
            ],
            'msg:ntext',
            [
                'attribute' => 'Reason',
                'value' => function ($data) {
                    if($data->reason == 1){return "Harassment";}
                    if($data->reason == 2){return "Nudlty";}
                    if($data->reason == 3){return "I do not like it";}
                    if($data->reason == 4){return "Stream report";}
                    if($data->reason == 5){return "Propaganda";}
                },
                'format' => 'raw',
            ],

            [
                'attribute' => 'User or Channel ID',
                'value' => function ($data) {
                    return $data->channel;
                },
                'format' => 'raw',
            ],

            ['attribute' => 'time', 'format' => ['date', 'php:d-m-Y H:i:s']],

            [
                    'class' => \common\widgets\ActionColumn::class,
                    'template' => '{view}',
                    

                ],
        ],
    ]); ?>


</div>
