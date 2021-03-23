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
            'beginDate' => isset($cond['dateRange'][0]) ? $cond['dateRange'][0] :'',
            'endDate' => isset($cond['dateRange'][1]) ? $cond['dateRange'][1] :'',
            'devBeginDate' => isset($cond['devDateRange'][0]) ? $cond['devDateRange'][0] :'',
            'devEndDate' => isset($cond['devDateRange'][1]) ? $cond['devDateRange'][1] :'',
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
            'devBeginDate' => isset($cond['devDateRange'][0]) ? $cond['devDateRange'][0] :'',
            'devEndDate' => isset($cond['devDateRange'][1]) ? $cond['devDateRange'][1] :'',
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
        }

        catch (\Exception  $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }

    public function actionRefundSuffixRate()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiReport::getRefundSuffixRate($condition);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            $data =  ApiReport::exportDevRateGoodsProfit($condition);
            $title = ['开发员','产品编码','主图', '商品名称','开发日期', '产品状态', '推荐人', '销量','销售额','总利润','近三个月单月最高利润', '利润率(%)'];
            ExportTools::toExcelOrCsv('dev-rate-goods-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }



    /**
     * 开发汇率下账号产品利润-- 此处只考虑清仓计划里面的产品
     * @return array
     */
    public function actionDevRateSuffixGoodsProfit()
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
            ];
            return ApiReport::getDevRateSuffixGoodsProfit($condition);
        }
        catch (\Exception $why) {
        return ['message' => $why->getMessage(), 'code' => $why->getCode()];

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
            ];
            $data = ApiReport::getDevRateSuffixGoodsProfit($condition)->models;
            $title = ['开发员','产品编码','主图', '商品名称','开发日期', '产品状态', '推荐人', '销量','销售额','总利润', '利润率'];
            ExportTools::toExcelOrCsv('dev-profit', $data, 'Xls', $title);
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
        }
    }


    public function actionTruncateClearList()
    {
        try {
            ApiReport::truncateClearList();
        } catch (\Exception $why) {
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
            return ['message' => $why->getMessage(), 'code' => $why->getCode()];
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
        return ['eBay-义乌仓', 'eBay-海外仓', 'Wish', 'Aliexpress', 'Amazon', 'Joom', 'VOVA','Shopee','Shopify','Lazada'];
    }
}
