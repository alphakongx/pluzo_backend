<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model frontend\models\Advance */

$this->title = 'Create Advance';
$this->params['breadcrumbs'][] = ['label' => 'Advances', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="advance-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
