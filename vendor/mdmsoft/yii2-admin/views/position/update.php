<?php

use yii\helpers\Html;
use yii\helpers\ArrayHelper;
use yii\widgets\DetailView;
use mdm\admin\components\Helper;
use mdm\admin\models\Menu;
use yii\bootstrap\ActiveForm;
use kartik\select2\Select2;
/* @var $this yii\web\View */
/* @var $model mdm\admin\models\User */

$this->title = $model->position;
//$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Users'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;

$controllerId = $this->context->uniqueId . '/';
?>
<div class="site-update">
    <h1><?= Html::encode($this->title) ?></h1>

    <?= Html::errorSummary($model)?>
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-update-position']); ?>
            <?= $form->field($model, 'menu')->widget(Select2::classname(), [
                'data' =>ArrayHelper::map(Menu::find()->all(),'id','name'),
                'options' => ['placeholder' => '--请选择该职位的菜单--',
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
