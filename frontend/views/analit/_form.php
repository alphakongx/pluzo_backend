<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\Analit */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="analit-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'user_id')->textInput() ?>

    <?= $form->field($model, 'page')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'time')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'time_start')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'time_end')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'during')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'leave')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
