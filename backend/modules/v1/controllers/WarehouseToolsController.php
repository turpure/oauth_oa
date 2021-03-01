<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\modules\v1\models\ApiWarehouseTools;
use backend\modules\v1\utils\ExportTools;
use Yii;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class WarehouseToolsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWareHouseTools';
    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    /**
     * @brief 拣货
     * @return array|bool
     */
    public function actionPick()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::setBatchNumber($condition);
    }

    /**
     * @brief 拣货人
     * @return array
     */
    public function actionPickMember()
    {
        return ApiWarehouseTools::getPickMember();
    }

    /**
     * @brief 拣货任务记录
     * @return \yii\data\ActiveDataProvider
     */
    public function actionScanningLog()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getScanningLog($condition);
    }

    /**
     * @brief 拣货人
     * @return array
     */
    public function actionSortMember()
    {
        return ApiWarehouseTools::getSortMember();
    }

    /**
     * @brief 保存分货任务
     * @return array|bool
     */
    public function actionSort()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::setSortBatchNumber($condition);
    }

    /**
     * @brief 分货扫描记录
     * @return \yii\data\ActiveDataProvider
     */
    public function actionSortLog()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getSortLog($condition);
    }

    /**
     * @brief 保存入库任务
     * @return array|bool
     */
    public function actionWarehouse()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::setWarehouseBatchNumber($condition);
    }

    /**
     * @brief 入库扫描记录
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWarehouseLog()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getWarehouseLog($condition);
    }

    /**
     * @brief 入库扫描记录下载
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWarehouseLogExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::warehouseLogExport($condition);
    }

    /**
     * @brief 拣货统计
     * @return \yii\data\ActiveDataProvider
     */
    public function actionPickStatistics()
    {
        $condition = Yii::$app->request->post()['condition'];

        return ApiWarehouseTools::getPickStatisticsData($condition);
    }


    /**
     * 库位匹配绩效查询
     * @return array|mixed
     */
    public function actionFreight()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiWarehouseTools::getFreightSpaceMatched($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }

    /**
     * 库位匹配扫描人
     * @return array|mixed
     */
    public function actionFreightMen()
    {
        try {
            return ApiWarehouseTools::getFreightMen();
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }


    /**
     * @brief 仓库仓位统计报表
     * @return \yii\data\ActiveDataProvider
     */
    public function actionWareStatistics()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getWareStatisticsData($condition);
    }


    /** 仓库仓位SKU对应表
     * Date: 2019-09-03 10:14
     * Author: henry
     * @return \yii\data\ArrayDataProvider
     */
    public function actionWareSku()
    {
        $condition = Yii::$app->request->post()['condition'];
        return ApiWarehouseTools::getWareSkuData($condition);
    }

    /**
     * Date: 2019-09-06 10:52
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionWareSkuExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $condition['pageSize'] = 100000;
        $title = ['SKU', '仓库', '库位', '操作人', '类型', '操作时间'];
        $dataProvider = ApiWarehouseTools::getWareSkuData($condition);
        $data = $dataProvider->getModels();
        if ($data) {
            ExportTools::toExcelOrCsv('WareSkuExport', $data, 'Xls', $title);
        }
    }


    public function actionIntegral()
    {
        $month = date('Y-m', strtotime('-1 days'));
        $con = Yii::$app->request->post('condition');
        $month = isset($con['month']) && $con['month'] ? $con['month'] : $month;
        $sql = "SELECT * FROM warehouse_integral_report WHERE month = '{$month}'";
        if (isset($con['group']) && $con['group']) $sql .= " AND `group`='{$con['group']}'";
        if (isset($con['job']) && $con['job']) $sql .= " AND job='{$con['job']}'";
        if (isset($con['team']) && $con['team']) $sql .= " AND team='{$con['team']}'";
        if (isset($con['name']) && $con['name']) $sql .= " AND name='{$con['name']}'";
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    public function actionQueryInfo()
    {
        $type = Yii::$app->request->get('type', 'job');
        if (!in_array($type, ['job', 'name', 'group'])) {
            return [
                'code' => 400,
                'message' => 'type is not correct value!',
            ];
        }
        try {
            $sql = "SELECT DISTINCT `{$type}` FROM warehouse_intergral_other_data_every_month 
                    where IFNULL(`{$type}`,'')<>'' ";
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($query, $type);
        } catch (Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage(),
            ];
        }
    }

    /////////////////////////////////////每日发货量/////////////////////////////////////////////////////
    public function actionDailyDelivery(){
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $dailyData = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}'")->queryAll();
        $dailyDataPro = new ArrayDataProvider([
            'allModels' => $dailyData,
            'sort' => [
                'attributes' => ['dt', 'singleNum', 'multiNum', 'totalNum'],
                'defaultOrder' => [
                    'dt' => SORT_ASC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        $packageData = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}', 1 ")->queryAll();
        $packageDataPro = new ArrayDataProvider([
            'allModels' => $packageData,
            'sort' => [
                'attributes' => ['packageMen', 'singleNum', 'multiNum', 'totalNum'],
                'defaultOrder' => [
                    'singleNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        return [
            'dailyData' => $dailyDataPro->getModels(),
            'packageData' => $packageDataPro->getModels(),
        ];
    }

    /** 每日发货量导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDailyDeliveryExport(){
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $dailyData = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}'")->queryAll();
        $title = ['发货日期','单品订单数','多品订单数','总订单数'];
        ExportTools::toExcelOrCsv('DailyDelivery', $dailyData, 'Xls', $title);
    }

    /** 打包定单量导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPackageOrderExport(){
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $dailyData = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}', 1")->queryAll();
        $title = ['打包人员','单品订单数','多品订单数','总订单数'];
        ExportTools::toExcelOrCsv('PackageOrder', $dailyData, 'Xls', $title);
    }



    /////////////////////////////////////仓位总况/////////////////////////////////////////////////////

    /** 仓位总况
     * actionPositionOverview
     * Date: 2021-02-23 10:56
     * Author: henry
     * @return array
     */
    public function actionPositionOverview()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        //获取仓库ID
        $storeId = Yii::$app->py_db->createCommand("SELECT NID FROM B_Store WHERE StoreName='{$store}'")->queryScalar();
        //仓位个数
        $locationSql = "SELECT COUNT(DISTINCT LocationName) AS Number FROM [dbo].[B_StoreLocation](nolock) WHERE StoreID='{$storeId}'";
        $locationNum = Yii::$app->py_db->createCommand($locationSql)->queryScalar();
        //有SKU仓位数
        $skuLocationSql = "SELECT COUNT(DISTINCT LocationName) AS Number FROM [dbo].[B_StoreLocation](nolock) sl
                        INNER JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID WHERE sl.StoreID='{$storeId}'";
        $skuLocationNum = Yii::$app->py_db->createCommand($skuLocationSql)->queryScalar();
        //空仓位数
        $emptyLocationSql = "SELECT COUNT(DISTINCT LocationName) AS Number FROM [dbo].[B_StoreLocation](nolock) sl
                        Left JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID 
                        WHERE sl.StoreID='{$storeId}' AND ISNULL(gs.sku,'')=''";
        $emptyLocationNum = Yii::$app->py_db->createCommand($emptyLocationSql)->queryScalar();
        //有库存SKU个数
        $locationSkuSql = "SELECT COUNT(DISTINCT sku) AS Number FROM [dbo].[B_StoreLocation](nolock) sl
                        INNER JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID 
                        LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID
                        WHERE sl.StoreID='{$storeId}' AND cs.Number > 0 ";
        $locationSkuNum = Yii::$app->py_db->createCommand($locationSkuSql)->queryScalar();

        $locationData = [
            'locationNum' => $locationNum,
            'skuLocationNum' => $skuLocationNum,
            'emptyLocationNum' => $emptyLocationNum,
            'locationSkuNum' => $locationSkuNum,
            'skuLocationRate' => (string)round($locationSkuNum / ($locationNum ?: 1), 2),
        ];

        $sql = "SELECT skuNum, COUNT(LocationName) AS locationNum FROM(
                SELECT sl.LocationName,COUNT(DISTINCT gs.SKU) AS skuNum
                FROM [dbo].[B_StoreLocation](nolock) sl
                INNER JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID
                WHERE sl.StoreID='{$storeId}' AND cs.Number > 0 GROUP BY sl.LocationName) s
                GROUP BY skuNum ORDER BY skuNum DESC";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['locationNum', 'skuNum'],
                'defaultOrder' => [
                    'skuNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        return ['locationData' => $locationData, 'skuData' => $dataProvider->getModels()];
    }


    /** 仓位总况 明细导出
     * actionPositionOverviewExport
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionOverviewDetailExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        //获取仓库ID
        $storeId = Yii::$app->py_db->createCommand("SELECT NID FROM B_Store WHERE StoreName='{$store}'")->queryScalar();

        $sql = "SELECT sl.LocationName, gs.SKU,
                LocationSkuNum = (
                    SELECT COUNT(DISTINCT gst.SKU) FROM [dbo].[B_StoreLocation](nolock) slt
                    INNER JOIN B_GoodsSKU(nolock) gst ON slt.NID=gst.LocationID
                    LEFT JOIN KC_CurrentStock(nolock) cst ON gst.NID=cst.GoodsSKUID AND cst.StoreID=slt.StoreID
                    WHERE slt.StoreID='{$storeId}' AND cst.Number > 0 AND slt.LocationName=sl.LocationName
                    GROUP BY slt.LocationName)
                FROM [dbo].[B_StoreLocation](nolock) sl
                INNER JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID
                WHERE sl.StoreID='{$storeId}' AND cs.Number > 0";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $dataProvider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['locationNum', 'SKU', 'LocationSkuNum'],
                'defaultOrder' => [
                    'LocationSkuNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        $title = ['仓位', '有库存SKU', '含有库存SKU个数'];
        ExportTools::toExcelOrCsv('positionOverviewDetail', $dataProvider->getModels(), 'Xls', $title);

    }


    /////////////////////////////////////仓位明细/////////////////////////////////////////////////////

    /** 仓位明细
     * actionPositionDetail
     * Date: 2021-02-24 9:01
     * Author: henry
     * @return ArrayDataProvider
     */
    public function actionPositionDetail()
    {
        $cond = Yii::$app->request->post('condition', []);
        $page = \Yii::$app->request->get('page', 1);;
        $pageSize = $condition['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionDetails($cond);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['skuNum', 'stockSkuNum'],
                'defaultOrder' => [
                    'stockSkuNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /** 仓位明细-- 主表导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailExport(){
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionDetails($cond);
        $title = ['仓库', '仓位', 'SKU个数', '有库存SKU个数'];
        ExportTools::toExcelOrCsv('positionDetail', $data, 'Xls', $title);

    }

    /** 仓位明细-- 查看明细
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailView()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $location = $cond['location'];

        $sql = "SELECT gs.sku,skuName,goodsskustatus,cs.number,g.devDate
                FROM [dbo].[B_StoreLocation](nolock) sl
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID 
                WHERE s.StoreName='{$store}' AND sl.LocationName='{$location}'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        return  new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName','goodsskustatus','number','devDate'],
                'defaultOrder' => [
                    'number' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 10000,
            ],
        ]);

    }
    /** 仓位明细-- 明细导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailViewExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionDetailsView($cond);
        $title = ['仓库', '仓位', 'SKU个数', 'SKU','SKU名称','SKU状态','库存数量','开发日期'];
        ExportTools::toExcelOrCsv('positionDetailView', $data, 'Xlsx', $title);

    }


    /////////////////////////////////////仓位查询/////////////////////////////////////////////////////

    /** 仓位查询
     * actionPositionSearch
     * Date: 2021-02-24 9:01
     * Author: henry
     * @return ArrayDataProvider
     */
    public function actionPositionSearch()
    {
        $cond = Yii::$app->request->post('condition', []);
        $page = Yii::$app->request->post('page', 1);
        $pageSize = $condition['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionSearchData($cond);
        return  new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName','goodsSkuStatus','Number','devDate'],
                'defaultOrder' => [
                    'Number' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /** 仓位查询--结果导出
     * actionPositionSearchExport
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionSearchExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionSearchData($cond);
        $title = ['仓库', '仓位', 'SKU','SKU名称','SKU状态','库存数量','开发日期'];
        ExportTools::toExcelOrCsv('positionSearch', $data, 'Xlsx', $title);
    }

/////////////////////////////////////无库存SKU查询与处理/////////////////////////////////////////////////////

    /** 仓位无库存SKU 查询
     * actionPositionSearch
     * Date: 2021-02-24 9:01
     * Author: henry
     * @return ArrayDataProvider
     */
    public function actionPositionManage()
    {
        $cond = Yii::$app->request->post('condition', []);
        $page = Yii::$app->request->post('page', 1);
        $pageSize = $condition['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionManageData($cond);
        return  new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName','goodsSkuStatus','number','devDate'],
                'defaultOrder' => [
                    'devDate' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'page' => $page - 1,
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /** 仓位无库存SKU导出
     * actionPositionManageExport
     * Date: 2021-02-24 9:32
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionManageExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionManageData($cond);
        $title = ['仓库', '仓位', 'SKU','SKU名称','SKU状态','库存数量','开发日期'];
        ExportTools::toExcelOrCsv('positionManage', $data, 'Xlsx', $title);
    }

    public function actionPositionSkuDelete()
    {
        $condition = Yii::$app->request->post('condition', []);
        try{
            $res = ApiWarehouseTools::positionSkuDelete($condition);
            if($res){
                return [
                    'code' => 400,
                    'message' => 'error',
                    'data' => $res,
                ];
            }
            return true;
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }


}
