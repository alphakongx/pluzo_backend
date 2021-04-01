<?php

use yii\helpers\Html;
use api\models\UserMsg;
use api\models\Friend;
use api\models\StreamUser;
use api\models\Images;
use api\models\Like;
use api\models\Message;
use api\models\Party;
use api\models\Stream;
use common\models\Analit;
use common\models\Client;
use yii\helpers\Url;
use kartik\grid\GridView;

$this->registerAssetBundle(yii\web\JqueryAsset::className(), \yii\web\View::POS_HEAD);
/* @var $this yii\web\View */
/* @var $searchModel frontend\models\search\UserSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Messages';
\yii\web\YiiAsset::register($this);
?>


<div class="user-index">

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'pjax' => true,
        'columns' => [
            [
                'attribute'=>'id',
                'contentOptions' => ['style' => 'width:80px;'],
            ],
            [
                'attribute'=>'username',
                
                'value' => function ($data) {
                    return '<a href="/message/index?MessageSearch[find_user]='.$data->id.'">'.$data->username.'</a>';
                },
                'format' => 'raw',
            ],
        ],
    ]); ?>
</div>