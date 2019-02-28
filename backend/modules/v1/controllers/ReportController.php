<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-12 14:15
 */

namespace backend\modules\v1\controllers;
use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
use backend\modules\v1\utils\Handler;

class ReportController extends  AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiReport';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
                'verbs' => [
                    'class' => VerbFilter::className(),
                    'actions' => [
                        'sales' => ['post','options'],
                        'develop' => ['post','options'],
                        'purchase' => ['post','options'],
                        'Possess' => ['post','options'],
                        'ebay-sales' => ['post','options'],
                        'sales-trend' => ['post','options'],
                        'profit' => ['post','options'],
                    ],
                ],
       ],
            parent::behaviors()
        );
        return $behaviors;

    }

    /**
     * @brief sales profit report
     * @throws \Exception
     * @return array
     */

    public function actionSales ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $params = [
            'platform' => isset($cond['plat'])?$cond['plat']:[],
            'username' => isset($cond['member'])?$cond['member']:[],
            'store' => isset($cond['account'])?$cond['account']:[]
        ];
        $exchangeRate = ApiSettings::getExchangeRate();
        $paramsFilter = Handler::paramsHandler($params);
        $condition= [
            'dateType' =>$cond['dateType']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'queryType' => $paramsFilter['queryType'],
            'store' => implode(',',$paramsFilter['store']),
            'warehouse' => $cond['store']?implode(',',$cond['store']):'',
            'exchangeRate' => $exchangeRate['salerRate']
        ];
        return ApiReport::getSalesReport($condition);
    }

    /**
     * @brief develop profit report
     * @return array
     */


    public function actionDevelop ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'seller' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getDevelopReport($condition);
        return $ret;
    }

    /**
     * @brief Purchase profit report
     * @return array
     */
    public function actionPurchase ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'purchase' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getPurchaseReport($condition);
        return $ret;
    }


    /**
     * @brief Possess profit report
     * @return array
     */
    public function actionPossess ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'possess' => $cond['member']?implode(',',$cond['member']):'',
        ];
        $ret = ApiReport::getPossessReport($condition);
        return $ret;
    }

    /**
     * @brief EbaySales profit report
     * @return array
     */
    public function actionEbaySales ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'possess' => $cond['member'],
        ];
        $ret = ApiReport::getEbaySalesReport($condition);
        return $ret;
    }



    /**
     * @brief SalesTrend profit report
     * @return array
     * @throws  \Exception
     */
    public function actionSalesTrend ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition= [
            'store' => $params['store']?implode(',',$params['store']):'',
            'queryType' => $params['queryType'],
            'dateFlag' =>$cond['dateType'],
            'showType' => $cond['flag']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];

        $ret = ApiReport::getSalesTrendReport($condition);
        return $ret;
    }

    /**
     * @brief 订单销量报表
     * @return array
     * @throws \Exception
     */
    public function actionOrderCount ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition= [
            'store' => $params['store']?implode(',',$params['store']):'',
            'queryType' => $params['queryType'],
            'dateFlag' =>$cond['dateType'],
            'showType' => $cond['flag']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];

        $ret = ApiReport::getOrderCountReport($condition);
        return $ret;
    }

    /**
     * @brief Sku销量报表
     * @return array
     * @throws \Exception
     */
    public function actionSkuCount ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition= [
            'store' => $params['store']?implode(',',$params['store']):'',
            'queryType' => $params['queryType'],
            'dateFlag' =>$cond['dateType'],
            'showType' => $cond['flag']?:0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];

        $ret = ApiReport::getSkuCountReport($condition);
        return $ret;
    }
    /**
     * @brief profit report
     * @return array
     */
    public function actionAccount ()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $condition= [
            'dateFlag' =>$cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'sku' => $cond['sku'],
            'salesman' => $cond['member']?"'".implode(',',$cond['member'])."'":'',
            'chanel' => $cond['plat'],
            'suffix' => $cond['account']?("'".implode(',',$cond['account'])."'"):'',
            'storeName' => $cond['store']?("'".implode(',',$cond['store'])."'"):'',
            'start' => $cond['start'],
            'limit' => $cond['limit'],
        ];
        $ret = ApiReport::getProfitReport($condition);
        $num = $ret ? $ret[0]['totalNum']:0;
        return [
            'items' => $ret,
            'totalCount' => $num,
        ];
    }

    /**
     * @brief introduce performance report
     */
    public function actionIntroduce()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'member' => $cond['member']?implode(',',$cond['member']):''
        ];
        //print_r($condition);exit;
        return ApiReport::getIntroduceReport($condition);
    }

    /**
     * suffix refund details
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionRefund()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $params = [
            'platform' => isset($cond['plat'])?$cond['plat']:[],
            'username' => isset($cond['member'])?$cond['member']:[],
            'store' => isset($cond['account'])?$cond['account']:[]
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition= [
            'type' =>$cond['type'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'".implode("','",$paramsFilter['store'])."'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getRefundDetails($condition);
    }


    /**
     * 死库明细
     * Date: 2019-01-04 10:21
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionDeadFee()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $params = [
            'platform' => isset($cond['plat'])?$cond['plat']:[],
            'username' => isset($cond['member'])?$cond['member']:[],
            'store' => isset($cond['account'])?$cond['account']:[]
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $storeName = $cond['storename']?"'".implode(',',$cond['storename'])."'":'';
        $storeName = str_replace(",","','",$storeName);
        $condition= [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'".implode("','",$paramsFilter['store'])."'",
            'storename' => $storeName,
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getDeadFee($condition);
    }

    /**
     * 杂费明细
     * Date: 2019-01-04 10:22
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionExtraFee()
    {
        $request = Yii::$app->request->post();
        $cond= $request['condition'];
        $params = [
            'platform' => isset($cond['plat'])?$cond['plat']:[],
            'username' => isset($cond['member'])?$cond['member']:[],
            'store' => isset($cond['account'])?$cond['account']:[]
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition= [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'".implode("','",$paramsFilter['store'])."'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getExtraFee($condition);

    }






}