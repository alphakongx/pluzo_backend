<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Analit */

$this->title = 'Update Analit: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'Analits', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="analit-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
