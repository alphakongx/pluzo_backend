<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model common\models\User */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="user-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->errorSummary($model) ?>
    
    <?= $form->field($model, 'username')->textInput(['maxlength' => true , 'disabled' => true]) ?>

    <?= $form->field($model, 'status')->dropDownList([
    '0' => 'Not active','1' => 'Active','3' => 'Banned',
    ]) ?>

    <?= $form->field($model, 'gender')->dropDownList([
    '1' => 'Male',
    '2' => 'Female',
    ]) ?>

    <?= $form->field($model, 'phone')->textInput() ?>


    <?= $form->field($model, 'first_name')->textInput() ?>

    <?= $form->field($model, 'last_name')->textInput() ?>

    <?= $form->field($model, 'address')->textInput()->label("Country") ?>
    <?= $form->field($model, 'city')->textInput() ?>
    <?= $form->field($model, 'state')->textInput() ?>


    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
