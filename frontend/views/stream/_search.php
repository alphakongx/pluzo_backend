<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model frontend\models\search\StreamSearch */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="stream-search">

    <?php $form = ActiveForm::begin([
        'action' => ['index'],
        'method' => 'get',
    ]); ?>

    <?= $form->field($model, 'id') ?>

    <?= $form->field($model, 'user_id') ?>

    <?= $form->field($model, 'channel') ?>

    <?= $form->field($model, 'created_at') ?>

    <?= $form->field($model, 'category') ?>

    <?php // echo $form->field($model, 'name') ?>

    <?php // echo $form->field($model, 'invite_only') ?>

    <?php // echo $form->field($model, 'stop') ?>

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
        <?= Html::resetButton('Reset', ['class' => 'btn btn-outline-secondary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
