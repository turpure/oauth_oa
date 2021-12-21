<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use \kartik\select2\Select2;
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
            <?= $form->field($model, 'platform')->widget(Select2::classname(), [
                'data' => [
                    'Aliexpress' => 'Aliexpress',
                    'Amazon' => 'Amazon',
                    'eBay' => 'eBay',
                    'Wish' => 'Wish',
                    'Joom' => 'Joom',
                    'VOVA' => 'VOVA',
                    'Shopee' => 'Shopee',
                    'Lazada' => 'Lazada',
                    'Topbuy' => 'Topbuy',
                    'mymall' => 'mymall',
                    'Shopify' => 'Shopify',
                    'Mercado' => 'Mercado',
                    '1688' => '1688',
                    'Fyndiq' => 'Fyndiq',
                    'Joybuy' => 'Joybuy',
                ],
                'options' => ['placeholder' => '--请选择销售平台--'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <?= $form->field($model, 'username')->widget(Select2::classname(), [
                'data' => Position::getPositionUser('销售'),
                'options' => ['placeholder' => '--请选择归属人--',
                    'multiple' => false,
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>

            <?= $form->field($model, 'check_username')->widget(Select2::classname(), [
                'data' => Position::getPositionUser(),
                'options' => ['placeholder' => '--请选择查看人--',
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <?php if (!$model->isNewRecord) echo $form->field($model, 'used', [])->checkbox() ?>

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
