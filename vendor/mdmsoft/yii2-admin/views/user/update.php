<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;
use mdm\admin\components\Helper;
use mdm\admin\models\Department;
use mdm\admin\models\Position;
use mdm\admin\models\Store;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model mdm\admin\models\User */

$this->title = $model->username;
$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$controllerId = $this->context->uniqueId . '/';
$depart = 0;
$firstDepart = 1;
?>
<div class="site-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= Html::errorSummary($model)?>
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-update-user']); ?>
            <?= $form->field($model, 'department')->dropDownList(
                    ArrayHelper::map(Department::find()->all(),'id','department'),
                    ['prompt'=>'--请选择部门--',]
            ) ?>
            <?= $form->field($model, 'position')->widget(Select2::classname(), [
                'data' =>ArrayHelper::map(Position::find()->all(),'id','position'),
                'options' => ['placeholder' => '--请选择该员工的职位--',
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <?= $form->field($model, 'store')->widget(Select2::classname(), [
                'data' =>ArrayHelper::map(Store::find()->all(),'id','store') ,
                'options' => ['placeholder' => '--如果是销售请选择店铺--',
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <div class="form-group">
                <?= Html::submitButton(Yii::t('rbac-admin', '保存'), ['class' => 'btn btn-primary', 'name' => 'save-button']) ?>
            </div>
            <?php ActiveForm::end(); ?>
        </div>
    </div>
</div>
