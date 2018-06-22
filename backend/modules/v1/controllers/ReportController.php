<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-12 14:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\controllers\AdminController;
use backend\modules\v1\models\ApiReport;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;

class ReportController extends  AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiReport';

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'sales' => ['post','options'],
                    ],
                ],
       ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * @brief sales profit report
     * @return array
     */

    public function actionSales ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'plat' => $cond['plat'],
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => $cond['account']?implode(',',$cond['account']):'',
            'seller' => $cond['member'],
            'storeName' => $cond['store']?implode(',',$cond['store']):'',
        ];
        $ret = ApiReport::getSalesReport($condition);
        return $ret;
    }
}