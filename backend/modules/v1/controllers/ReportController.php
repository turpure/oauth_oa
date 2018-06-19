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
                        'sales' => ['post'],
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
//        $condition = Yii::$app->request->post()['condition'];
        $condition= [
            'plat' => '',
            'dateFlag' =>'1',
            'beginDate' => '2018-05-10',
            'endDate' => '2018-05-11',
            'suffix' => '',
            'seller' => '',
            'storeName' => '',
        ];
        $ret = ApiReport::getSalesReport($condition);
        return $ret;
    }
}