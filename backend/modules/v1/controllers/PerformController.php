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

class PerformController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiPerform';

    public function behaviors()
    {
        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'perform' => ['post', 'options'],
                    'sales' => ['post', 'options'],
                    'cost' => ['post', 'options'],
                ],
            ],
        ],
            parent::behaviors()
        );
        return $behaviors;
    }

    /**
     * 新品开发表现
     * @return mixed
     */
    public function actionPerform()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        //日期为空的话不能显示表了 可以前端验证 required
        if (empty($cond['beginDate']) || empty($cond['endDate'])) {
            return [
                'code' => 400,
                'message' => 'BeginDate and EndDate can not be empty！'
            ];
        }
        $condition = [
            'beginDate' => $cond['beginDate'],
            'endDate' => $cond['endDate'],
            'createBeginDate' => $cond['createBeginDate'],
            'createEndDate' => $cond['createEndDate'],
        ];
        $ret = ApiPerform::getNewProductDevelopmentPerformance($condition);
        return $ret;
    }


    /**
     * 销售变化表
     * @return array|string
     */
    public  function actionSales()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        $condition = [
            'suffix' => implode(',',$cond['suffix']),
            'plat' => $cond['plat'],
            'saler' => $cond['saler'],
            'start' => $cond['start'],
            'limit' => $cond['limit'],
        ];
        $ret = ApiPerform::getSalesChange($condition);
        $num = $ret ? $ret[0]['totalNum']:0;
        return [
            'items' => $ret,
            'totalCount' => $num,
        ];
    }

    /**
     * 获取物流公司
     * @return array|string
     */
    public function actionLogistics()
    {
        $list = Yii::$app->py_db->createCommand("SELECT * FROM T_Express WHERE used=0 ORDER BY code")->queryAll();
        return ArrayHelper::map($list,'Name', 'Name');
    }

    /**
     * 平台物流费用
     * @return array|string
     */
    public function actionCost()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];

        //日期为空的话不能显示表了 可以前端验证 required
        if (empty($cond['beginDate']) || empty($cond['endDate'])) {
            return [
                'code' => 400,
                'message' => 'BeginDate and EndDate can not be empty！'
            ];
        }
        $condition = [
            'beginDate' => $cond['beginDate'],
            'endDate' => $cond['endDate'],
            'wlCompany' => $cond['wlCompany'],
        ];
        $ret = ApiPerform::getLogisticsCost($condition);
        return $ret;
    }




}