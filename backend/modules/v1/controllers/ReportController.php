<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-06-12 14:15
 */

namespace backend\modules\v1\controllers;

use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiUser;
use Codeception\Template\Api;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use yii\data\ArrayDataProvider;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use Yii;
use backend\modules\v1\utils\Handler;
use backend\modules\v1\utils\ExportTools;

class ReportController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiReport';

    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];

    public function behaviors()
    {

        $behaviors = ArrayHelper::merge([
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'sales' => ['post', 'options'],
                    'develop' => ['post', 'options'],
                    'purchase' => ['post', 'options'],
                    'Possess' => ['post', 'options'],
                    'ebay-sales' => ['post', 'options'],
                    'sales-trend' => ['post', 'options'],
                    'profit' => ['post', 'options'],
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
     * @throws \Exception
     */

    public function actionSales()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $exchangeRate = ApiSettings::getExchangeRate();
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'dateType' => $cond['dateType'] ?: 0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'queryType' => $paramsFilter['queryType'],
            'store' => implode(',', $paramsFilter['store']),
            'warehouse' => $cond['store'] ? implode(',', $cond['store']) : '',
            'exchangeRate' => $exchangeRate['salerRate'],
            'wishExchangeRate' => $exchangeRate['wishSalerRate'],
        ];
        return ApiReport::getSalesReport($condition);
    }

    /**
     * @brief develop profit report
     * @return array
     */


    public function actionDevelop()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'seller' => $cond['member'] ? implode(',', $cond['member']) : '',
            'flag' => 0
        ];
        $ret = ApiReport::getDevelopReport($condition);
        return $ret;
    }

    /** 开发毛利详情
     * Date: 2020-06-09 14:12
     * Author: henry
     * @return array
     */
    public function actionDevelopProfitDetail()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'seller' => $cond['member'] ? implode(',', $cond['member']) : '',
        ];
        $pageSize = isset($cond['pageSize']) ? $cond['pageSize'] : 20;
        $ret = ApiReport::getDevelopProfitDetailReport($condition);
        $totalSaleMoney = array_sum(ArrayHelper::getColumn($ret, 'saleMoneyRmbZn'));
        $totalCostMoney = array_sum(ArrayHelper::getColumn($ret, 'costMoneyRmb'));
        $totalPpEbay = array_sum(ArrayHelper::getColumn($ret, 'ppEbayZn'));
        $totalPackage = array_sum(ArrayHelper::getColumn($ret, 'packageFeeRmb'));
        $totalExpress = array_sum(ArrayHelper::getColumn($ret, 'expressFareRmb'));
        $totalProfit = array_sum(ArrayHelper::getColumn($ret, 'profit'));
        $totalRate = round($totalProfit * 100 / $totalSaleMoney, 2);
        $provider = new ArrayDataProvider([
            'allModels' => $ret,
            'sort' => ['attributes' =>
                [
                    'timeGroup', 'salerName', 'goodsCode', 'goodsName', 'categoryName', 'goodsSkuStatus',
                    'salerName2', 'sku', 'createDate', 'saleMoneyRmbZn', 'costMoneyRmb', 'ppEbayZn',
                    'packageFeeRmb', 'expressFareRmb', 'profit', 'rate'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return [
            'provider' => $provider,
            'extra' => [
                'totalSaleMoney' => $totalSaleMoney,
                'totalCostMoney' => $totalCostMoney,
                'totalPpEbay' => $totalPpEbay,
                'totalPackage' => $totalPackage,
                'totalExpress' => $totalExpress,
                'totalProfit' => $totalProfit,
                'totalRate' => $totalRate
            ]
        ];
    }

    /** 开发毛利详情 導出
     * Date: 2020-06-09 14:15
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDevelopProfitDetailExport()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'seller' => $cond['member'] ? implode(',', $cond['member']) : ''
        ];
        $data = ApiReport::getDevelopProfitDetailReport($condition);
//        var_dump($ret);exit;
        $title = ['时间区域', '开发员1', '开发员2', '商品编码', 'SKU', '商品名称', '类目', '商品状态', '开发时间', '销售额￥', '成本￥', 'PP+Ebay费用￥', '打包费￥', '物流费￥', '利润', '利润率%'];
        ExportTools::toExcelOrCsv('dev-profit-detail', $data, 'Xls', $title);
    }


    /**
     * @brief Purchase profit report
     * @return array
     */
    public function actionPurchase()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'purchase' => $cond['member'] ? implode(',', $cond['member']) : '',
        ];
        $ret = ApiReport::getPurchaseReport($condition);
        return $ret;
    }


    /**
     * @brief Possess profit report
     * @return array
     */
    public function actionPossess()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'possess' => $cond['member'] ? implode(',', $cond['member']) : '',
        ];
        $ret = ApiReport::getPossessReport($condition);
        return $ret;
    }

    /**
     * @brief EbaySales profit report
     * @return array
     */
    public function actionEbaySales()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
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
    public function actionSalesTrend()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $queryParams = [
            'department' => $cond['department'],
            'secDepartment' => $cond['secDepartment'],
            'platform' => $cond['plat'],
            'username' => $cond['member'],
            'store' => $cond['account'],
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
        $ret = ApiReport::getSalesTrendReport($condition);
        return $ret;
    }

    /**
     * @brief profit trend  report
     * @return array
     * @throws  \Exception
     */
    public function actionProfitTrend()
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
        $exchangeRate = ApiSettings::getExchangeRate();
        $condition = [
            'store' => $params['store'] ? implode(',', $params['store']) : '',
            'queryType' => $params['queryType'],
            'dateFlag' => $cond['dateType'],
            'showType' => $cond['flag'] ?: 0,
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'exchangeRate' => $exchangeRate['salerRate']
        ];

        return ApiReport::getProfitTrendReport($condition);
    }

    /**
     * @brief 订单销量报表
     * @return array
     * @throws \Exception
     */
    public function actionOrderCount()
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

        $ret = ApiReport::getOrderCountReport($condition);
        return $ret;
    }

    /**
     * @brief Sku销量报表
     * @return array
     * @throws \Exception
     */
    public function actionSkuCount()
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

        $ret = ApiReport::getSkuCountReport($condition);
        return $ret;
    }

    /** 账号产品利润表
     * Date: 2019-10-11 16:22
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionAccount()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => isset($cond['dateRange'][0]) ? $cond['dateRange'][0] : '',
            'endDate' => isset($cond['dateRange'][1]) ? $cond['dateRange'][1] : '',
            'devBeginDate' => isset($cond['devDateRange'][0]) ? $cond['devDateRange'][0] : '',
            'devEndDate' => isset($cond['devDateRange'][1]) ? $cond['devDateRange'][1] : '',
            'sku' => $cond['sku'],
            'goodsName' => isset($cond['goodsName']) && $cond['goodsName'] ? $cond['goodsName'] : '',
            'salesman' => $cond['member'] ? implode(',', $cond['member']) : '',
            'chanel' => $cond['plat'],
            'suffix' => $cond['account'] ? implode(',', $cond['account']) : '',
            'storeName' => $cond['store'] ? implode(',', $cond['store']) : '',
            'start' => $cond['start'],
            'limit' => $cond['limit'],
        ];
        return ApiReport::getProfitReport($condition);

    }

    /** 账号产品毛利导出
     * Date: 2019-09-18 11:47
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionAccountExport()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'dateFlag' => $cond['dateType'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'devBeginDate' => isset($cond['devDateRange'][0]) ? $cond['devDateRange'][0] : '',
            'devEndDate' => isset($cond['devDateRange'][1]) ? $cond['devDateRange'][1] : '',
            'sku' => $cond['sku'],
            'goodsName' => isset($cond['goodsName']) && $cond['goodsName'] ? $cond['goodsName'] : '',
            'salesman' => $cond['member'] ? implode(',', $cond['member']) : '',
            'chanel' => $cond['plat'],
            'suffix' => $cond['account'] ? implode(',', $cond['account']) : '',
            'storeName' => $cond['store'] ? implode(',', $cond['store']) : '',
            'start' => $cond['start'],
            'limit' => $cond['limit'],
        ];
        $name = 'AccountExport';
        list($title, $data) = ApiReport::getProfitReportExport($condition);
        ExportTools::toExcelOrCsv($name, $data, 'Xls', $title);

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
            'member' => $cond['member']
        ];
        //print_r($condition);exit;
        return ApiReport::getIntroduceReport($condition);
    }

    ##################### 退款分析-开始 ##################################

    /**
     * suffix refund details
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionRefund()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'type' => $cond['type'],
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getRefundDetails($condition);
    }

    /**
     * ebay托管后的退款
     *
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionEbayRefund()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getEbayRefundDetails($condition);
    }

    /**
     * ebay托管后的店铺杂费
     *
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionEbayStoreFee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            //'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'suffix' => $paramsFilter['store'],
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getEbayStoreFee($condition);
    }



    /**
     * suffix wish refund details
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionWishRefund()
    {
        try {
            $request = Yii::$app->request->post();
            $cond = $request['condition'];
            $params = [
                'platform' => isset($cond['plat']) ? $cond['plat'] : [],
                'username' => isset($cond['member']) ? $cond['member'] : [],
                'store' => isset($cond['account']) ? $cond['account'] : []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $condition = [
                'type' => $cond['type'],
                'beginDate' => $cond['dateRange'][0],
                'endDate' => $cond['dateRange'][1],
                'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
                'page' => isset($cond['page']) ? $cond['page'] : 1,
                'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
            ];
            return ApiReport::getWishRefundDetails($condition);
        } catch (\Exception  $why) {
            return ['code' => 400, 'message' => $why->getMessage()];
        }

    }

    /**
     * @brief 退款账号分析
     */
    public function actionRefundAnalysisSuffix()
    {
        try {
            $refund = $this->actionRefund()['provider']->allModels;
            $ret = [];
            foreach ($refund as $row) {
                if (array_key_exists($row['suffix'], $ret)) {
                    $ret[$row['suffix']] += $row['refundZn'];
                } else {
                    $ret[$row['suffix']] = $row['refundZn'];
                }
            }
            $total = array_sum(array_values($ret));
            arsort($ret);
            if (count($ret) <= 10) {
                $item = $ret;
            } else {
                $item = array_slice($ret, 0, (int)(0.2 * count($ret)));
                $item['other'] = $total - array_sum(array_values($item));
            }
            $out = [];
            foreach ($item as $key => $value) {
                $out['item'][] = ['name' => $key, 'value' => $value];
            }
            $out['unit'] = '￥';
            return $out;
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }

    }

    /**
     * @brief 退款平台分析
     * @return array|ArrayDataProvider
     */
    public function actionRefundAnalysisPlat()
    {
        try {
            $refund = $this->actionRefund()['provider']->allModels;
            $ret = [];
            foreach ($refund as $row) {
                if (array_key_exists($row['platform'], $ret)) {
                    $ret[$row['platform']] += (float)$row['refundZn'];
                } else {
                    $ret[$row['platform']] = (float)$row['refundZn'];
                }
            }
            $total = array_sum(array_values($ret));
            arsort($ret);
            if (count($ret) <= 10) {
                $item = $ret;
            } else {
                $item = array_slice($ret, 0, (int)(0.2 * count($ret)));
                $item['other'] = $total - array_sum(array_values($item));
            }
            $out = [];
            foreach ($item as $key => $value) {
                $out['item'][] = ['name' => $key, 'value' => $value];
            }
            $out['unit'] = '￥';
            return $out;
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }

    }

    /**
     * @brief 退款产品分析
     * @return array|ArrayDataProvider
     */
    public function actionRefundAnalysisGoods()
    {
        try {
            $refund = $this->actionRefund()['provider']->allModels;
            $ret = [];
            foreach ($refund as $row) {
                if (array_key_exists($row['goodsCode'], $ret)) {
                    $ret[$row['goodsCode']] += (int)$row['times'];
                } else {
                    $ret[$row['goodsCode']] = (int)$row['times'];
                }
            }
            $total = array_sum(array_values($ret));
            arsort($ret);
            if (count($ret) <= 10) {
                $item = $ret;
            } else {
                $item = array_slice($ret, 0, (int)(0.2 * count($ret)));
                $item['other'] = $total - array_sum(array_values($item));
            }
            $out = [];
            foreach ($item as $key => $value) {
                $out['item'][] = ['name' => $key, 'value' => $value];
            }
            $out['unit'] = '次';
            return $out;
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    /**
     * @brief 退款物流分析
     * @return array|ArrayDataProvider
     */
    public function actionRefundAnalysisExpress()
    {
        try {
            $refund = $this->actionRefund()['provider']->allModels;
            $ret = [];
            foreach ($refund as $row) {
                if (array_key_exists($row['expressWay'], $ret)) {
                    ++$ret[$row['expressWay']];
                } else {
                    $ret[$row['expressWay']] = 1;
                }
            }
            $total = array_sum(array_values($ret));
            arsort($ret);
            if (count($ret) <= 10) {
                $item = $ret;
            } else {
                $item = array_slice($ret, 0, (int)(0.2 * count($ret)));
                $item['other'] = $total - array_sum(array_values($item));
            }
            $out = [];
            foreach ($item as $key => $value) {
                $out['item'][] = ['name' => $key, 'value' => $value];
            }
            $out['unit'] = '次';
            return $out;
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 物流退款比例
     * @return array|mixed
     *
     */
    public function actionRefundExpressRate()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiReport::getRefundExpressRate($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    public function actionRefundSuffixRate()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiReport::getRefundSuffixRate($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    ##################### 退款分析-结束 #############################################

    /**
     * 销售死库明细
     * Date: 2019-01-04 10:21
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionDeadFee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $storeName = $cond['storename'] ? "'" . implode(',', $cond['storename']) . "'" : '';
        $storeName = str_replace(",", "','", $storeName);
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'storename' => $storeName,
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 20,
        ];
        return ApiReport::getDeadFee($condition);
    }

    /**
     * 其他死库明细
     * Date: 2019-01-04 10:21
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionOtherDeadFee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'member' => $cond['member'] ? ("'" . implode("','", $cond['member']) . "'") : '',
            'role' => $cond['role'],
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 20,
        ];
        return ApiReport::getOtherDeadFee($condition);
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
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getExtraFee($condition);

    }

    /**
     * eBay托管费
     * Date: 2021-04-27 13:38
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public function actionTrusteeshipFee()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        $params = [
            'platform' => isset($cond['plat']) ? $cond['plat'] : [],
            'username' => isset($cond['member']) ? $cond['member'] : [],
            'store' => isset($cond['account']) ? $cond['account'] : []
        ];
        $paramsFilter = Handler::paramsHandler($params);
        $condition = [
            'beginDate' => $cond['dateRange'][0],
            'endDate' => $cond['dateRange'][1],
            'suffix' => "'" . implode("','", $paramsFilter['store']) . "'",
            'page' => isset($cond['page']) ? $cond['page'] : 1,
            'pageSize' => isset($cond['pageSize']) ? $cond['pageSize'] : 10,
        ];
        return ApiReport::getTrusteeshipFee($condition);

    }

    /** 导出excel表格
     * Date: 2019-04-12 16:10
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * @throws \yii\db\Exception
     */
    public function actionExport()
    {
        $request = Yii::$app->request->post();
        $cond = $request['condition'];
        switch ($cond['type']) {
            case 'order'://退款订单明细下载
                $fileName = 'refundOrderData';
                $title = ['退款月份', '账号简称', '销售员', '商品名称', '商品编码', 'SKU',
                    '交易单号', '店铺单号', '合并单号', '仓库', '退款金额(原始币种)', '退款金额(￥)', '退款时间', '交易时间',
                    '国家', '平台', '物流方式',];
                $headers = ['refMonth', 'suffix', 'salesman', 'goodsName', 'goodsCode', 'goodsSku',
                    'tradeId', 'orderId', 'mergeBillId', 'storeName', 'refund', 'refundZn', 'refundTime', 'orderTime',
                    'orderCountry', 'platform', 'expressWay'];
                $data = $this->actionRefund()['provider']->getModels();
                break;
            case 'wishOrder':
                $fileName = 'wishRefundOrderData';
                $title = ['退款月份', '账号简称', '销售员', '商品名称', '商品编码', 'SKU',
                    '交易单号', '店铺单号', '合并单号', '仓库', '退款金额(原始币种)', '货币符号', '退款时间', '交易时间',
                    '国家', '平台', '物流方式',];
                $headers = ['refMonth', 'suffix', 'salesman', 'goodsName', 'goodsCode', 'goodsSku',
                    'tradeId', 'orderId', 'mergeBillId', 'storeName', 'refund', 'currencyCode', 'refundTime', 'orderTime',
                    'orderCountry', 'platform', 'expressWay'];
                $data = $this->actionWishRefund()['provider']->getModels();
                break;
            case 'goods'://退款产品明细下载
                $fileName = 'refundGoodsData';
                $title = ['账号简称', '销售员', '商品名称', '商品编码', 'SKU', '退款次数'];
                $headers = ['suffix', 'salesman', 'goodsName', 'goodsCode', 'goodsSku', 'times'];
                $data = $this->actionRefund()['provider']->getModels();
                break;
            case 'extra'://杂费明细下载
                $fileName = 'extraFeeData';
                //$fileName = '杂费明细';
                $title = ['账号简称', '杂费(￥)', '备注', '日期', '销售员'];
                $headers = ['suffix', 'saleOpeFeeZn', 'comment', 'dateTime', 'salesman'];
                $data = $this->actionExtraFee()['provider']->getModels();
                break;
            case 'otherDeadFee'://其他死库明细下载
                $fileName = 'otherDeadFeeData';
                $title = ['导入时间', '死库类型', '开发员', '开发员2', '美工', '推荐人', '采购', '仓库',
                    '商品编码', 'SKU', '商品名称', '创建时间', '最后采购时间', '盘点数量', '盘点前单价', '盘少单价(死库)', '盘后单价',
                    '分摊死库', '创建时间2'];
                $headers = ['importDate', 'type', 'developer', 'developer2', 'possessMan', 'introducer', 'purchaser', 'storeName',
                    'goodsCode', 'sku', 'goodsName', 'createDate', 'lastPurchaseDate', 'checkNumber', 'preCheckPrice', 'deadPrice', 'aftCheckPrice',
                    'aveAmount', 'createDate2'];
                //print_r($this->actionOtherDeadFee());exit;
                $data = $this->actionOtherDeadFee()['provider']->getModels();
                break;
            case 'salesDeadFee'://销售死库明细下载
                $fileName = 'salesDeadFeeData';
                $title = ['导入时间', '死库类型', '平台', '账号简称', '销售员', '仓库',
                    '商品编码', 'SKU', '商品名称', '创建时间', '最后采购时间', '盘点数量', '盘点前单价', '盘少单价(死库)', '盘后单价',
                    '账号销量', '总销量', '库存金额', '分摊死库'];
                $headers = ['importDate', 'type', 'plat', 'suffix', 'salesman', 'storeName',
                    'goodsCode', 'sku', 'goodsName', 'createDate', 'lastPurchaseDate', 'checkNumber', 'preCheckPrice', 'deadPrice', 'aftCheckPrice',
                    'suffixSalesNumber', 'totalNumber', 'amount', 'aveAmount'];
                $data = $this->actionDeadFee()['provider']->getModels();
                break;
            case 'trusteeshipFee'://eBay托管费
                $fileName = 'trusteeshipFee';
                $title = ['账号简称', '订单编号', '费用类型', '金额(£)','金额(￥)', '原始币种', '费用时间', '销售员'];
                $headers = ['suffix', 'orderId', 'fee_type', 'total', 'totalRmb', 'currency_code', 'fee_time', 'salesman'];
                $data = $this->actionTrusteeshipFee()['provider']->getModels();
                break;
            case 'ebayRefund'://eBay托管后退款
                $fileName = 'ebayRefund';
                $title = ['退款月份', '账号简称', '销售员', '商品名称', '商品编码', 'SKU',
                    '交易单号', '店铺单号', '合并单号', '仓库', '退款金额(原始币种)', '退款金额(￥)', '退款时间', '交易时间',
                    '国家', '平台', '物流方式',];
                $headers = ['refMonth', 'suffix', 'salesman', 'goodsName', 'goodsCode', 'goodsSku',
                    'tradeId', 'orderId', 'mergeBillId', 'storeName', 'refund', 'refundZn', 'refundTime', 'orderTime',
                    'orderCountry', 'platform', 'expressWay'];
                $data = $this->actionEbayRefund()['provider']->getModels();
                break;
            case 'ebayStoreFee'://eBay托管后店铺杂费
                $fileName = 'ebayStoreFee';
                $title = ['销售员', '账号简称', '费用类型', '币种', '原始币种金额', '人民币金额(￥)'];
                $headers = ['salerman', 'suffix', 'feeType', 'currency', 'value', 'valueZn'];
                $data = $this->actionEbayStoreFee()['provider']->getModels();
                break;
            default://默认销售死库明细下载
                $fileName = 'salesDeadFeeData';
                $title = ['导入时间', '死库类型', '平台', '账号简称', '销售员', '仓库',
                    '商品编码', 'SKU', '商品名称', '创建时间', '最后采购时间', '盘点数量', '盘点前单价', '盘少单价(死库)', '盘后单价',
                    '账号销量', '总销量', '库存金额', '分摊死库'];
                $headers = ['importDate', 'type', 'plat', 'suffix', 'salesman', 'storeName',
                    'goodsCode', 'sku', 'goodsName', 'createDate', 'lastPurchaseDate', 'checkNumber', 'preCheckPrice', 'deadPrice', 'aftCheckPrice',
                    'suffixSalesNumber', 'totalNumber', 'amount', 'aveAmount'];
                $data = $this->actionDeadFee()['provider']->getModels();
        }

        //ApiTool::exportExcel($fileName, $headers, $data);
        $fileName = iconv('utf-8', 'GBK', $fileName);//文件名称
        $fileName = $fileName . date('_YmdHis');//or $xlsTitle 文件名称可根据自己情况设定
        $spreadsheet = new Spreadsheet();
        $worksheet = $spreadsheet->getActiveSheet();

        //设置表头字段名称
        foreach ($title as $key => $value) {
            $worksheet->setCellValueByColumnAndRow($key + 1, 1, $value);
        }
        //填充表内容
        foreach ($data as $k => $rows) {
            foreach ($headers as $i => $val) {
                $worksheet->setCellValueExplicitByColumnAndRow($i + 1, $k + 2, $rows[$val], DataType::TYPE_STRING);
            }
        }
        header('pragma:public');
        header('Access-Control-Allow-Origin: *');
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename="' . $fileName . '.xls"');
        header('Cache-Control: max-age=0');
        //attachment新窗口打印inline本窗口打印
        $writer = IOFactory::createWriter($spreadsheet, 'Xls');
        $writer->save('php://output');
        exit;
    }

    /**
     * @brief 开发款数限制
     * @return array
     */
    public function actionDevLimit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getDevLimit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 开发产品利润表
     * @return mixed
     */
    public function actionDevGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getDevGoodsProfit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 开发汇率产品利润表
     * @return mixed
     */
    public function actionDevRateGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getDevRateGoodsProfit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    /**
     * @brief 开发汇率开发利润表
     * @return mixed
     */
    public function actionDevRateDeveloperGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getDevRateDeveloperGoodsProfit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    /**
     * @brief 导出开发汇率开发利润表
     * @return array
     */
    public function actionExportDevRateDeveloperGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $condition['pageSize'] = 100000;
            $data = ApiReport::getDevRateDeveloperGoodsProfit($condition)->allModels;
            $title = ['开发员', '销售额', '销量', '总利润', '近三个月单月最高利润', '增长利润'];
            ExportTools::toExcelOrCsv('dev-rate-developer-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    /**
     * @brief 导出开发利润
     * @return array
     */
    public function actionExportDevRateGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $data = ApiReport::exportDevRateGoodsProfit($condition);
            $title = ['开发员', '推荐人', '商品编码', '商品名称', '主图', '开发日期', '商品状态', '销量', '销售额', '总利润', '利润率(%)'];
            ExportTools::toExcelOrCsv('dev-rate-goods-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    ////////////////////////////////////清仓列表//////////////////////////////////////

    /**
     * 开发汇率下账号产品利润-- 此处只考虑清仓计划里面的产品
     * @return array
     */
    public function actionDevRateSuffixGoodsProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $params = [
                'platform' => isset($condition['plat']) ? $condition['plat'] : [],
                'username' => isset($condition['member']) ? $condition['member'] : [],
                'store' => isset($condition['account']) ? $condition['account'] : []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $condition = [
                'dateType' => $condition['dateType'] ?: 0,
                'beginDate' => $condition['dateRange'][0],
                'endDate' => $condition['dateRange'][1],
                'queryType' => $paramsFilter['queryType'],
                'store' => implode(',', $paramsFilter['store']),
                'warehouse' => $condition['store'] ? implode(',', $condition['store']) : '',
                'pageSize' => isset($condition['pageSize']) ? $condition['pageSize'] : 10
            ];
            return ApiReport::getDevRateSuffixGoodsProfit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];

        }

    }


    /**
     * @brief 导出开发利润
     * @return array
     */
    public function actionExportDevRateSuffixGoodsProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $params = [
                'platform' => isset($cond['plat']) ? $condition['plat'] : [],
                'username' => isset($cond['member']) ? $condition['member'] : [],
                'store' => isset($cond['account']) ? $condition['account'] : []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $condition = [
                'dateType' => $condition['dateType'] ?: 0,
                'beginDate' => $condition['dateRange'][0],
                'endDate' => $condition['dateRange'][1],
                'queryType' => $paramsFilter['queryType'],
                'store' => implode(',', $paramsFilter['store']),
                'warehouse' => $condition['store'] ? implode(',', $condition['store']) : '',
                'pageSize' => $condition['pageSize'] ?? 10000
            ];
            $data = ApiReport::getDevRateSuffixGoodsProfit($condition)->models;
            $title = ['产品编码', '产品名称', '平台', '部门', '账号', '销售员', '仓库', '销量', '销售额', '总利润', '利润率(%)'];
            ExportTools::toExcelOrCsv('dev-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }



    /**
     * @brief 清仓列表
     * @return mixed
     */
    public function actionClearList()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getClearList($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 清仓列表
     * @return mixed
     */
    public function actionExportClearList()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $condition['pageSize'] = 100000;
            $data = ApiReport::getClearList($condition)->models;
            $title = ['产品编码', '产品状态', '仓库', '清仓计划', '计划创建时间', '产品名称', '主图', '主类目', '子类目', '库存数量', '库存金额', '开发员', '销售员'];
            ExportTools::toExcelOrCsv('clear-list', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 清仓列表
     * @return mixed
     */
    public function actionImportClearList()
    {
        try {
            $request = Yii::$app->request->post();
//            $condition = $request['condition'];
            return ApiReport::importClearList();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    /**
     * @brief 清仓列表
     * @return mixed
     */
    public function actionExportClearTemplate()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            ApiReport::exportClearListTemplate($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    public function actionTruncateClearList()
    {
        try {
            ApiReport::truncateClearList();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }

    }

    ////////////////////////////////////海外仓清仓列表//////////////////////////////////////

    /**
     * 开发汇率下账号产品利润-- 此处只考虑清仓计划里面的产品
     * @return array
     */
    public function actionEbayClearSkuProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $params = [
                'platform' => isset($condition['plat']) ? $condition['plat'] : [],
                'username' => isset($condition['member']) ? $condition['member'] : [],
                'store' => isset($condition['account']) ? $condition['account'] : []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $condition = [
                'dateType' => $condition['dateType'] ?: 0,
                'beginDate' => $condition['dateRange'][0],
                'endDate' => $condition['dateRange'][1],
                'queryType' => $paramsFilter['queryType'],
                'store' => implode(',', $paramsFilter['store']),
                'warehouse' => $condition['store'] ? implode(',', $condition['store']) : '',
                'pageSize' => isset($condition['pageSize']) ? $condition['pageSize'] : 10
            ];
            return ApiReport::getEbayClearSkuProfit($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];

        }

    }


    /**
     * @brief 导出开发利润
     * @return array
     */
    public function actionExportEbayClearSkuProfit()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $params = [
                'platform' => isset($cond['plat']) ? $condition['plat'] : [],
                'username' => isset($cond['member']) ? $condition['member'] : [],
                'store' => isset($cond['account']) ? $condition['account'] : []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $condition = [
                'dateType' => $condition['dateType'] ?: 0,
                'beginDate' => $condition['dateRange'][0],
                'endDate' => $condition['dateRange'][1],
                'queryType' => $paramsFilter['queryType'],
                'store' => implode(',', $paramsFilter['store']),
                'warehouse' => $condition['store'] ? implode(',', $condition['store']) : '',
                'pageSize' => $condition['pageSize'] ?? 10000
            ];
            $data = ApiReport::EbayClearSkuProfit($condition)->models;
            $title = ['SKU', '产品编码', '产品名称', '平台', '部门', '账号', '销售员', '仓库', '销量', '销售额', '总利润', '利润率(%)'];
            ExportTools::toExcelOrCsv('dev-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 清仓列表
     * @return mixed
     */
    public function actionEbayClearList()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getEbayClearList($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 清仓列表导出
     * @return mixed
     */
    public function actionExportEbayClearList()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $condition['pageSize'] = 100000;
            $data = ApiReport::getEbayClearList($condition)->models;
            $title = ['SKU', 'SKU状态', '仓库', '清仓计划', '计划创建时间', 'SKU名称', '主图', '主类目', '子类目', '库存数量', '库存金额', '开发员', '销售员'];
            ExportTools::toExcelOrCsv('clear-list', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 清仓列表导入
     * @return mixed
     */
    public function actionImportEbayClearList()
    {
        try {
            return ApiReport::importEbayClearList();
        } catch (\Exception $why) {
            return ['message' => 400, 'code' => 400];
        }
    }


    /**
     * @brief 清仓列表模板
     * @return mixed
     */
    public function actionExportEbayClearTemplate()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            ApiReport::exportClearListTemplate($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }


    public function actionTruncateEbayClearList()
    {
        try {
            Yii::$app->py_db->createCommand()->update('oauth_clearPlanEbay',['isRemoved' => 1])->execute();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }

    }


    /**
     * @brief 导出开发利润
     * @return array
     */
    public function actionExportDevGoodsProfit()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            $condition['pageSize'] = 100000;
            $data = ApiReport::getDevGoodsProfit($condition)->models;
            $title = ['开发员', '推荐人', '产品编码', '产品名称', '产品图片', '开发日期', '产品状态', '销量', '销售额(￥)', '总利润(￥)', '利润率',
                'eBay销量', 'eBay利润(￥)', 'Wish销量', 'Wish利润(￥)', 'SMT销量', 'SMT利润(￥)', 'Joom销量',
                'Joom利润(￥)', 'Amazon销量', 'Amazon利润(￥)', '时间类型(0交易时间，1发货时间)', '时间'];
            ExportTools::toExcelOrCsv('dev-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 开发产品利润表
     * @return array
     */
    public function actionDevGoodsProfitDetail()
    {
        try {
            $request = Yii::$app->request->post();
            $condition = $request['condition'];
            return ApiReport::getDevGoodsProfitDetail($condition);

        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => 400];
        }
    }

    /**
     * @brief 开发状态
     * @return array
     */
    public function actionDevStatus()
    {
        return ApiReport::getDevStatus();
    }

    /**
     * @brief 销售历史利润
     * @return mixed
     */
    public function actionHistorySalesProfit()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::getHistorySalesProfit($condition);
    }

    /** 销售历史利润 导出
     * actionHistorySalesProfitExport
     * Date: 2020-12-09 14:51
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionHistorySalesProfitExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::exportHistorySalesProfit($condition);
    }


    /**
     * @brief 历史利润走势
     * @return array
     */
    public function actionHistoryProfit()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::getHistoryProfit($condition);
    }

    /**
     * @brief 历史利润排名
     * @return array
     */
    public function actionHistoryRank()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::getHistoryRank($condition);
    }

    /**
     * @brief 销售平台
     * @return array
     */
    public function actionHistoryPlat()
    {
        return ['eBay-义乌仓', 'eBay-海外仓', 'Wish', 'Aliexpress', 'Amazon', 'Joom', 'VOVA', 'Shopee', 'Shopify', 'Lazada'];
    }

    ////////////////////////////////////采购账期/////////////////////////////////////////////

    /**
     * 采购账期
     * Date: 2021-07-01 13:27
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionPurchaseAccountPeriod()
    {
        try {
            return ApiReport::getPurchaseAccountPeriod();
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 采购账期导出
     * Date: 2021-07-15 8:43
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPurchaseAccountPeriodExport()
    {
        $data = ApiReport::getPurchaseAccountPeriod();
        $res = [];
        foreach ($data as $val){
            $item = [];
            $item['采购员'] = $val['purchaser'];
            foreach ($val['value'] as $v){
                $item['账期-'.$v['dt']] = $v['orderMoney'];
            }
            $res[]= $item;
        }
        ExportTools::toExcelOrCsv('purchaseAccountPeriod', $res, 'Xlsx');
    }


    ////////////////////////////////////运营KPI/////////////////////////////////////////////


    /**
     * 运营KPI
     * Date: 2021-07-06 13:27
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionOperatorKpi()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::getOperatorKpi($condition);
    }

    /**
     * 运营KPI导出
     * Date: 2021-07-12 13:49
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionOperatorKpiExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiReport::getOperatorKpi($condition);
        $res = [];
        foreach ($data as &$v){
            $item['姓名'] = $v['name'];
            $item['月份'] = $v['month'];
            $item['部门'] = $v['depart'];
            $item['角色'] = $v['plat'];
            $item['入职时间'] = $v['hireDate'];
            $item['入职时长（月）'] = $v['hireMonth'];
            $item['保护期（入职90天内）'] = $v['flag'];
            $item['综合排名'] = $v['sort'] . '/' . $v['totalNum'];
            $item['综合得分'] = $v['totalScore'];
            $item['评价等级'] = $v['rank'];
            $item['更新时间'] = $v['updateTime'];
            $item['开发12月毛利'] = $v['profitTwo'];
            $item['毛利排名'] = $v['profitSort'] . '/' . $v['platNum'];
            $item['毛利排名得分'] = $v['profitSortScore'];
            $item['毛利值'] = $v['profit'];
            $item['毛利值得分'] = $v['profitScore'];
            $item['毛利绝对值增长'] = $v['profitAdd'];
            $item['毛利绝对值增长得分'] = $v['profitAddScore'];
            $item['毛利百分比增长(%)'] = $v['profitRate'];
            $item['毛利百分比增长得分'] = $v['profitRateScore'];
            $item['销售额绝对值增长'] = $v['salesAdd'];
            $item['销售额绝对值增长得分'] = $v['salesAddScore'];
            $item['销售额百分比增长(%)'] = $v['salesRate'];
            $item['销售额百分比增长得分'] = $v['salesRateScore'];
            $item['入职时间系数'] = $v['userHireRate'];
            $item['业绩指标总得分'] = $v['achieveTotalScore'];
            $item['合作度'] = $v['cooperateScore'];
            $item['积极性'] = $v['activityScore'];
            $item['执行力'] = $v['executiveScore'];
            $item['工作态度总得分'] = $v['workingTotalScore'];
            $item['新人培训'] = $v['otherTrainingScore'];
            $item['挑战专项加分'] = $v['otherChallengeScore'];
            $item['扣分项'] = $v['otherDeductionScore'];
            $res[] = $item;
        }
        ExportTools::toExcelOrCsv('operatorKpi', $res, 'Xlsx');
    }

    /**
     * 运营KPI历史数据
     * Date: 2021-07-06 13:27
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionOperatorKpiHistory()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiReport::getOperatorKpiHistory($condition);
        }catch (\Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * 运营KPI历史数据导出
     * Date: 2021-07-12 13:49
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionOperatorKpiHistoryExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiReport::getOperatorKpiHistory($condition);
        $out = [];
        foreach ($data as $v){
            $item['姓名'] = $v['name'];
            $item['部门'] = $v['depart'];
            $item['入职日期'] = $v['hireDate'];
            $item['综合比例'] = $v['totalRate'];
            $item['综合排名'] = $v['totalSort'];
            foreach ($v['value'] as $val){
                $item['评价等级-'.$val['month']] = $val['rank'];
            }
            $item['计数-A'] = $v['numA'];
            $item['计数-B'] = $v['numB'];
            $item['计数-C'] = $v['numC'];
            $item['计数-D'] = $v['numD'];
            $item['计数-保护期-A'] = $v['testNumA'];
            $item['计数-保护期-B'] = $v['testNumB'];
            $item['计数-保护期-C'] = $v['testNumC'];
            $item['计数-保护期-D'] = $v['testNumD'];
            $out[] = $item;
        }
        ExportTools::toExcelOrCsv('operatorKpi', $out, 'Xlsx');
    }

    /**
     * 运营其他平台KPI
     * Date: 2021-07-06 13:27
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public function actionOperatorKpiOther()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiReport::getOperatorKpiOther($condition);
    }

    /**
     * 运营其他平台KPI导出
     * Date: 2021-07-12 13:49
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionOperatorKpiOtherExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiReport::getOperatorKpiOther($condition);
        $res = [];
        foreach ($data as &$v){
            $item['姓名'] = $v['name'];
            $item['月份'] = $v['month'];
            $item['部门'] = $v['depart'];
            $item['角色'] = $v['plat'];
            $item['入职时间'] = $v['hireDate'];
            $item['入职时长（月）'] = $v['hireMonth'];
            $item['保护期（入职90天内）'] = $v['flag'];
            $item['综合得分'] = $v['totalScore'];
            $item['更新时间'] = $v['updateTime'];
            $item['开发12月毛利'] = $v['profitTwo'];
            $item['毛利排名'] = $v['profitSort'] . '/' . $v['platNum'];
            $item['毛利排名得分'] = $v['profitSortScore'];
            $item['毛利值'] = $v['profit'];
            $item['毛利值得分'] = $v['profitScore'];
            $item['毛利绝对值增长'] = $v['profitAdd'];
            $item['毛利绝对值增长得分'] = $v['profitAddScore'];
            $item['毛利百分比增长(%)'] = $v['profitRate'];
            $item['毛利百分比增长得分'] = $v['profitRateScore'];
            $item['销售额绝对值增长'] = $v['salesAdd'];
            $item['销售额绝对值增长得分'] = $v['salesAddScore'];
            $item['销售额百分比增长(%)'] = $v['salesRate'];
            $item['销售额百分比增长得分'] = $v['salesRateScore'];
            $item['入职时间系数'] = $v['userHireRate'];
            $item['业绩指标总得分'] = $v['achieveTotalScore'];
            $item['合作度'] = $v['cooperateScore'];
            $item['积极性'] = $v['activityScore'];
            $item['执行力'] = $v['executiveScore'];
            $item['工作态度总得分'] = $v['workingTotalScore'];
            $item['新人培训'] = $v['otherTrainingScore'];
            $item['挑战专项加分'] = $v['otherChallengeScore'];
            $item['扣分项'] = $v['otherDeductionScore'];
            $res[] = $item;
        }
        ExportTools::toExcelOrCsv('operatorKpiOther', $res, 'Xlsx');
    }

}
