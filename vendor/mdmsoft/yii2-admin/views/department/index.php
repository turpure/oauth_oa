<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\grid\GridView;
use mdm\admin\components\Helper;
use mdm\admin\models\Department;

/* @var $this yii\web\View */
/* @var $searchModel mdm\admin\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->params['breadcrumbs'][] = $this->title;
?>
<div class="department-index">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a(Yii::t('rbac-admin', '新增部门'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            [
                    'attribute' => 'parent',
                'value' => function($model){
                    return $model->parent ? Department::findOne($model->parent)['department'] : '';
                }

            ],
            'department',
            'description',
            'created_at:date',
            'updated_at:date',
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => Helper::filterActionColumn(['view','update', 'delete']),
                'buttons' => [
                    'view' => function ($url, $model, $key) {
                        return Html::a('<span class="glyphicon glyphicon-eye-open"></span>',
                            Url::to($url),
                            ['type' => 'button', 'title' => '分配员工', 'aria-label' => '分配员工']);
                    },

                ],

            ],
        ]
    ]);
    ?>
</div>
