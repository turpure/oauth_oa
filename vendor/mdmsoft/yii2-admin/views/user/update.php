<?php

use yii\helpers\Html;
use yii\helpers\Url;
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
?>
<div class="site-update">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= Html::errorSummary($model) ?>
    <div class="row">
        <div class="col-lg-5">
            <?php $form = ActiveForm::begin(['id' => 'form-update-user']); ?>
            <?= $form->field($model, 'department')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Department::findAll(['parent' => 0]), 'id', 'department'),
                'pluginEvents' => [
                    'change' => "function(evt){
                                var depart_id = $('#updateuser-department option:selected').val();
                                if(depart_id == 0 || depart_id.length == 0){
                                    $('.form-group.field-updateuser-child_depart').hide();
                                }else{
                                    $('.form-group.field-updateuser-child_depart').show();
                                }
                                $.ajax({
                                    url: '" . Url::toRoute('/admin/user/ajax') . "',
                                    type: 'post',
                                    dataType: 'json',
                                    data: {'id':depart_id},
                                    success: function (data) {
                                        //console.log(data);
                                        var str = '<option value=\'0\'>--请选择二级部门--</option>';
                                        if (data.length == 0) {
                                            $('.form-group.field-updateuser-child_depart').hide();
                                        }else{
                                            $.each(data, function(i, item){
                                                console.log(item);
                                                console.log(item.department);
                                                str += '<option value=\"' + item.id + '\">' + item.department + '</option>'
                                            });
                                        }
                                        
                                        $('#updateuser-child_depart > option').remove();
                                        $('#updateuser-child_depart').append(str);
                                    }
                                });
                            }",
                ],
                'language' => 'zh',
                'options' => ['prompt' => '--请选择部门--'],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>

            <?= $form->field($model, 'child_depart')->dropDownList(
                $model->department?ArrayHelper::map(Department::findAll(['parent' => $model->department]), 'id', 'department'):[],
                ['prompt' => '--请选择二级部门--',]
            )->label(false) ?>
            <?= $form->field($model, 'position')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Position::find()->all(), 'id', 'position'),
                'options' => ['placeholder' => '--请选择该员工的职位--',
                    'multiple' => true,
                ],
                'pluginOptions' => [
                    'allowClear' => true
                ],
            ]) ?>
            <?= $form->field($model, 'store')->widget(Select2::classname(), [
                'data' => ArrayHelper::map(Store::find()->all(), 'id', 'store'),
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
