<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Json;
use mdm\admin\AutocompleteAsset;
use \mdm\admin\models\Position;
/* @var $this yii\web\View */
/* @var $model mdm\admin\models\Menu */
/* @var $form yii\widgets\ActiveForm */


?>

<div class="department-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'store')->textInput(['maxlength' => 128]) ?>
            <?= $form->field($model, 'platform')->textInput(['id' => 'platform']) ?>
            <?= $form->field($model, 'username')->dropDownList(Position::getPositionUser('销售'),['prompt' => '请选择归属人']) ?>
            <?php if(!$model->isNewRecord) echo $form->field($model, 'used', [])->checkbox() ?>

        </div>
    </div>

    <div class="form-group">
        <?=
        Html::submitButton($model->isNewRecord ? Yii::t('rbac-admin', 'Create') : Yii::t('rbac-admin', 'Update'), ['class' => $model->isNewRecord
                    ? 'btn btn-success' : 'btn btn-primary'])
        ?>
    </div>
    <?php ActiveForm::end(); ?>
</div>
