<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-09-07 10:00
 */

namespace backend\modules\v1\controllers;


use backend\models\ShopElf\YPayPalStatus;
use backend\models\ShopElf\YPayPalStatusLogs;
use backend\modules\v1\models\ApiDataCenter;
use backend\modules\v1\utils\Handler;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;
use yii\swiftmailer\Message;

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


    /** 仓库库存情况
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
                        SELECT CASE WHEN SUBSTR(storeName,1,5)='万邑通UK' THEN '万邑通UK' ELSE storeName END storeName,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData`
                        GROUP BY CASE WHEN SUBSTR(storeName,1,5)='万邑通UK' THEN '万邑通UK' ELSE storeName END
                ) aa LEFT JOIN 
                (
                        SELECT CASE WHEN SUBSTR(storeName,1,5)='万邑通UK' THEN '万邑通UK' ELSE storeName END storeName,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData`
                        GROUP BY CASE WHEN SUBSTR(storeName,1,5)='万邑通UK' THEN '万邑通UK' ELSE storeName END
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

    /** 仓库库存情况状态明细
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

    /** 仓库库存情况开发明细
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

    /** 部门库存情况
     * Date: 2019-06-14 15:11
     * Author: henry
     * @return array
     */
    public function actionStockDepartDetail(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $sql = "SELECT IFNULL(aa.depart,'无部门') AS depart,useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND totalCostmoney>0 THEN 10000 ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END
                ) aa LEFT JOIN 
                (
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END
                ) bb ON IFNULL(aa.depart,'无部门')=IFNULL(bb.depart,'无部门')
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


    /** 部门库存情况状态明细
     * Date: 2019-06-14 15:11
     * Author: henry
     * @return array
     */
    public function actionStockDepartStatusDetail(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');

        $sql = "SELECT IFNULL(aa.depart,'无部门') AS depart,aa.goodsStatus,useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND totalCostmoney>0 THEN 10000 ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        IFNULL(goodsStatus,'无状态') goodsStatus,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        WHERE 1=1 ";
        if(isset($cond['depart']) && $cond['depart'])
            $sql .= " AND (IFNULL(d.department,'无部门') LIKE '%{$cond['depart']}%' AND IFNULL(p.department,'无部门') LIKE '%{$cond['depart']}%')";

        $sql .=   " GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END,IFNULL(goodsStatus,'无状态')
                ) aa LEFT JOIN 
                (
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        IFNULL(goodsStatus,'无状态') goodsStatus,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        WHERE 1=1 ";
        if(isset($cond['storeName']) && $cond['storeName'])
            $sql .= " AND (IFNULL(d.department,'无部门') LIKE '%{$cond['depart']}%' AND IFNULL(p.department,'无部门') LIKE '%{$cond['depart']}%')";
        $sql .=  " GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END,IFNULL(goodsStatus,'无状态')
                ) bb ON aa.depart=bb.depart AND IFNULL(aa.goodsStatus,'无状态')=IFNULL(bb.goodsStatus,'无状态')
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

    /** 部门库存情况开发明细
     * Date: 2019-06-14 15:11
     * Author: henry
     * @return array
     */
    public function actionStockDepartDeveloperDetail(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $sql = "SELECT IFNULL(aa.depart,'无部门') AS depart,aa.salerName,useNum,costmoney,notInStore,notInCostmoney,hopeUseNum,totalCostmoney,
                        IFNULL(30DayCostmoney,0) AS 30DayCostmoney,
                        CASE WHEN IFNULL(aveCostmoney,0)=0 AND totalCostmoney>0 THEN 10000 ELSE IFNULL(ROUND(totalCostmoney/aveCostmoney,1),0) END AS sellDays
                FROM(
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END AS salerName,
                        SUM(useNum) AS useNum,
                        SUM(costmoney) costmoney,
                        SUM(notInStore) notInStore,
                        SUM(notInCostmoney) notInCostmoney,
                        SUM(hopeUseNum) hopeUseNum,
                        SUM(totalCostmoney) totalCostmoney
                        FROM `cache_stockWaringTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        WHERE 1=1 ";
        if(isset($cond['depart']) && $cond['depart'])
            $sql .= " AND (IFNULL(d.department,'无部门') LIKE '%{$cond['depart']}%' AND IFNULL(p.department,'无部门') LIKE '%{$cond['depart']}%')";
        $sql .=  " GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END,
                            CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END
                ) aa LEFT JOIN 
                (
                        SELECT 
                        CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END AS salerName,
                        SUM(costMoney) AS 30DayCostmoney,
                        ROUND(SUM(costMoney)/30,4) AS aveCostmoney
                        FROM `cache_30DayOrderTmpData` c
                        LEFT JOIN `user` u ON u.username=c.salerName
						LEFT JOIN auth_department_child dc ON dc.user_id=u.id
					  	LEFT JOIN auth_department d ON d.id=dc.department_id
						LEFT JOIN auth_department p ON p.id=d.parent
                        WHERE 1=1 ";
        if(isset($cond['depart']) && $cond['depart'])
            $sql .= " AND (IFNULL(d.department,'无部门') LIKE '%{$cond['depart']}%' AND IFNULL(p.department,'无部门') LIKE '%{$cond['depart']}%')";
        $sql .=    " GROUP BY CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END,
                              CASE WHEN IFNULL(salerName,'')='' THEN '无人' ELSE salerName END
                ) bb ON aa.depart=bb.depart AND IFNULL(aa.salerName,'无人')=IFNULL(bb.salerName,'无人')
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

    /** pp余额
     * actionPpBalance
     * Date: 2020-12-04 13:12
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionPpBalance(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $beginTime = isset($cond['dateRange'][0]) ? $cond['dateRange'][0] : '';
        $endTime = isset($cond['dateRange'][1]) ? $cond['dateRange'][1] : '';
        $email = isset($cond['email']) ? $cond['email'] : '';
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $sql = "SELECT DownTime,PayPalEamil,TotalRMB,USD,AUD,CAD,EUR,GBP,Memo FROM Y_PayPalBalance WHERE 1=1 ";
        if($beginTime && $endTime) $sql .= " AND convert(varchar(10),DownTime,121) between '{$beginTime}' and '{$endTime}'";
        if($email) $sql .= " AND PayPalEamil LIKE '%{$email}%'";
        try{
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => [
                        'DownTime','PayPalEamil','TotalRMB', 'USD', 'AUD', 'CAD', 'EUR','GBP'
                    ],
                    'defaultOrder' => [
                        'PayPalEamil' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return $provider;
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** pp状态
     * actionPpBalance
     * Date: 2020-12-04 13:12
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionPpStatus(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $accountName = isset($cond['accountName']) ? $cond['accountName'] : '';
        $isUrUsed = isset($cond['isUrUsed']) ? $cond['isUrUsed'] : null;
        $isPyUsed = isset($cond['isPyUsed']) ? $cond['isPyUsed'] : null;
        $paypalStatus = isset($cond['paypalStatus']) ? $cond['paypalStatus'] : null;
        $memo  = isset($cond['memo ']) ? $cond['memo '] : null;
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $sql = "SELECT nid,accountName,isUrUsed,isPyUsed,paypalStatus,memo,createdTime,updatedTime FROM Y_PayPalStatus WHERE 1=1 ";
        if($accountName) $sql .= " AND accountName LIKE '%{$accountName}%'";
        if($paypalStatus) $sql .= " AND paypalStatus LIKE '%{$paypalStatus}%'";
        if($memo) $sql .= " AND memo LIKE '%{$memo}%'";
        if($isUrUsed || $isUrUsed === 0) $sql .= " AND isUrUsed = {$isUrUsed}";
        if($isPyUsed || $isPyUsed === 0) $sql .= " AND isPyUsed = {$isPyUsed}";
        try{
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => [
                        'accountName','isUrUsed','isPyUsed', 'paypalStatus'
                    ],
                    'defaultOrder' => [
                        'accountName' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return $provider;
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /** pp状态修改
     * Date: 2020-12-04 13:12
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionPpStatusUpdate(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $accountName = isset($cond['accountName']) ? $cond['accountName'] : '';

        $query = YPayPalStatus::findOne(['accountName' => $accountName]);
        if(!$query) {
            $query = new YPayPalStatus();
        }

        $query->setAttributes($cond);
        $changedAttr = $query->getDirtyAttributes();
//        var_dump($oldAttr);exit;
        $res = $query->save();
//        var_dump($query->isNewRecord);exit;
        if(!$res){
            return ['code' => 400, 'message' => 'Failed to save payPal info!'];
        }else{
            if(!$query->isNewRecord){
                $content = 'PayPal账号状态信息更新：';
                foreach ($changedAttr as $k => $v){
                    $content .= $k.'->'.$v.',';
                }
            }else{
                $content = '创建PayPal账号状态信息！';
            }
            if($changedAttr){
                $log = new YPayPalStatusLogs();
                $log->paypalNid = $query->nid;
                $log->opertor = Yii::$app->user->identity->username;
                $log->content = $content;
                $log->save();
            }
            return true;
        }
    }

    /** pp状态修改日志
     * Date: 2020-12-04 13:12
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionPpStatusUpdateLog(){
        $request = Yii::$app->request;
        if(!$request->isPost){
            return [];
        }
        $cond = $request->post('condition');
        $paypalNid = isset($cond['paypalNid']) ? $cond['paypalNid'] : null;
        $query = YPayPalStatusLogs::findAll(['paypalNid' => $paypalNid]);
        return $query;
    }



}
