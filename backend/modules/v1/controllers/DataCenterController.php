<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiDataCenter;
use yii\data\ActiveDataProvider;
use yii\db\Query;
use Yii;

class DataCenterController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiDataCenter';

    public function behaviors()
    {
        return parent::behaviors();
    }


    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief  show sku out of stock
     * @return string
     */
    public function actionOutOfStockInfo()
    {
        $get = Yii::$app->request->get();
        $pageSize = isset($get['pageSize']) ? $get['pageSize']:10;
        $query = (new Query())->from('oauth_outOfStockSkuInfo');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'db' => \Yii::$app->py_db,
            'pagination' => [
                'pageSize' => $pageSize
            ]
        ]);
       return $provider;
    }


    /**
     * @brief show express info
     * @return array
     */
    public function actionExpress()
    {
        return ApiDataCenter::express();
    }

    /**
     * 获取销售变化表（连个时间段对比）
     * Date: 2018-12-29 15:47
     * Author: henry
     * @return \yii\data\ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionSalesChange()
    {
        $cond = Yii::$app->request->post('condition');
        $suffix = $cond['suffix']?"'".implode("','",$cond['suffix'])."'":'';
        $salesman = $cond['salesman']?"'".implode("','",$cond['salesman'])."'":'';
        $condition = [
            'lastBeginDate' => $cond['lastDateRange'][0],
            'lastEndDate' => $cond['lastDateRange'][1],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => str_replace(",","','",$suffix),
            'salesman' => str_replace(",","','",$salesman),
            'goodsName' => $cond['goodsName'],
            'goodsCode' => $cond['goodsCode'],
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiDataCenter::getSalesChangeData($condition);
    }
}