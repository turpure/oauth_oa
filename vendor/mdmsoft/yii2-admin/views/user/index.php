<?php

use yii\helpers\Html;
use yii\grid\GridView;
use mdm\admin\components\Helper;

/* @var $this yii\web\View */
/* @var $searchModel mdm\admin\models\searchs\User */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('rbac-admin', 'Users');
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-index">

    <p>
        <?= Html::a(Yii::t('rbac-admin', '新增用户'), ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?=
    GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],
            'username',
            'email:email',
            [
                'attribute' => 'depart',
                'value' => function ($model) {
                    $sql = "SELECT d.department FROM auth_department_child dc LEFT JOIN auth_department d ON d.id=dc.department_id WHERE dc.user_id=$model->id";
                    $departList = Yii::$app->db->createCommand($sql)->queryAll();
                    if($departList){
                        $list = \yii\helpers\ArrayHelper::getColumn($departList,'department');
                        return implode(',',$list);
                    }else{
                        return '';
                    }
                },
            ],
            [
                'attribute' => 'position',
                'value' => function ($model) {
                    $sql = "SELECT d.position FROM auth_position_child dc LEFT JOIN auth_position d ON d.id=dc.position_id WHERE dc.user_id=$model->id";
                    $posiList = Yii::$app->db->createCommand($sql)->queryAll();
                    if($posiList){
                        $list = \yii\helpers\ArrayHelper::getColumn($posiList,'position');
                        return implode(',',$list);
                    }else{
                        return '';
                    }
                },
            ],
            'created_at:date',
            [
                'attribute' => 'status',
                'value' => function ($model) {
                    return $model->status == 0 ? '停用' : '启用';
                },
                'filter' => [
                    0 => '停用',
                    10 => '启用'
                ]
            ],
            [
                'class' => 'yii\grid\ActionColumn',
                'template' => Helper::filterActionColumn(['view', 'update', 'activate', 'inactivate', 'delete']),
                'buttons' => [
                    'activate' => function ($url, $model) {
                        if ($model->status == 10 || $model->id == 1) {
                            return '';
                        }
                        $options = [
                            'title' => Yii::t('rbac-admin', 'Activate'),
                            'aria-label' => Yii::t('rbac-admin', 'Activate'),
                            'data-confirm' => Yii::t('rbac-admin', 'Are you sure you want to activate this user?'),
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ];
                        return Html::a('<span class="glyphicon glyphicon-ok"></span>', $url, $options);
                    },
                    'inactivate' => function ($url, $model) {
                        if ($model->status == 10 && $model->id != 1) {
                            $options = [
                                'title' => Yii::t('rbac-admin', 'Inactivate'),
                                'aria-label' => Yii::t('rbac-admin', 'Inactivate'),
                                'data-confirm' => Yii::t('rbac-admin', 'Are you sure you want to inactivate this user?'),
                                'data-method' => 'post',
                                'data-pjax' => '0',
                            ];
                            return Html::a('<span class="glyphicon glyphicon-remove"></span>', $url, $options);
                        }
                        return '';
                    },
                    'delete' => function ($url, $model) {
                        if ($model->id == 1) {
                            return '';
                        }
                        $options = [
                            'title' => Yii::t('rbac-admin', '删除'),
                            'aria-label' => Yii::t('rbac-admin', '删除'),
                            'data-confirm' => Yii::t('rbac-admin', '您确定要删除此项吗？'),
                            'data-method' => 'post',
                            'data-pjax' => '0',
                        ];
                        return Html::a('<span class="glyphicon glyphicon-trash"></span>', $url, $options);
                    },

                ],
            ],
        ]
    ]);
    ?>
</div>
