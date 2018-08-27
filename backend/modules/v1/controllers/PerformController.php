<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-08-24
 * Time: 11:52
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiPerform;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;

class PerformController extends  AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiPerform';

    public function behaviors(){
        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'perform' => ['post','options'],
                    'develop' => ['post','options'],
                    'purchase' => ['post','options'],
                    'Possess' => ['post','options'],
                    'ebay-sales' => ['post','options'],
                    'sales-trend' => ['post','options']
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;
    }

    /**
     *
     * @return mixed
     */
    public function actionPerform(){
        $request = Yii::$app->request->post();
        ///print_r($request);exit;
        $cond= $request['condition'];
        $condition= [
            'beginDate' => $cond['beginDate'],
            'endDate' => $cond['endDate'],
            'CreateBeginDate' => $cond['CreateBeginDate'],
            'CreateEndDate' => $cond['CreateEndDate'],
        ];
        $ret = ApiPerform::getNewProductDevelopmentPerformance($condition);
        return $ret;
    }




}