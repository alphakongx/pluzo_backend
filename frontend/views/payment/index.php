<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\PaymentSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Payments';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="payment-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            [
            'attribute' => 'User',
            'value' => function ($data) {
                return Html::a(Html::encode('User').' '.$data->user_id, Url::to(['users/view', 'id' => $data->user_id]));
            },
            'format' => 'raw',
        ],
     
            ['attribute' => 'time', 'format' => ['date', 'php:d-m-Y H:i:s']],
            'payment_method',
            'transaction_id',
            'amount',

                [
                    'class' => \common\widgets\ActionColumn::class,
                    'template' => '{view}',
                ],
        ],
    ]); ?>
</div>
