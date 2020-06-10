<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\ActiveForm;
use yii\helpers\Json;
use mdm\admin\AutocompleteAsset;
use kartik\select2\Select2;
use mdm\admin\models\Department;
/* @var $this yii\web\View */
/* @var $model mdm\admin\models\Menu */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="department-form">
    <?php $form = ActiveForm::begin(); ?>
    <div class="row">
        <div class="col-sm-6">
            <?= $form->field($model, 'parent')->widget(Select2::classname(), [
                'data' =>ArrayHelper::map(Department::findAll(['parent' => 0]),'id','department'),
                'options' => ['placeholder' => '--请选择该职位的菜单--'],
                'pluginOptions' => ['allowClear' => true],
            ]) ?>

            <?= $form->field($model, 'department')->textInput(['maxlength' => 128]) ?>

            <?= $form->field($model, 'type')->widget(Select2::classname(), [
                'data' => ['业务' => '业务','销售' => '销售','开发' => '开发','采购' => '采购','供应链' => '供应链','仓储' => '仓储'],
                'options' => ['multiple' => true, 'placeholder' => '--请选择业务类型--'],
                'pluginOptions' => ['allowClear' => true],
            ]); ?>

            <?= $form->field($model, 'description')->textInput(['id' => 'description']) ?>

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
