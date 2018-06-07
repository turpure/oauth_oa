<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-05-28 11:33
 */
use yii\helpers\Html;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model mdm\admin\models\Menu */

$this->title = Yii::t('rbac-admin', '新增用户');
$this->params['breadcrumbs'][] = ['label' => Yii::t('rbac-admin', 'Menus'), 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="menu-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="department-form">
        <?php $form = ActiveForm::begin(); ?>
        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'username')->label('登陆名')->textInput(['autofocus' => true]) ?>
                <?= $form->field($model, 'email')->label('邮箱') ?>
                <?= $form->field($model, 'password')->label('密码')->passwordInput() ?>
                <?= $form->field($model, 'password_repeat')->label('确认密码')->passwordInput() ?>

            </div>
        </div>

        <div class="form-group">
            <?=
            Html::submitButton( Yii::t('rbac-admin', 'Create') , ['class' =>  'btn btn-primary'])
            ?>
        </div>
        <?php ActiveForm::end(); ?>
    </div>

</div>
