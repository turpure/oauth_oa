<?php

use yii\helpers\Html;
use yii\grid\GridView;
use mdm\admin\components\Helper;
use \yii\bootstrap\Modal;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $searchModel mdm\admin\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="store-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('rbac-admin', '新增店铺'), ['create'], ['class' => 'btn btn-success']) ?>
        <?= Html::a(Yii::t('rbac-admin', '导出'), ['export'], ['class' => 'export btn btn-primary']) ?>

        <?php /*$form = ActiveForm::begin(["options" => ["enctype" => "multipart/form-data"]]); */?><!--
        <?/*= $form->field($model, "file")->fileInput() */?>
        <?/*= Html::a(Yii::t('rbac-admin', '批量导入'), ['import'], ['id' => 'create', 'class' => 'import btn btn-danger']) */?>
        --><?php /*ActiveForm::end(); */?>
    </p>



    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'store',
            'platform',
            [
                'attribute' => 'used',
                'value' => function ($modle) {
                    return $modle->used ? '停用' : '启用';
                }
            ],
            'username',
            'check_username',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => Helper::filterActionColumn(['view', 'update', 'delete']),

            ],
        ],
        'pager' => [
            'class' => \common\widgets\MLinkPager::className(),
            'firstPageLabel' => '首页',
            'prevPageLabel' => '<',
            'nextPageLabel' => '>',
            'lastPageLabel' => '尾页',
            'goPageLabel' => true,
            'goPageSizeArr' => ['10' => 10, '20' => 20, '50' => 50, '100' => 100, '500' => 500, '1000' => 1000, '100000' => '全部'],
            'totalPageLable' => '共x页',
            'goButtonLable' => '确定',
            'maxButtonCount' => 10
        ],
    ]);


    ?>
</div>
