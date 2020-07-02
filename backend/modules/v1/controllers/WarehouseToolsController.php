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
        }
        catch (\Exception $why) {
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
        }
        catch (\Exception $why) {
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
        $title = ['SKU','仓库','库位','操作人','类型','操作时间'];
        $dataProvider = ApiWarehouseTools::getWareSkuData($condition);
        $data = $dataProvider->getModels();
        if($data){
            ExportTools::toExcelOrCsv('WareSkuExport', $data, 'Xls', $title);
        }
    }



    public function actionIntegral(){
        $month = date('Y-m', strtotime('-1 days'));
        $con = Yii::$app->request->post('condition');
        $month = isset($con['month']) && $con['month'] ? $con['month'] : $month;
        $sql = "SELECT * FROM warehouse_integral_report WHERE month = '{$month}'";
        if(isset($con['group']) && $con['group']) $sql .= " AND `group`='{$con['group']}'";
        if(isset($con['job']) && $con['job']) $sql .= " AND job='{$con['job']}'";
        if(isset($con['team']) && $con['team']) $sql .= " AND team='{$con['team']}'";
        if(isset($con['name']) && $con['name']) $sql .= " AND name='{$con['name']}'";
        return Yii::$app->db->createCommand($sql)->queryAll();
    }

    public function actionQueryInfo(){
        $type = Yii::$app->request->get('type','job');
        if(!in_array($type, ['job','name','group'])) {
            return [
                'code' => 400,
                'message' => 'type is not correct value!',
            ];
        }
        try{
            $sql = "SELECT DISTINCT `{$type}` FROM warehouse_intergral_other_data_every_month";
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            return ArrayHelper::getColumn($query, $type);
        }catch (Exception $why){
            return [
                'code' => 400,
                'message' => $why->getMessage(),
            ];
        }
    }








}
