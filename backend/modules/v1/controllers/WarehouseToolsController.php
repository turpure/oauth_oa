<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\models\ShopElf\OauthLoadSkuError;
use backend\models\ShopElf\OauthSysConfig;
use backend\models\ShopElf\OauthLabelGoodsRate;
use backend\modules\v1\models\ApiOverseas;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiWarehouseTools;
use backend\modules\v1\utils\ExportTools;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class WarehouseToolsController extends AdminController
{
    public $modelClass = 'backend\modules\v1\models\ApiWareHouseTools';
    public $serializer = [
        'class' => 'backend\modules\v1\utils\PowerfulSerializer',
        'collectionEnvelope' => 'items',
    ];
    public function behaviors()
    {
        return parent::behaviors();
    }


    ######################################拣货工具#########################################

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
        return ApiWarehouseTools::getWarehouseMember('pick');
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

    ######################################分拣#########################################

    /**
     * @brief 分拣人
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


    ######################################线下清仓工具#########################################

    /**
     * @brief 线下清仓工具-SKU列表
     */
    public function actionCleanOfflineList()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiWarehouseTools::getCleanOfflineList($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 线下清仓工具-导入SKU
     */
    public function actionCleanOfflineImport()
    {
        try {
            return ApiWarehouseTools::cleanOfflineImport();
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * @brief 线下清仓工具-导入SKU模板下载
     */
    public function actionCleanOfflineImportTemplate()
    {
        try {
            $ret = ApiWarehouseTools::cleanOfflineImportTemplate();
            ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Xlsx');
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /**
     * 导出未拣货列表
     * @return array
     */
    public function actionCleanOfflineExportUnPicked()
    {
        try {
            $ret = ApiWarehouseTools::cleanOfflineExportUnPicked();
            ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Xlsx', $title = $ret['title']);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }

    /**
     * 导出错拣货列表
     * @return array
     */
    public function actionCleanOfflineExportWrongPicked()
    {
        try {
            $ret = ApiWarehouseTools::cleanOfflineImportExportWrongPicked();
            ExportTools::toExcelOrCsv($ret['name'], $ret['data'], 'Xlsx');
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }
    }


    /**
     * 扫描逻辑
     * @return mixed
     */
    public function actionCleanOfflineScan()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiWarehouseTools::cleanOfflineScan($condition);
        } catch (\Exception $why) {
            return ['code' => $why->getCode(), 'message' => $why->getMessage()];
        }

    }

    #####################################包裹扫描工具##############################################
    public function actionPackageScanningMember(){
        return ApiWarehouseTools::getWarehouseMember('packageScanning');
    }

    /**
     * @brief 包裹扫描
     * @return array|bool
     */
    public function actionPackageScanning()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::getPackageScanningResult($condition);
    }

    /**
     * 包裹扫描
     * Date: 2021-05-07 14:35
     * Author: henry
     * @return ArrayDataProvider
     * @throws Exception
     */
    public function actionPackageScanningLog()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::getPackageScanningLog($condition);
    }

    /**
     * 包裹扫描
     * Date: 2021-05-07 14:35
     * Author: henry
     * @return ArrayDataProvider
     * @throws Exception
     */
    public function actionPackageScanningStatistics()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::getPackageScanningStatistics($condition);
    }



    ######################################入库工具#########################################

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

    #############################贴标工具#########################################
    /**
     * @brief 贴标人
     * @return array
     */
    public function actionLabelMember()
    {
        return ApiWarehouseTools::getWarehouseMember('label');
    }

    /**
     * 贴标设置
     * Date: 2021-04-22 11:45
     * Author: henry
     * @return array|OauthSysConfig|bool|null
     */
    public function actionLabelSet()
    {
        try {
            $request = Yii::$app->request;
            if($request->isGet){
                return OauthSysConfig::findOne(['name' => OauthSysConfig::LABEL_SET_SKU_NUM]);
            }else{
                $condition = $request->post('condition', []);
                $model = OauthSysConfig::findOne(['name' => OauthSysConfig::LABEL_SET_SKU_NUM]);
                if(!$model){
                    $model = new OauthSysConfig();
                    $model->name = OauthSysConfig::LABEL_SET_SKU_NUM;
                }
                $model->value = $condition['value'];
                $model->memo = $condition['memo'];
                $model->creator = Yii::$app->user->identity->username;
                if($model->save()){
                    return true;
                }else{
                    throw new Exception("Failed to save setting info!");
                }
            }

        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief 贴标扫描
     * @return array | bool
     */
    public function actionLabel()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiWarehouseTools::label($condition);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 明细
     * Date: 2021-04-29 10:36
     * Author: henry
     * @return array|bool
     */
    public function actionLabelDetail()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            return ApiWarehouseTools::getLabelDetail($condition);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 贴标-- 商品难度系数
     * Date: 2021-04-21 17:04
     * Author: henry
     * @return ActiveDataProvider
     */
    public function actionLabelGoodsRate()
    {
        $condition = Yii::$app->request->post('condition', []);
        $pageSize = $condition['pageSize'] ?: 20;
        $data = OauthLabelGoodsRate::find();
        return new ActiveDataProvider([
            'query' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /**
     * 批量导入商品难度系数
     * Date: 2021-04-30 10:21
     * Author: henry
     * @return array
     */
    public function actionImportLabelGoods(){
        $file = $_FILES['file'];

        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if (!in_array($extension , ['.xlsx', '.xls'])) return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' or 'xls'"];

        //文件上传
        $result = ApiSettings::file($file, 'labelGoods');
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        } else {
            //获取上传excel文件的内容并保存
            return ApiWarehouseTools::saveImportLabelGoods($result, $extension);
        }
    }

    /**
     * @brief 贴标-- 商品难度系数保存
     * @return array | bool
     */
    public function actionLabelGoodsSave()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $id = $condition['id'] ?? '';
            $model = OauthLabelGoodsRate::findOne(['id' => $id]);
            if(!$model){
                $model = new OauthLabelGoodsRate();
                $model->creator = Yii::$app->user->identity->username;
            }
            $model->setAttributes($condition);
            if($model->save()){
                return true;
            }else{
                throw new Exception("Failed to save info of '{$condition['goodsCode']}'");
            }
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }

    /**
     * @brief 贴标-- 商品难度系数删除
     * @return array | bool
     */
    public function actionLabelGoodsDelete()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            OauthLabelGoodsRate::deleteAll(['id' => $condition['id']]);
            return true;
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief 贴标统计
     * @return array
     */
    public function actionLabelStatistics()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $personLabelData = ApiWarehouseTools::getLabelStatisticsData($condition);
            $dateLabelData = ApiWarehouseTools::getLabelStatisticsData($condition, 1);
            return [
                'personLabelData' => $personLabelData,
                'dateLabelData' => $dateLabelData,
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Date: 2021-04-20 16:08
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionLabelStatisticsExport(){
        $condition = Yii::$app->request->post()['condition'];
        $personData = ApiWarehouseTools::getLabelStatisticsData($condition);
        $dateData = ApiWarehouseTools::getLabelStatisticsData($condition, 1);
        $data = [
            ['title' => ['贴标人员', '贴标SKU数量'], 'name' => '人员数据', 'data' => $personData],
            ['title' => ['日期', '贴标SKU数量'], 'name' => '时间数据', 'data' => $dateData],
        ];
        ExportTools::toExcelMultipleSheets('labelStatistics', $data, 'Xlsx');
    }

    #############################上架工具#########################################
    /**
     * 上架异常SKU列表
     * Date: 2021-05-10 10:31
     * Author: henry
     * @return array|mixed
     */
    public function actionLoadError()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $pageSize = $condition['pageSize'] ?: 20;
            $data = ApiWarehouseTools::getLoadErrorData($condition);
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['SKU', 'recorder', 'createdDate'],
                    'defaultOrder' => [
                        'createdDate' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 上架异常SKU列表
     * Date: 2021-05-10 10:31
     * Author: henry
     * @return array|bool
     */
    public function actionSaveSkuLoadError()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $query = OauthLoadSkuError::findOne($condition['id']);
            $query = $query ?: new OauthLoadSkuError();
            $query->SKU = $condition['SKU'];
            $query->recorder = Yii::$app->user->identity->username;
            $res = $query->save();
            if(!$res){
                throw new \Exception('Failed save sku info!');
            }
            return true;
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 上架完成度
     * Date: 2021-05-10 10:31
     * Author: henry
     * @return array|mixed
     */
    public function actionLoadRate()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $pageSize = $condition['pageSize'] ?: 20;
            $data = ApiWarehouseTools::getLoadRateData($condition);
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['warehouseMen', 'warehouseDate', 'SKU', 'loadMen', 'loadDate', 'isLoad', 'isError'],
                    'defaultOrder' => [
                        'warehouseDate' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 上架统计列表
     * Date: 2021-05-10 10:31
     * Author: henry
     * @return array|mixed
     */
    public function actionLoadList()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $pageSize = $condition['pageSize'] ?: 100;
            $data = ApiWarehouseTools::getLoadListData($condition);
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt', 'totalNum', 'num', 'rate', 'oneDateNum', 'oneDateRate', 'errorNum', 'problemNum'],
                    'defaultOrder' => [
                        'dt' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }





    /**
     * @brief 上架人
     * @return array
     */
    public function actionLoadMember()
    {
        return ApiWarehouseTools::getWarehouseMember('load');
    }
    /**
     * 上货统计
     * Date: 2021-04-19 10:31
     * Author: henry
     * @return array|mixed
     */
    public function actionLoadingStatistics()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $personLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition);
            $dateLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition, 1);
            return [
                'personLoadingData' => $personLoadingData,
                'dateLoadingData' => $dateLoadingData,
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * actionLoadingPersonDetail
     * Date: 2021-04-19 16:09
     * Author: henry
     * @return array
     */
    public function actionLoadingPersonDetail(){
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiWarehouseTools::getLoadStatisticsDetail($condition);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }

    }

    /**
     * actionLoadingDateDetail
     * Date: 2021-04-19 16:10
     * Author: henry
     * @return array
     */
    public function actionLoadingDateDetail(){
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiWarehouseTools::getLoadStatisticsDetail($condition, 1);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     *
     * Date: 2021-04-20 16:08
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionLoadingStatisticsExport(){
        $condition = Yii::$app->request->post()['condition'];
        $personLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition);
        $dateLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition, 1);
        $data = [
            ['title' => ['日期', '上架数量'], 'name' => '时间数据', 'data' => $dateLoadingData],
            ['title' => ['上架人员', '上架数量'], 'name' => '人员数据', 'data' => $personLoadingData],
        ];
        ExportTools::toExcelMultipleSheets('loadingStatistics', $data, 'Xlsx');
    }


    #############################拣货统计#########################################

    /**
     * 拣货统计
     * Date: 2021-04-15 10:31
     * Author: henry
     * @return array|mixed
     */
    public function actionPickStatistics()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $totalNotPickingNum = ApiWarehouseTools::getNotPickingTradeNum($condition);
            $personPickingData = ApiWarehouseTools::getPickStatisticsData($condition);
            $datePickingData = ApiWarehouseTools::getPickStatisticsData($condition, 1);
            return [
                'totalNotPickingNum' => $totalNotPickingNum,
                'personPickingData' => $personPickingData,
                'datePickingData' => $datePickingData,
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * actionPersonPickingExport
     * Date: 2021-04-15 11:52
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPersonPickingExport(){
        $condition = Yii::$app->request->post()['condition'];
        $personPickingData = ApiWarehouseTools::getPickStatisticsData($condition);
        $title = ['拣货人','单品拣货量','多品拣货量','总拣货量'];
        ExportTools::toExcelOrCsv('PersonPickingData', $personPickingData, 'Xls', $title);
    }
    /**
     * actionDatePickingExport
     * Date: 2021-04-15 11:52
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDatePickingExport(){
        $condition = Yii::$app->request->post()['condition'];
        $datePickingData = ApiWarehouseTools::getPickStatisticsData($condition);
        $title = ['拣货日期','单品拣货量','多品拣货量','总拣货量'];
        ExportTools::toExcelOrCsv('DatePickingData', $datePickingData, 'Xls', $title);
    }

    #############################出库统计#########################################




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
    public function actionDailyDelivery()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $totalNotPickingNum = ApiWarehouseTools::getNotPickingTradeNum($cond);
        $data = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}'")->queryAll();
        $dailyData = $packageData = $pickingData = [];
        foreach ($data as &$v){
            $item = [
                'singleNum' => $v['singleNum'],
                'skuSingleNum' => $v['skuSingleNum'],
                'multiNum' => $v['multiNum'],
                'skuMultiNum' => $v['skuMultiNum'],
                'totalNum' => $v['totalNum'],
                'totalSkuNum' => $v['totalSkuNum'],
            ];
            if($v['flag'] == 'date'){
                $item['dt'] = $v['name'];
                $dailyData[] = $item;
            }elseif ($v['flag'] == 'packageMen'){
                $item['packageMen'] = $v['name'];
                $packageData[] = $item;
            }else{
                $item['packingMen'] = $v['name'];
                $pickingData[] = $item;
            }
        }
        $dailyDataPro = new ArrayDataProvider([
            'allModels' => $dailyData,
            'sort' => [
                'attributes' => ['dt', 'singleNum', 'skuSingleNum', 'multiNum','skuMultiNum', 'totalNum','totalSkuNum'],
                'defaultOrder' => [
                    'dt' => SORT_ASC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        $packageDataPro = new ArrayDataProvider([
            'allModels' => $packageData,
            'sort' => [
                'attributes' => ['packageMen', 'singleNum', 'skuSingleNum', 'multiNum','skuMultiNum', 'totalNum','totalSkuNum'],
                'defaultOrder' => [
                    'singleNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);

        $pickingDataPro = new ArrayDataProvider([
            'allModels' => $pickingData,
            'sort' => [
                'attributes' => ['pickingMen', 'singleNum', 'skuSingleNum', 'multiNum','skuMultiNum', 'totalNum','totalSkuNum'],
                'defaultOrder' => [
                    'singleNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 100,
            ],
        ]);
        return [
            'totalNotPickingNum' => $totalNotPickingNum,
            'dailyData' => $dailyDataPro->getModels(),
            'packageData' => $packageDataPro->getModels(),
            'pickingData' => $pickingDataPro->getModels(),
        ];
    }

    /** 每日发货量导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDailyDeliveryExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $data = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}'")->queryAll();
        $dailyData = [];
        foreach ($data as $v) {
            if ($v['flag'] == 'date') {
                unset($v['flag']);
                $dailyData[] = $v;
            }
        }
        $title = ['发货日期', '单品订单数', '单品SKU数', '多品订单数', '多品SKU数', '总订单数', '总SKU数'];
        ExportTools::toExcelOrCsv('DailyDelivery', $dailyData, 'Xls', $title);
    }

    /** 打包定单量导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPackageOrderExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $data = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}', 1")->queryAll();
        $dailyData = [];
        foreach ($data as $v) {
            if ($v['flag'] == 'packageMen') {
                unset($v['flag']);
                $dailyData[] = $v;
            }
        }
        $title = ['打包人员', '单品订单数', '单品SKU数', '多品订单数', '多品SKU数', '总订单数', '总SKU数'];
        ExportTools::toExcelOrCsv('PackageOrder', $dailyData, 'Xls', $title);
    }

    /** 打包定单量导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPackingOrderExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $store = $cond['store'] ?: '义乌仓';
        $begin = $cond['dateRange'][0] ?? '';
        $end = $cond['dateRange'][1] ?? '';
        $data = Yii::$app->py_db->createCommand("Exec oauth_dailyDelivery '{$begin}','{$end}','{$store}', 1")->queryAll();
        $dailyData = [];
        foreach ($data as $v) {
            if ($v['flag'] == 'packingMen') {
                unset($v['flag']);
                $dailyData[] = $v;
            }
        }
        $title = ['拣货人员', '单品订单数', '单品SKU数', '多品订单数', '多品SKU数', '总订单数', '总SKU数'];
        ExportTools::toExcelOrCsv('PackingOrder', $dailyData, 'Xls', $title);
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
        $skuLocationSql = "SELECT COUNT(DISTINCT LocationName) AS Number 
                            FROM [dbo].[B_StoreLocation](nolock) l
                            WHERE StoreID='{$storeId}' AND nid  IN(SELECT LocationID FROM B_GoodsSKULocation a WHERE StoreID = l.StoreID)";
        $skuLocationNum = Yii::$app->py_db->createCommand($skuLocationSql)->queryScalar();
        //空仓位数
        $emptyLocationSql = "SELECT COUNT(DISTINCT LocationName) AS Number 
                            FROM [dbo].[B_StoreLocation](nolock) l
                            WHERE StoreID='{$storeId}' AND nid NOT IN(SELECT LocationID FROM B_GoodsSKULocation a WHERE StoreID = l.StoreID)";
        $emptyLocationNum = Yii::$app->py_db->createCommand($emptyLocationSql)->queryScalar();
        //无库存仓位数
        $nonStockLocationSql = "SELECT COUNT(DISTINCT LocationName) AS Number 
                            FROM [dbo].[B_StoreLocation](nolock) l
                            INNER JOIN (
                                SELECT gsl.locationID,gsl.StoreID
                                FROM B_GoodsSKULocation(nolock) gsl 
                                INNER JOIN B_GoodsSKU(nolock) gs ON gs.NID=gsl.GoodsSKUID 
                                LEFT JOIN KC_CurrentStock(nolock) cs ON cs.GoodsSKUID=gs.NID AND cs.StoreID=gsl.StoreID
                                -- WHERE isnull(cs.number,0)>=0 
                                GROUP BY gsl.locationID,gsl.StoreID
								HAVING SUM(isnull(cs.number,0))<=0
                            ) aa ON aa.locationID=l.nid AND aa.StoreID=l.StoreID WHERE l.StoreID='{$storeId}' ";
        $nonStockLocationNum = Yii::$app->py_db->createCommand($nonStockLocationSql)->queryScalar();
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
            'nonStockLocationNum' => $nonStockLocationNum,
            'locationSkuNum' => $locationSkuNum,
            'skuLocationRate' => (string)round($locationSkuNum / ($locationNum ?: 1), 2),
        ];

        $sql = "SELECT skuNum, COUNT(LocationName) AS locationNum FROM(
		            SELECT LocationName,sum(skuNum) AS skuNum FROM(
                        SELECT sl.LocationName,isnull(gs.SKU,'') AS sku,cs.number,
                        CASE WHEN isnull(gs.SKU,'')='' OR cs.number <= 0 THEN 0 ELSE 1 END skuNum
                        FROM [dbo].[B_StoreLocation](nolock) sl
                        LEFT JOIN B_GoodsSKULocation(nolock) gsl ON sl.NID=gsl.LocationID AND gsl.StoreID=sl.StoreID
                        LEFT JOIN KC_CurrentStock(nolock) cs ON cs.GoodsSKUID=gsl.GoodsSKUID AND cs.StoreID=gsl.StoreID
						LEFT JOIN B_GoodsSKU(nolock) gs ON gs.NID=cs.GoodsSKUID
                        WHERE sl.StoreID='{$storeId}' 
		            )aa GROUP BY LocationName
                ) bb GROUP BY skuNum ORDER BY skuNum ";
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

    /**
     * 仓位明细
     * actionPositionDetail
     * Date: 2021-02-24 9:01
     * Author: henry
     * @return ArrayDataProvider
     */
    public function actionPositionDetail()
    {
        $cond = Yii::$app->request->post('condition', []);
        $page = \Yii::$app->request->get('page', 1);
        $pageSize = $cond['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionDetails($cond);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['StoreName','LocationName','skuNum', 'stockSkuNum'],
                'defaultOrder' => [
                    'stockSkuNum' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /**
     * 仓位明细-- 主表导出
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionDetails($cond);
        $title = ['仓库', '仓位', 'SKU个数', '有库存SKU个数'];
        ExportTools::toExcelOrCsv('positionDetail', $data, 'Xls', $title);

    }

    /**
     * 仓位明细-- 查看明细
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailView()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionDetailsView($cond);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName', 'goodsskustatus', 'number', 'devDate', 'hasPurchaseOrder','reservationNum'],
                'defaultOrder' => [
                    'number' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 10000,
            ],
        ]);

    }

    /**
     * 仓位明细-- 明细导出
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
        $title = ['仓库', '仓位', 'SKU个数', 'SKU', 'SKU名称', 'SKU状态', '库存数量', '开发日期', '占用数量', '是否有采购单'];
        ExportTools::toExcelOrCsv('positionDetailView', $data, 'Xlsx', $title);

    }

    /**
     * 仓位明细-- 明细导出(按是否有采购单 分表)
     * actionPositionDetail
     * Date: 2021-02-23 13:33
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionPositionDetailViewSheetsExport()
    {
        $cond = Yii::$app->request->post('condition', []);
        $data = ApiWarehouseTools::getPositionDetailsView($cond);
        $includedData = $notIncludedData = $emptyStockData = [];
        foreach ($data as $k => $v) {
            if ($v['number'] > 0){
                if ($v['hasPurchaseOrder'] == '是' || $v['reservationNum'] > 0) {
                    $includedData[] = $v;
                } else{
                    $notIncludedData[] = $v;
                }
            }else{
                $emptyStockData[] = $v;
            }

        }
        $title = ['仓库', '仓位', '有库存SKU个数', 'SKU', 'SKU名称', 'SKU状态', '库存数量', '开发日期', '占用数量', '是否有采购单'];
        $data = [
            ['title' => $title, 'name' => 'SKU有采购单或有占用数据', 'data' => $includedData],
            ['title' => $title, 'name' => 'SKU无采购单且无占用数据', 'data' => $notIncludedData],
            ['title' => $title, 'name' => 'SKU库存为0数据', 'data' => $emptyStockData],
        ];
//        return $data;
        ExportTools::toExcelMultipleSheets('positionDetailView', $data, 'Xlsx');

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
        $pageSize = $cond['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionSearchData($cond);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName', 'goodsSkuStatus', 'Number', 'devDate'],
                'defaultOrder' => [
                    'Number' => SORT_DESC,
                ]
            ],
            'pagination' => [
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
        $title = ['仓库', '仓位', 'SKU', 'SKU名称', 'SKU状态', '库存数量', '开发日期'];
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
        $pageSize = $cond['pageSize'] ?? 20;
        $data = ApiWarehouseTools::getPositionManageData($cond);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['sku', 'skuName', 'goodsSkuStatus', 'number', 'devDate'],
                'defaultOrder' => [
                    'devDate' => SORT_DESC,
                ]
            ],
            'pagination' => [
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
        $title = ['仓库', '仓位', 'SKU', 'SKU名称', 'SKU状态', '库存数量', '开发日期'];
        ExportTools::toExcelOrCsv('positionManage', $data, 'Xlsx', $title);
    }

    public function actionPositionSkuDelete()
    {
        $condition = Yii::$app->request->post('condition', []);
        try {
            $res = ApiWarehouseTools::positionSkuDelete($condition);
            if ($res) {
                return [
                    'code' => 400,
                    'message' => 'error',
                    'data' => $res,
                ];
            }
            return true;
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

    }




    /////////////////////////////////每日差异单比例////////////////////////////////////

    /**
     * 每日差异单比例
     * Date: 2021-03-18 16:15
     * Author: henry
     * @return array
     */
    public function actionDifferenceOrderRate(){
        try {
            $condition = Yii::$app->request->post('condition', []);
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_difference_order_rate '{$storeName}','{$beginDate}','{$endDate}'";
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /////////////////////////////////入库时效////////////////////////////////////

    /**
     * 入库时效
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStorageTimeRate(){
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ? : 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_in_storage_time_rate '{$beginDate}','{$endDate}','{$storeName}'";
            $data =  Yii::$app->py_db->createCommand($sql)->queryAll();
            $totalNum = array_sum(ArrayHelper::getColumn($data,'totalNum'));
            $totalInNum = array_sum(ArrayHelper::getColumn($data,'inNum'));
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['storeName', 'dt', 'totalNum','inNum','notInNum','notInRate','num','rate',
                        'oneNum','oneRate','twoNum','twoRate','threeNum','threeRate','otherNum','otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return [
                'provider' => $provider,
                'extra' => [
                    'totalNum' => $totalNum,
                    'totalInNum' => $totalInNum,
                ]
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 入库时效2.0
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStorageTimeRate2(){
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ? : 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_in_storage_time_rate '{$beginDate}','{$endDate}','{$storeName}','2.0'";
            $data =  Yii::$app->py_db->createCommand($sql)->queryAll();
            $totalNum = array_sum(ArrayHelper::getColumn($data,'totalNum'));
            $totalInNum = array_sum(ArrayHelper::getColumn($data,'inNum'));
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['storeName', 'dt', 'totalNum','inNum','notInNum','notInRate','num','rate',
                        'oneNum','oneRate','twoNum','twoRate','threeNum','threeRate','otherNum','otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return [
                'provider' => $provider,
                'extra' => [
                    'totalNum' => $totalNum,
                    'totalInNum' => $totalInNum,
                ]
            ];
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * 发货时效
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDeliverTimeRate(){
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ? : 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}'";
            $data =  Yii::$app->py_db->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt','storeName', 'totalNum', 'deliverableNum','zeroNum','zeroRate',
                        'oneNum','oneRate','twoNum','twoRate','threeNum','threeRate','otherNum','otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 发货时效2.0
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDeliverTimeRate2(){
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ? : 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}','2.0'";
            $data =  Yii::$app->py_db->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt','storeName', 'totalNum', 'deliverableNum','zeroNum','zeroRate',
                        'oneNum','oneRate','twoNum','twoRate','threeNum','threeRate','otherNum','otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        }catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }





}
