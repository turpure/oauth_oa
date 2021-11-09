<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 9:50
 */

namespace backend\modules\v1\controllers;


use backend\models\ShopElf\BGoods;
use backend\models\ShopElf\OauthLoadSkuError;
use backend\models\ShopElf\OauthSysConfig;
use backend\models\ShopElf\OauthLabelGoodsRate;
use backend\models\ShopElf\TaskWarehouse;
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

    ######################################多品分拣#########################################

    /**
     * @brief 分拣人
     * @return array
     */
    public function actionSortMember()
    {
//        $identity = Yii::$app->request->get('type', 'sort');
//        return ApiWarehouseTools::getSortMember($identity);
        return ApiWarehouseTools::getWarehouseMember('sort');
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
     * 分货扫描统计
     * Date: 2021-09-23 14:54
     * Author: henry
     * @return array
     * @throws Exception
     */
    public function actionSortStatistics()
    {
        $condition = Yii::$app->request->post()['condition'];
        list($totalData, $detail) = ApiWarehouseTools::getSortScanningStatistics($condition);
        $provider = new ArrayDataProvider([
            'allModels' => $detail,
            'sort' => [
                'attributes' => ['dt', 'username', 'batchNumber', 'tradeNum', 'skuNum', 'goodsNum'],
                'defaultOrder' => [
                    'dt' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 10000,
            ],
        ]);
        return ['totalData' => $totalData, 'detail' => $provider->getModels()];
    }

    /**
     * 分拣统计导出
     * Date: 2021-09-23 15:04
     * Author: henry
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionSortStatisticsExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        list($totalData, $detail) = ApiWarehouseTools::getSortScanningStatistics($condition);
        $res = [
            ['title' => ['分拣人员', '批次数量', '订单数量', 'SKU数量', '产品数量'], 'name' => '分拣人员数据汇总', 'data' => $totalData],
            ['title' => ['日期', '分拣人员', '批次号', '订单数量', 'SKU数量', '产品数量'], 'name' => '分拣人员数据明细', 'data' => $detail],
        ];
        ExportTools::toExcelMultipleSheets('labelStatistics', $res, 'Xlsx');
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
    public function actionPackageScanningMember()
    {
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
     * @brief 包裹删除
     * @return array|bool
     */
    public function actionPackageDelete()
    {
        $condition = Yii::$app->request->post('condition');
        return ApiWarehouseTools::packageDelete($condition);
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
     * 包裹扫描统计
     * Date: 2021-05-07 14:35
     * Author: henry
     * @return array
     * @throws Exception
     */
    public function actionPackageScanningStatistics()
    {
        $condition = Yii::$app->request->post('condition');
        $data = ApiWarehouseTools::getPackageScanningStatistics($condition);
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['dt', 'username', 'scanNum', 'outOfStockNum', 'num', 'errorNum'],
                'defaultOrder' => [
                    'dt' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => 1000,
            ],
        ]);
        return $provider->getModels();
    }


    ######################################入库工具#########################################
    public function actionWarehousingMember()
    {
        return ApiWarehouseTools::getWarehouseMember('warehousing');
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


    public function actionWarehouseLogDel()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $id = $condition['id'] ?? 0;
            TaskWarehouse::deleteAll(['id' => $id]);
            return true;
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }

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
     * @brief 打标统计
     * @return array
     */
    public function actionMarkStatistics()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            $data = ApiWarehouseTools::getLabelStatisticsData($condition, 1);
            $personLabelData = $dateLabelData = [];
            foreach ($data as $v) {
                $item = $v;
                unset($item['flag']);
                if ($v['flag'] == 'time') {
                    $dateLabelData[] = $item;
                } else {
                    $personLabelData[] = $item;
                }
            }
            return [
                'personLabelData' => $personLabelData,
                'dateLabelData' => $dateLabelData,
            ];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 打标统计导出
     * Date: 2021-04-20 16:08
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionMarkStatisticsExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiWarehouseTools::getLabelStatisticsData($condition, 1);
        $personData = $dateData = [];
        foreach ($data as $v) {
            $item = $v;
            unset($item['flag']);
            if ($v['flag'] == 'time') {
                $dateData[] = $item;
            } else {
                $personData[] = $item;
            }
        }
        $res = [
            ['title' => ['打标人员', '打标SKU数量', '打标SKU种数', '打标包裹数'], 'name' => '人员数据', 'data' => $personData],
            ['title' => ['日期', '打标SKU数量', '打标SKU种数', '打标包裹数'], 'name' => '时间数据', 'data' => $dateData],
        ];
        ExportTools::toExcelMultipleSheets('markStatistics', $res, 'Xlsx');
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
            if ($request->isGet) {
                return OauthSysConfig::findOne(['name' => OauthSysConfig::LABEL_SET_SKU_NUM]);
            } else {
                $condition = $request->post('condition', []);
                $model = OauthSysConfig::findOne(['name' => OauthSysConfig::LABEL_SET_SKU_NUM]);
                if (!$model) {
                    $model = new OauthSysConfig();
                    $model->name = OauthSysConfig::LABEL_SET_SKU_NUM;
                }
                $model->value = $condition['value'];
                $model->memo = $condition['memo'];
                $model->creator = Yii::$app->user->identity->username;
                if ($model->save()) {
                    return true;
                } else {
                    throw new Exception("Failed to save setting info!");
                }
            }

        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
     * @return ArrayDataProvider
     */
    public function actionLabelGoodsRate()
    {
        $condition = Yii::$app->request->post('condition', []);
        $pageSize = $condition['pageSize'] ?: 20;
        $goodsCode = $condition['goodsCode'] ?? '';
        $purchaser = $condition['purchaser'] ?? '';
        $rate = isset($condition['rate']) && $condition['rate'] ? $condition['rate'] : 0;
//        var_dump($rate);exit();
        //$data = OauthLabelGoodsRate::find()->andFilterWhere(['like', 'goodsCode', $goodsCode]);
        $sql = "SELECT a.id,a.goodsCode,a.rate,g.purchaser FROM oauth_label_goods_rate a
                LEFT JOIN B_Goods g ON a.goodsCode=g.GoodsCode WHERE 1=1 ";
        if ($goodsCode) $sql .= " AND a.goodsCode LIKE '%{$goodsCode}%'";
        if ($purchaser) $sql .= " AND g.purchaser LIKE '%{$purchaser}%'";
        if ($rate) $sql .= " AND a.rate = '{$rate}'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['goodsCode', 'rate', 'purchaser'],
                'defaultOrder' => [
                    'rate' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
    }

    /**
     * 贴标-- 商品难度系数 导出
     * Date: 2021-07-08 11:48
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionLabelGoodsRateExport()
    {
        $condition = Yii::$app->request->post('condition', []);
        $goodsCode = $condition['goodsCode'] ?? '';
        $purchaser = $condition['purchaser'] ?? '';
        $rate = isset($condition['rate']) && $condition['rate'] ? $condition['rate'] : 0;
        //$data = OauthLabelGoodsRate::find()->andFilterWhere(['like', 'goodsCode', $goodsCode]);
        $sql = "SELECT a.goodsCode,a.rate,g.purchaser FROM oauth_label_goods_rate a
                LEFT JOIN B_Goods g ON a.goodsCode=g.GoodsCode WHERE 1=1 ";
        if ($goodsCode) $sql .= " AND a.goodsCode LIKE '%{$goodsCode}%'";
        if ($purchaser) $sql .= " AND g.purchaser LIKE '%{$purchaser}%'";
        if ($rate) $sql .= " AND a.rate = '{$rate}'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $title = ['商品编码', '困难系数', '采购员'];
        ExportTools::toExcelOrCsv('labelGoodsRate', $data, 'Xlsx', $title);
    }

    /**
     * 批量导入商品难度系数
     * Date: 2021-04-30 10:21
     * Author: henry
     * @return array
     */
    public function actionImportLabelGoods()
    {
        $file = $_FILES['file'];

        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = ApiSettings::get_extension($file['name']);
        if (!in_array($extension, ['.xlsx', '.xls'])) return ['code' => 400, 'message' => "File format error,please upload files in 'xlsx' or 'xls'"];

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
            if (!$model) {
                $model = new OauthLabelGoodsRate();
                $model->creator = Yii::$app->user->identity->username;
            }
            $model->setAttributes($condition);
            if ($model->save()) {
                BGoods::updateAll(['PackingRatio' => $model->rate], ['GoodsCode' => $model->goodsCode]);
                return true;
            } else {
                throw new Exception("Failed to save info of '{$condition['goodsCode']}'");
            }
        } catch (Exception $e) {
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
            $model = OauthLabelGoodsRate::findOne($condition['id']);
            BGoods::updateAll(['PackingRatio' => null], ['GoodsCode' => $model->goodsCode]);
            //OauthLabelGoodsRate::deleteAll(['id' => $condition['id']]);
            $model->delete();
            return true;
        } catch (Exception $e) {
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
            $data = ApiWarehouseTools::getLabelStatisticsData($condition);
            $personData = $dateData = [];
            foreach ($data as $v) {
                $item = $v;
                unset($item['flag']);
                if ($v['flag'] == 'time') {
                    unset($item['job'], $item['rate']);
                    $dateData[] = $item;
                } else {
                    $personData[] = $item;
                }
            }
            return [
                'personLabelData' => $personData,
                'dateLabelData' => $dateData,
            ];
        } catch (Exception $e) {
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
    public function actionLabelStatisticsExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiWarehouseTools::getLabelStatisticsData($condition);
        $personData = $dateData = [];
        foreach ($data as $v) {
            $item = $v;
            unset($item['flag']);
            if ($v['flag'] == 'time') {
                unset($item['job'], $item['rate']);
                $dateData[] = $item;
            } else {
                $personData[] = $item;
            }
        }
        $res = [
            ['title' => ['贴标人员', '职位', '困难系数', '贴标SKU数量', '贴标SKU种数'], 'name' => '人员数据', 'data' => $personData],
            ['title' => ['日期', '贴标SKU数量', '贴标SKU种数'], 'name' => '时间数据', 'data' => $dateData],
        ];
        ExportTools::toExcelMultipleSheets('labelStatistics', $res, 'Xlsx');
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
        } catch (Exception $e) {
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
            if (!$res) {
                throw new \Exception('Failed save sku info!');
            }
            return true;
        } catch (Exception $e) {
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
                    'attributes' => ['warehouseMen', 'warehouseDate', 'SKU', 'loadMen', 'loadDate', 'isLoad', 'isError', 'isNew'],
                    'defaultOrder' => [
                        'warehouseDate' => SORT_DESC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        } catch (Exception $e) {
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
    public function actionLoadRateExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $data = ApiWarehouseTools::getLoadRateData($condition);
        $title = ['入库人', '入库时间', 'SKU', '上架人', '上架时间', '上架完成', '是否异常', '是否新品'];
        ExportTools::toExcelOrCsv('storageTimeRate', $data, 'Xls', $title);
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
        } catch (Exception $e) {
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
        } catch (Exception $e) {
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
    public function actionLoadingPersonDetail()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiWarehouseTools::getLoadStatisticsDetail($condition);
        } catch (Exception $e) {
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
    public function actionLoadingDateDetail()
    {
        try {
            $condition = Yii::$app->request->post()['condition'];
            return ApiWarehouseTools::getLoadStatisticsDetail($condition, 1);
        } catch (Exception $e) {
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
    public function actionLoadingStatisticsExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $personLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition);
        $dateLoadingData = ApiWarehouseTools::getLoadStatisticsData($condition, 1);
        $data = [
            ['title' => ['日期', '上架数量'], 'name' => '时间数据', 'data' => $personLoadingData],
            ['title' => ['上架人员', '上架数量'], 'name' => '人员数据', 'data' => $dateLoadingData],
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
        } catch (Exception $e) {
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
    public function actionPersonPickingExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $personPickingData = ApiWarehouseTools::getPickStatisticsData($condition);
        $title = ['拣货人', '单品拣货量', '多品拣货量', '总拣货量', '单品订单数', '多品订单数', '总订单数'];
        ExportTools::toExcelOrCsv('PersonPickingData', $personPickingData, 'Xls', $title);
    }

    /**
     * actionDatePickingExport
     * Date: 2021-04-15 11:52
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDatePickingExport()
    {
        $condition = Yii::$app->request->post()['condition'];
        $datePickingData = ApiWarehouseTools::getPickStatisticsData($condition);
        $title = ['拣货日期', '单品拣货量', '多品拣货量', '总拣货量'];
        ExportTools::toExcelOrCsv('DatePickingData', $datePickingData, 'Xls', $title);
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

    /**
     * actionIntegralEveryDay
     * Date: 2021-11-08 18:08
     * Author: henry
     * @return ArrayDataProvider
     * @throws Exception
     */
    public function actionIntegralEveryDay()
    {
        $con = Yii::$app->request->post('condition');
        $pageSize = isset($con['pageSize']) && $con['pageSize'] ? $con['pageSize'] : 20;
        $data = ApiWarehouseTools::getIntegralEveryDay($con);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ["name", "dt", "total_integral", "wages", "all_days", "labeling_days", "sorting_days",
                    "inbound_sorting_days", "group", "job", "team", "pur_in_package_num", "marking_in_storage_package_num",
                    "marking_in_storage_num", "labeling_in_storage_num", "labeling_in_storage_num2", "labeling_in_storage_num3",
                    "labeling_in_storage_num4", "labeling_in_storage_num5", "labeling_in_storage_num6", "labeling_in_storage_num7",
                    "labeling_in_storage_num8", "labeling_in_storage_num9", "pda_in_storage_sku_num", "inbound_pda_in_storage_sku_num",
                    "picking_single_sku_num", "picking_multi_sku_num", "picking_total_num", "multi_sorting_sku_num",
                    "pack_single_package_num", "pack_multi_package_num", "package_num", "unpacking_integral", "marking_integral",
                    "labeling_integral", "on_shelf_integral", "nbound_sorting_integral", "picking_integral", "multi_sorting_integral",
                    "packing_integral", "sorting_integral", "other_integral", "deduction_integral", "update_date"],
                'defaultOrder' => [
                    'dt' => SORT_DESC,
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

    }

    /**
     * actionIntegralEveryDayExport
     * Date: 2021-11-08 18:17
     * Author: henry
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionIntegralEveryDayExport()
    {
        $con = Yii::$app->request->post('condition');
        $data = ApiWarehouseTools::getIntegralEveryDay($con);
        $res = [];
        foreach ($data as $v) {
            $item["姓名"] = $v["name"];
            $item["日期"] = $v["dt"];
            $item["总积分"] = $v["total_integral"];
            $item["计件工资"] = $v["wages"];
            $item["出勤天数"] = $v["all_days"];
            $item["贴标出勤天数"] = $v["labeling_days"];
            $item["分拣出勤天数"] = $v["sorting_days"];
            $item["入库分拣天数"] = $v["inbound_sorting_days"];
            $item["组别"] = $v["group"];
            $item["职位"] = $v["job"];
            $item["小组"] = $v["team"];
            $item["采购入库包裹"] = $v["pur_in_package_num"];
            $item["打标入库SKU种数"] = $v["marking_in_storage_package_num"];
            $item["打标入库数量"] = $v["marking_in_storage_num"];
            $item["贴标入库数量(系数1)"] = $v["labeling_in_storage_num"];
            $item["贴标入库数量(系数2)"] = $v["labeling_in_storage_num2"];
            $item["贴标入库数量(系数3)"] = $v["labeling_in_storage_num3"];
            $item["贴标入库数量(系数4)"] = $v["labeling_in_storage_num4"];
            $item["贴标入库数量(系数5)"] = $v["labeling_in_storage_num5"];
            $item["贴标入库数量(系数6)"] = $v["labeling_in_storage_num6"];
            $item["贴标入库数量(系数7)"] = $v["labeling_in_storage_num7"];
            $item["贴标入库数量(系数8)"] = $v["labeling_in_storage_num8"];
            $item["贴标入库数量(系数9)"] = $v["labeling_in_storage_num9"];
            $item["PDA入库SKU数"] = $v["pda_in_storage_sku_num"];
            $item["入库SKU数"] = $v["inbound_pda_in_storage_sku_num"];
            $item["单品拣货SKU种数"] = $v["picking_single_sku_num"];
            $item["多品拣货SKU种数"] = $v["picking_multi_sku_num"];
            $item["拣货总数量"] = $v["picking_total_num"];
            $item["多品分拣总数量"] = $v["multi_sorting_sku_num"];
            $item["打包单品包裹数"] = $v["pack_single_package_num"];
            $item["打包核单包裹数"] = $v["pack_multi_package_num"];
            $item["分拣包裹数"] = $v["package_num"];
            $item["拆包积分"] = $v["unpacking_integral"];
            $item["打标积分"] = $v["marking_integral"];
            $item["贴标积分"] = $v["labeling_integral"];
            $item["上架积分"] = $v["on_shelf_integral"];
            $item["入库分拣积分"] = $v["inbound_sorting_integral"];
            $item["拣货积分"] = $v["picking_integral"];
            $item["多品分拣积分"] = $v["multi_sorting_integral"];
            $item["打包积分"] = $v["packing_integral"];
            $item["分拣积分"] = $v["sorting_integral"];
            $item["其它得分项"] = $v["other_integral"];
            $item["扣分项"] = $v["deduction_integral"];
            $item["统计截止时间"] = $v["update_date"];
            $res[] = $item;
        }
        $res = [
            ['测试A' => 1,'测试B' => 1,'测试C' => 1,'测试D' => 1,'测试E' => 1,'测试F' => 1],
            ['测试A' => 2,'测试B' => 12,'测试C' => 1,'测试D' => 1,'测试E' => 1,'测试F' => 1],
            ['测试A' => 3,'测试B' => 13,'测试C' => 1,'测试D' => 1,'测试E' => 1,'测试F' => 1],
            ['测试A' => 4,'测试B' => 14,'测试C' => 1,'测试D' => 1,'测试E' => 1,'测试F' => 1],
            ['测试A' => 5,'测试B' => 15,'测试C' => 1,'测试D' => 1,'测试E' => 1,'测试F' => 1],

        ];
//        var_dump($item);exit;
        ExportTools::toExcelOrCsv('WarehouseIntegralEveryDay', $res, 'Xls');
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
        foreach ($data as &$v) {
            $item = [
                'singleNum' => $v['singleNum'],
                'skuSingleNum' => $v['skuSingleNum'],
                'multiNum' => $v['multiNum'],
                'skuMultiNum' => $v['skuMultiNum'],
                'totalNum' => $v['totalNum'],
                'totalSkuNum' => $v['totalSkuNum'],
                'skuTypeNum' => $v['skuTypeNum'],
            ];
            if ($v['flag'] == 'date') {
                $item['dt'] = $v['name'];
                $dailyData[] = $item;
            } elseif ($v['flag'] == 'packageMen') {
                $item['packageMen'] = $v['name'];
                $packageData[] = $item;
            } else {
                $item['packingMen'] = $v['name'];
                $pickingData[] = $item;
            }
        }
        $dailyDataPro = new ArrayDataProvider([
            'allModels' => $dailyData,
            'sort' => [
                'attributes' => ['dt', 'singleNum', 'skuSingleNum', 'multiNum', 'skuMultiNum', 'totalNum', 'totalSkuNum'],
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
                'attributes' => ['packageMen', 'singleNum', 'skuSingleNum', 'multiNum', 'skuMultiNum', 'totalNum', 'totalSkuNum'],
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
                'attributes' => ['pickingMen', 'singleNum', 'skuSingleNum', 'multiNum', 'skuMultiNum', 'totalNum', 'totalSkuNum'],
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
                'attributes' => ['StoreName', 'LocationName', 'skuNum', 'stockSkuNum'],
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
                'attributes' => ['sku', 'skuName', 'goodsskustatus', 'number', 'devDate', 'hasPurchaseOrder', 'reservationNum'],
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
            if ($v['number'] > 0) {
                if ($v['hasPurchaseOrder'] == '是' || $v['reservationNum'] > 0) {
                    $includedData[] = $v;
                } else {
                    $notIncludedData[] = $v;
                }
            } else {
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
                'attributes' => ['sku', 'LocationName', 'skuName', 'goodsSkuStatus', 'Number', 'devDate'],
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
    public function actionDifferenceOrderRate()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_difference_order_rate '{$storeName}','{$beginDate}','{$endDate}'";
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 每日差异单比例导出
     * Date: 2021-07-15 9:24
     * Author: henry
     * @return array
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDifferenceOrderRateExport()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_difference_order_rate '{$storeName}','{$beginDate}','{$endDate}'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $title = ['仓库', '发货时间', '拣货SKU种数', '异常SKU种数', '异常比例'];
            ExportTools::toExcelOrCsv('differenceOrderRate', $data, 'Xls', $title);
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /////////////////////////////////入库时效////////////////////////////////////

    /**
     * 入库时效详情
     * Date: 2021-07-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStorageTimeRateDetail()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $beginDate = $condition['dt'] ?: '';
            $endDate = $beginDate . " 23:59:59";
            $flag = $condition['flag'] ?: 0;  //-- 0  当天 ， 1 一天内 ， 2 两天内 ，3 三天内 ， 4 三天以上 ， 5 未入库
            $version = $condition['version'] ?: '1.0';
//            $sql = "EXEC oauth_warehouse_tools_in_storage_time_rate_detail '{$beginDate}', {$flag} , '{$version}' ";
            if ($flag == 5 || $flag == 4) {
                $sql = "SELECT * FROM oauth_in_storage_time_rate_data_copy
                    WHERE CASE WHEN '{$version}' = '1.0' THEN CONVERT(VARCHAR(10),OPDate,121)
					    ELSE CONVERT(VARCHAR(10),DATEADD(hh, 9, OPDate),121) END BETWEEN '{$beginDate}' AND '{$endDate}'
					AND ISNULL(audieDate,'') = '' ";
            } else {
                $sql = "SELECT * FROM oauth_in_storage_time_rate_data_copy
                    WHERE CASE WHEN '{$version}' = '1.0' THEN CONVERT(VARCHAR(10),OPDate,121)
					    ELSE CONVERT(VARCHAR(10),DATEADD(hh, 9, OPDate),121) END BETWEEN '{$beginDate}' AND '{$endDate}'
					AND trackingNo NOT IN (
                        SELECT DISTINCT trackingNo FROM oauth_in_storage_time_rate_data_copy 
                        WHERE CASE WHEN '{$version}' = '1.0' THEN CONVERT(VARCHAR(10),OPDate,121)
					        ELSE CONVERT(VARCHAR(10),DATEADD(hh, 9, OPDate),121) END BETWEEN '{$beginDate}' AND '{$endDate}'
						AND CONVERT(VARCHAR(10),audieDate,121) BETWEEN CONVERT(VARCHAR(10),DATEADD(dd, -10, '{$beginDate}'),121) 
						AND CONVERT(VARCHAR(10),DATEADD(dd, {$flag}, '{$beginDate}'),121)    
					) ";
            }
            return Yii::$app->py_db->createCommand($sql)->queryAll();
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 入库时效
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStorageTimeRate()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ?: 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            /*$sql = "SELECT storeName, dt, totalNum, num, rate,oneNum, oneRate, twoNum, twoRate,
                        threeNum, threeRate, otherNum, otherRate, notInNum, notInRate FROM oauth_in_storage_time_rate_data
                    WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' AND flag = '1.0' ";
            if ($storeName) $sql .= " AND storeName = '{$storeName}' ";*/
            $sql = "oauth_warehouse_tools_in_storage_time_rate_jisuan '{$beginDate}','{$endDate}','{$storeName}','1.0'";

            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $totalNum = array_sum(ArrayHelper::getColumn($data, 'totalNum'));
            $notInNum = array_sum(ArrayHelper::getColumn($data, 'notInNum'));
            $total['totalNum'] = $totalNum;
            $total['totalInNum'] = $totalNum - $notInNum;
            $total['num'] = array_sum(ArrayHelper::getColumn($data, 'num'));
            $total['rate'] = $totalNum ? round($total['num'] * 100.0 / $totalNum, 2) : 0;
            $total['oneNum'] = array_sum(ArrayHelper::getColumn($data, 'oneNum'));
            $total['oneRate'] = $totalNum ? round($total['oneNum'] * 100.0 / $totalNum, 2) : 0;
            $total['twoNum'] = array_sum(ArrayHelper::getColumn($data, 'twoNum'));
            $total['twoRate'] = $totalNum ? round($total['twoNum'] * 100.0 / $totalNum, 2) : 0;
            $total['threeNum'] = array_sum(ArrayHelper::getColumn($data, 'threeNum'));
            $total['threeRate'] = $totalNum ? round($total['threeNum'] * 100.0 / $totalNum, 2) : 0;
            $total['otherNum'] = array_sum(ArrayHelper::getColumn($data, 'otherNum'));
            $total['otherRate'] = $totalNum ? round($total['otherNum'] * 100.0 / $totalNum, 2) : 0;
            $total['notInNum'] = $notInNum;
            $total['notInRate'] = $totalNum ? round($notInNum * 100.0 / $totalNum, 2) : 0;
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['storeName', 'dt', 'totalNum', 'notInNum', 'notInRate', 'num', 'rate',
                        'oneNum', 'oneRate', 'twoNum', 'twoRate', 'threeNum', 'threeRate', 'otherNum', 'otherRate'],
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
                'extra' => $total
            ];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 入库时效导出
     * Date: 2021-06-09 9:30
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionStorageTimeRateExport()
    {
        $condition = Yii::$app->request->post('condition', []);
        $storeName = $condition['storeName'] ?: '';
        $beginDate = $condition['dateRange'][0] ?: '';
        $endDate = $condition['dateRange'][1] ?: '';
        /*$sql = "SELECT storeName, dt, totalNum, num, rate, oneNum, oneRate, twoNum, twoRate,
                        threeNum, threeRate, otherNum, otherRate, notInNum, notInRate FROM oauth_in_storage_time_rate_data
                    WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' AND flag = '1.0' ";
        if ($storeName) $sql .= " AND storeName = '{$storeName}' ";*/
        $sql = "oauth_warehouse_tools_in_storage_time_rate_jisuan '{$beginDate}','{$endDate}','{$storeName}','1.0'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        foreach ($data as &$v) {
            $v['rate'] = round($v['rate'] * 100, 2) . '%';
            $v['oneRate'] = round($v['oneRate'] * 100, 2) . '%';
            $v['twoRate'] = round($v['twoRate'] * 100, 2) . '%';
            $v['threeRate'] = round($v['threeRate'] * 100, 2) . '%';
            $v['otherRate'] = round($v['otherRate'] * 100, 2) . '%';
            $v['notInRate'] = round($v['notInRate'] * 100, 2) . '%';
        }
        $title = ['仓库', '扫描日期', '扫描数量', '当天入库数', '当天入库率', '1天内入库数', '1天内入库率',
            '2天内入库数', '2天内入库率', '3天内入库数', '3天内入库率', '3天以上入库数', '3天以上入库率', '未入库数', '未入库率'
        ];
        ExportTools::toExcelOrCsv('storageTimeRate', $data, 'Xls', $title);
    }

    /**
     * 入库时效2.0
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionStorageTimeRate2()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ?: 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            /*$sql = "SELECT storeName, dt, totalNum, num, rate,oneNum, oneRate, twoNum, twoRate,
                        threeNum, threeRate, otherNum, otherRate, notInNum, notInRate FROM oauth_in_storage_time_rate_data
                    WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' AND flag = '2.0' ";
            if ($storeName) $sql .= " AND storeName = '{$storeName}' ";*/
            $sql = "oauth_warehouse_tools_in_storage_time_rate_jisuan '{$beginDate}','{$endDate}','{$storeName}','2.0'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $totalNum = array_sum(ArrayHelper::getColumn($data, 'totalNum'));
            $notInNum = array_sum(ArrayHelper::getColumn($data, 'notInNum'));
            $total['totalNum'] = $totalNum;
            $total['totalInNum'] = $totalNum - $notInNum;
            $total['num'] = array_sum(ArrayHelper::getColumn($data, 'num'));
            $total['rate'] = $totalNum ? round($total['num'] * 100.0 / $totalNum, 2) : 0;
            $total['oneNum'] = array_sum(ArrayHelper::getColumn($data, 'oneNum'));
            $total['oneRate'] = $totalNum ? round($total['oneNum'] * 100.0 / $totalNum, 2) : 0;
            $total['twoNum'] = array_sum(ArrayHelper::getColumn($data, 'twoNum'));
            $total['twoRate'] = $totalNum ? round($total['twoNum'] * 100.0 / $totalNum, 2) : 0;
            $total['threeNum'] = array_sum(ArrayHelper::getColumn($data, 'threeNum'));
            $total['threeRate'] = $totalNum ? round($total['threeNum'] * 100.0 / $totalNum, 2) : 0;
            $total['otherNum'] = array_sum(ArrayHelper::getColumn($data, 'otherNum'));
            $total['otherRate'] = $totalNum ? round($total['otherNum'] * 100.0 / $totalNum, 2) : 0;
            $total['notInNum'] = $notInNum;
            $total['notInRate'] = $totalNum ? round($notInNum * 100.0 / $totalNum, 2) : 0;
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['storeName', 'dt', 'totalNum', 'notInNum', 'notInRate', 'num', 'rate',
                        'oneNum', 'oneRate', 'twoNum', 'twoRate', 'threeNum', 'threeRate', 'otherNum', 'otherRate'],
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
                'extra' => $total
            ];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 入库时效2.0导出
     * Date: 2021-06-09 9:40
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionStorageTimeRate2Export()
    {
        $condition = Yii::$app->request->post('condition', []);
        $storeName = $condition['storeName'] ?: '';
        $beginDate = $condition['dateRange'][0] ?: '';
        $endDate = $condition['dateRange'][1] ?: '';
        /*$sql = "SELECT storeName, dt, totalNum, num, rate, oneNum, oneRate, twoNum, twoRate,
                        threeNum, threeRate, otherNum, otherRate, notInNum, notInRate FROM oauth_in_storage_time_rate_data
                    WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' AND flag = '2.0' ";
        if ($storeName) $sql .= " AND storeName = '{$storeName}' ";*/
        $sql = "oauth_warehouse_tools_in_storage_time_rate_jisuan '{$beginDate}','{$endDate}','{$storeName}','2.0'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        foreach ($data as &$v) {
            $v['rate'] = round($v['rate'] * 100, 2) . '%';
            $v['oneRate'] = round($v['oneRate'] * 100, 2) . '%';
            $v['twoRate'] = round($v['twoRate'] * 100, 2) . '%';
            $v['threeRate'] = round($v['threeRate'] * 100, 2) . '%';
            $v['otherRate'] = round($v['otherRate'] * 100, 2) . '%';
            $v['notInRate'] = round($v['notInRate'] * 100, 2) . '%';
        }
        $title = ['仓库', '扫描日期', '扫描数量', '当天入库数', '当天入库率', '1天内入库数', '1天内入库率',
            '2天内入库数', '2天内入库率', '3天内入库数', '3天内入库率', '3天以上入库数', '3天以上入库率', '未入库数', '未入库率'
        ];
        ExportTools::toExcelOrCsv('storageTimeRate2.0', $data, 'Xls', $title);
    }

    /////////////////////////////////发货时效////////////////////////////////////

    /**
     * 发货时效
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDeliverTimeRate()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ?: 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();

            $total['storeName'] = $storeName;
            $total['totalNum'] = array_sum(ArrayHelper::getColumn($data, 'totalNum'));
            $total['deliverableNum'] = array_sum(ArrayHelper::getColumn($data, 'deliverableNum'));
            $total['zeroNum'] = array_sum(ArrayHelper::getColumn($data, 'zeroNum'));
            $total['zeroRate'] = round(array_sum(ArrayHelper::getColumn($data, 'zeroNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['oneNum'] = array_sum(ArrayHelper::getColumn($data, 'oneNum'));
            $total['oneRate'] = round(array_sum(ArrayHelper::getColumn($data, 'oneNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['twoNum'] = array_sum(ArrayHelper::getColumn($data, 'twoNum'));
            $total['twoRate'] = round(array_sum(ArrayHelper::getColumn($data, 'twoNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['threeNum'] = array_sum(ArrayHelper::getColumn($data, 'threeNum'));
            $total['threeRate'] = round(array_sum(ArrayHelper::getColumn($data, 'threeNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['otherNum'] = array_sum(ArrayHelper::getColumn($data, 'otherNum'));
            $total['otherRate'] = round(array_sum(ArrayHelper::getColumn($data, 'otherNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt', 'storeName', 'totalNum', 'deliverableNum', 'zeroNum', 'zeroRate',
                        'oneNum', 'oneRate', 'twoNum', 'twoRate', 'threeNum', 'threeRate', 'otherNum', 'otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return ['provider' => $provider, 'extra' => $total];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 发货时效导出
     * Date: 2021-06-09 9:42
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDeliverTimeRateExport()
    {
        $condition = Yii::$app->request->post('condition', []);
        $storeName = $condition['storeName'] ?: '';
        $beginDate = $condition['dateRange'][0] ?: '';
        $endDate = $condition['dateRange'][1] ?: '';
        $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $title = ['日期', '仓库', '订单数量', '可发货数量', '当天发货数', '当天发货率', '1天内发货数', '1天内发货率',
            '2天内发货数', '2天内发货率', '3天内发货数', '3天内发货率', '未发货数', '未发货率'
        ];
        ExportTools::toExcelOrCsv('deliverTimeRate', $data, 'Xls', $title);
    }

    /**
     * 发货时效2.0
     * Date: 2021-03-19 14:33
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public function actionDeliverTimeRate2()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ?: 20;
            $storeName = $condition['storeName'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}','2.0'";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $total['storeName'] = $storeName;
            $total['totalNum'] = array_sum(ArrayHelper::getColumn($data, 'totalNum'));
            $total['deliverableNum'] = array_sum(ArrayHelper::getColumn($data, 'deliverableNum'));
            $total['zeroNum'] = array_sum(ArrayHelper::getColumn($data, 'zeroNum'));
            $total['zeroRate'] = round(array_sum(ArrayHelper::getColumn($data, 'zeroNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['oneNum'] = array_sum(ArrayHelper::getColumn($data, 'oneNum'));
            $total['oneRate'] = round(array_sum(ArrayHelper::getColumn($data, 'oneNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['twoNum'] = array_sum(ArrayHelper::getColumn($data, 'twoNum'));
            $total['twoRate'] = round(array_sum(ArrayHelper::getColumn($data, 'twoNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['threeNum'] = array_sum(ArrayHelper::getColumn($data, 'threeNum'));
            $total['threeRate'] = round(array_sum(ArrayHelper::getColumn($data, 'threeNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $total['otherNum'] = array_sum(ArrayHelper::getColumn($data, 'otherNum'));
            $total['otherRate'] = round(array_sum(ArrayHelper::getColumn($data, 'otherNum'))
                * 100.0 / array_sum(ArrayHelper::getColumn($data, 'deliverableNum')), 2);
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt', 'storeName', 'totalNum', 'deliverableNum', 'zeroNum', 'zeroRate',
                        'oneNum', 'oneRate', 'twoNum', 'twoRate', 'threeNum', 'threeRate', 'otherNum', 'otherRate'],
                    'defaultOrder' => [
                        'storeName' => SORT_ASC,
                        'dt' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
            return ['provider' => $provider, 'extra' => $total];
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * 发货时效2.0导出
     * Date: 2021-06-09 9:45
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     * AND (m.FilterFlag = 5) THEN '等待派单'
     * WHEN (m.Orig = 0) AND (m.FilterFlag = 6) AND (l.eub = 1) THEN '派至E邮宝'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 6)AND (l.eub = 2) THEN '派至E线下邮宝'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 6)AND (l.eub = 3) THEN '派4PX独立帐户'
     * WHEN (m.Orig = 0)AND ((m.FilterFlag = 6)AND (l.eub = 4)) THEN  '派至非E邮宝'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 20) THEN  '未拣货'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 22) THEN  '未核单'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 24) THEN  '未包装'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 40) THEN  '待发货'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 26) THEN  '订单缺货(仓库)'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 28) THEN  '缺货待包装'
     * WHEN (m.Orig = 0)AND (m.FilterFlag = 100) THEN  '已发货'
     * WHEN m.Orig = 1 THEN  '已归档'
     * WHEN m.Orig = 3 THEN '异常已归档'
     * WHEN (m.Orig = 2)AND (m.FilterFlag = 0) THEN '等待付款'
     * WHEN (m.Orig = 2)AND (m.FilterFlag = 1) THEN  '订单缺货'
     * WHEN (m.Orig = 2)AND (m.FilterFlag = 2) THEN  '订单退货'
     * WHEN (m.Orig = 2)AND (m.FilterFlag = 3) THEN '订单取消'
     * WHEN (m.Orig = 2)AND (m.FilterFlag = 4) THEN '其它异常单'*/
    public function actionDeliverTimeRate2Export()
    {
        $condition = Yii::$app->request->post('condition', []);
        $storeName = $condition['storeName'] ?: '';
        $beginDate = $condition['dateRange'][0] ?: '';
        $endDate = $condition['dateRange'][1] ?: '';
        $sql = "EXEC oauth_warehouse_tools_deliver_time_rate '{$beginDate}','{$endDate}','{$storeName}','2.0'";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $title = ['日期', '仓库', '订单数量', '可发货数量', '当天发货数', '当天发货率', '1天内发货数', '1天内发货率',
            '2天内发货数', '2天内发货率', '3天内发货数', '3天内发货率', '未发货数', '未发货率'
        ];
        ExportTools::toExcelOrCsv('deliverTimeRate2.0', $data, 'Xls', $title);
    }

    /**
     * 发货时效详情
     * Date: 2021-06-25 17:12
     * Author: henry
     * @return mixed
     */
    public function actionDeliverTimeRateDetail()
    {
        $condition = Yii::$app->request->post('condition', []);

        $pageSize = $condition['pageSize'] ?: 20;

        $data = ApiWarehouseTools::getDeliverTimeRateDetail($condition);
        return new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['tradeNid', 'orderTime', 'scanningDate', 'operateTime', 'storeName', 'closingDate', 'filterFlag'],
                'defaultOrder' => [
                    'tradeNid' => SORT_ASC,
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);

    }

    /**
     * actionDeliverTimeRateDetailExport
     * Date: 2021-06-29 10:39
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionDeliverTimeRateDetailExport()
    {
        $condition = Yii::$app->request->post('condition', []);

        $data = ApiWarehouseTools::getDeliverTimeRateDetail($condition);
        $title = ['订单编号', '交易日期', '操作日期', '核单日期', '发货仓库', '发货日期', '更新时间', '订单状态', '说明'];
        ExportTools::toExcelOrCsv('deliverTimeRateDetail', $data, 'Xls', $title);
    }


    /////////////////////////////////KPI////////////////////////////////////
    public function actionKpiReport()
    {
        try {
            $condition = Yii::$app->request->post('condition', []);
            $pageSize = $condition['pageSize'] ?: 20;
            $name = $condition['name'] ?: '';
            $job = $condition['job'] ?: '';
            $beginDate = $condition['dateRange'][0] ?: '';
            $endDate = $condition['dateRange'][1] ?: '';
            $sql = "SELECT * FROM `warehouse_kpi_report` WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' ";
            if ($name) $sql .= " AND `name` LIKE '%{$name}%'";
            if ($job) $sql .= " AND `job` LIKE '%{$job}%'";
            $data = Yii::$app->db->createCommand($sql)->queryAll();
            return new ArrayDataProvider([
                'allModels' => $data,
                'sort' => [
                    'attributes' => ['dt', 'name', 'job', 'pur_in_package_num',
                        'marking_stock_order_num', 'marking_sku_num', 'labeling_sku_num', 'labeling_goods_num',
                        'pda_in_storage_sku_num', 'pda_in_storage_goods_num', 'pda_in_storage_location_num',
                        'single_sku_num', 'single_goods_num', 'single_location_num',
                        'multi_sku_num', 'multi_goods_num', 'multi_location_num',
                        'pack_single_order_num', 'pack_single_goods_num', 'pack_multi_order_num', 'pack_multi_goods_num',
                        'update_date',
                        'labeling_order_num', 'single_order_num', 'multi_order_num', 'integral',

                    ],
                    'defaultOrder' => [
                        'dt' => SORT_DESC,
                        'name' => SORT_ASC,
                    ]
                ],
                'pagination' => [
                    'pageSize' => $pageSize,
                ],
            ]);
        } catch (Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * actionKpiExport
     * Date: 2021-05-27 15:27
     * Author: henry
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public function actionKpiExport()
    {
        $condition = Yii::$app->request->post('condition', []);
        $name = $condition['name'] ?: '';
        $job = $condition['job'] ?: '';
        $beginDate = $condition['dateRange'][0] ?: '';
        $endDate = $condition['dateRange'][1] ?: '';
        $sql = "SELECT * FROM `warehouse_kpi_report` WHERE dt BETWEEN '{$beginDate}' AND '{$endDate}' ";
        if ($name) $sql .= " AND `name` LIKE '%{$name}%'";
        if ($job) $sql .= " AND `job` LIKE '%{$job}%'";
        $data = Yii::$app->db->createCommand($sql)->queryAll();
        $title = ['id', '姓名', '日期', '职位', '扫描包裹数', '打标订单数', '打标SKU种数', '贴标SKU种数', '贴标产品数',
            '入库SKU种数', '入库产品数', '入库库位数', '拣货单品SKU种数', '拣货单品产品数', '拣货单品库位数', '拣货多品SKU种数',
            '拣货多品产品数', '拣货多品库位数', '打包单品订单数', '打包单品产品数', '打包多品订单数', '打包多品产品数', '更新时间',
            '打标产品数', '贴标订单数', '拣货单品订单数', '拣货多品订单数', '积分'
        ];
        ExportTools::toExcelOrCsv('KPIreport', $data, 'Xls', $title);

    }


}
