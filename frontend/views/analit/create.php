<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model common\models\Analit */

$this->title = 'Create Analit';
$this->params['breadcrumbs'][] = ['label' => 'Analits', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="analit-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
