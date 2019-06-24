<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiDataCenter;
use backend\modules\v1\utils\Handler;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

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
        $pageSize = isset($get['pageSize']) ? $get['pageSize'] : 10;
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
        $suffix = $cond['suffix'] ? "'" . implode("','", $cond['suffix']) . "'" : '';
        $salesman = $cond['salesman'] ? "'" . implode("','", $cond['salesman']) . "'" : '';
        $condition = [
            'lastBeginDate' => $cond['lastDateRange'][0],
            'lastEndDate' => $cond['lastDateRange'][1],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => $suffix,
            'salesman' => $salesman,
            'goodsName' => $cond['goodsName'],
            'goodsCode' => $cond['goodsCode'],
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiDataCenter::getSalesChangeData($condition);
    }


    /**
     * Date: 2019-02-21 14:37
     * Author: henry
     * @return array
     * @throws \Exception
     */
    public function actionPriceTrend()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'queryType' => $params['queryType'],
            'dateFlag' => $cond['dateType'],
            'showType' => $cond['flag'] ?: 0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];
        $data = ApiDataCenter::getPriceChangeData($condition);

        return ApiDataCenter::outputData($data);
    }


    /**
     * 获取订单重量与对应的sku重量总和的差异表
     * Date: 2019-02-21 14:52
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \Exception
     */
    public function actionWeightDiff()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        if(!$params['store']) return [];
        //print_r($params);exit;
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'trendId' => $cond['trendId'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1]
        ];
        $data = ApiDataCenter::getWeightDiffData($condition);
        if (!$data) return $data;
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'defaultOrder' => ['weightDiff' => SORT_DESC],
                'attributes' => ['trendId', 'department', 'secDepartment', 'suffix', 'platform', 'username', 'profit',
                    'orderWeight', 'skuWeight', 'weightDiff', 'orderCloseDate'],
            ],
            'pagination' => [
                'pageSize' => isset($cond['pageSize']) && $cond['pageSize'] ? $cond['pageSize'] : 20,
            ],
        ]);
        return $provider;
    }


    /**
     * Date: 2019-02-22 11:31
     * Author: henry
     * @return bool
     * @throws \Exception
     * @throws \Throwable
     * @throws \yii\db\Exception
     */
    public function actionUpdateWeight()
    {
        $request = Yii::$app->request->post()['condition'];
        return ApiDataCenter::updateWeight($request);
    }


    /**
     * Date: 2019-03-04 13:12
     * Author: henry
     * @return array
     * @throws \Exception
     */
    public function actionDelayDelivery()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'queryType' => $params['queryType'],
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'] ? $cond['dateRange'][0] : '',
            'endDate' => $cond['dateRange'] ? $cond['dateRange'][1] : '',
        ];
        //print_r($condition);exit;
        return [
            'delayDeliveryData' => [
                'pieData' => ApiDataCenter::getDelayDeliveryData($condition),
                'barData' => ApiDataCenter::getDelayDeliveryData($condition, 2),
            ],
            'delayShipData' => ApiDataCenter::getDelayShipData($condition),
        ];
    }


    /**
     * Date: 2019-03-04 13:12
     * Author: henry
     * @return array
     * @throws \Exception
     */
    public function actionDelayDetail()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'queryType' => $params['queryType'],
            'beginDate' => $cond['dateRange'] ? $cond['dateRange'][0] : '',
            'endDate' => $cond['dateRange'] ? $cond['dateRange'][1] : '',
        ];
        //print_r($condition);exit;
        return ApiDataCenter::getDelayDeliveryData($condition, 1);
    }


    /** 库存情况
     * Date: 2019-06-14 14:20
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionStockStatus()
    {
        $sql = "SELECT aa.storeName,aa.useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND IFNULL(totalCostmoney,0)<>0 THEN 10000 
                              ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT storeName,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData`
                        GROUP BY storeName
                ) aa LEFT JOIN 
                (
                        SELECT storeName,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData`
                        GROUP BY storeName
                ) bb ON aa.storeName=bb.storeName
                ORDER BY IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) DESC;";
        try{
            return Yii::$app->db->createCommand($sql)->queryAll();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** 库存情况状态明细
     * Date: 2019-06-14 15:11
     * Author: henry
     * @return array
     */
    public function actionStockStatusDetail(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');

        $sql = "SELECT aa.storeName,aa.goodsStatus,useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND totalCostmoney>0 THEN 10000 ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT storeName,IFNULL(goodsStatus,'无状态') goodsStatus,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData`
                        WHERE 1=1 ";
        if(isset($cond['storeName']) && $cond['storeName']) $sql .= " AND storeName LIKE '%{$cond['storeName']}%'";
        $sql .=                " GROUP BY storeName,IFNULL(goodsStatus,'无状态')
                ) aa LEFT JOIN 
                (
                        SELECT storeName,IFNULL(goodsStatus,'无状态') goodsStatus,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData`
                        WHERE 1=1 ";
        if(isset($cond['storeName']) && $cond['storeName']) $sql .= " AND storeName LIKE '%{$cond['storeName']}%'";
        $sql .=                " GROUP BY storeName,IFNULL(goodsStatus,'无状态')
                ) bb ON aa.storeName=bb.storeName AND IFNULL(aa.goodsStatus,'无状态')=IFNULL(bb.goodsStatus,'无状态')
                ORDER BY IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) DESC;";
        try{
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            return $data;
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** 库存情况开发明细
     * Date: 2019-06-14 15:11
     * Author: henry
     * @return array
     */
    public function actionStockDeveloperDetail(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $sql = "SELECT aa.storeName,aa.salerName,useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND totalCostmoney>0 THEN 10000 ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT storeName,
                        CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END AS salerName,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData`
                        WHERE 1=1 ";
        if(isset($cond['storeName']) && $cond['storeName']) $sql .= " AND storeName LIKE '%{$cond['storeName']}%'";
        $sql .=                " GROUP BY storeName,CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END
                ) aa LEFT JOIN 
                (
                        SELECT storeName,
                        CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END AS salerName,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData`
                        WHERE 1=1 ";
        if(isset($cond['storeName']) && $cond['storeName']) $sql .= " AND storeName LIKE '%{$cond['storeName']}%'";
        $sql .=                " GROUP BY storeName,CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END
                ) bb ON aa.storeName=bb.storeName AND IFNULL(aa.salerName,'无人')=IFNULL(bb.salerName,'无人')
                ORDER BY IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) DESC;";
        try{
            return Yii::$app->db->createCommand($sql)->queryAll();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }


    /**
     * Date: 2019-06-17 11:12
     * Author: henry
     * @return array
     */
    /*
    public function actionDelayShip()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account']
        ];
        $params = Handler::paramsFilter($queryParams);
        //var_dump($params);exit;
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'queryType' => $params['queryType'],
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'] ? $cond['dateRange'][0] : '',
            'endDate' => $cond['dateRange'] ? $cond['dateRange'][1] : '',
        ];
        //print_r($condition);exit;
        return ApiDataCenter::getDelayShipData($condition);
    }
    */




}