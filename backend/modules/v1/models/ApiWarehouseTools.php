<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\OaCleanOffline;
use backend\models\ShopElf\BGoods;
use backend\models\ShopElf\BPerson;
use backend\models\ShopElf\KCCurrentStock;
use backend\models\ShopElf\OauthLabelGoodsRate;
use backend\models\ShopElf\OauthLoadSkuError;
use backend\models\TaskPick;
use backend\models\TaskSort;
use backend\models\ShopElf\TaskWarehouse;
use Codeception\Template\Api;
use PhpOffice\PhpSpreadsheet\IOFactory;
use backend\modules\v1\utils\ExportTools;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\db\Expression;
use yii\helpers\ArrayHelper;
use Yii;
use yii\data\ActiveDataProvider;
use backend\modules\v1\utils\Helper;


class ApiWarehouseTools
{
    /**
     * 包裹扫描日志
     * @param $condition
     * Date: 2021-04-30 10:18
     * Author: henry
     * @return ArrayDataProvider
     * @throws Exception
     */
    public static function getPackageScanningLog($condition){
        $username = $condition['username'];
        $trackingNumber = $condition['trackingNumber'];
        $pageSize = $condition['pageSize'] ?: 20;
        $stockOrderNumber = $condition['stockOrderNumber'] ?: '';
        $flag = $condition['flag'];
        $begin = $condition['dateRange'][0];
        $end = $condition['dateRange'][1] . " 23:59:59";
        $sql = "SELECT id,trackingNumber,stockOrderNumber,username,flag,MAX(createdTime) AS createdTime 
                FROM oauth_task_package_info 
                WHERE createdTime BETWEEN '{$begin}' AND '{$end}' ";
        if ($username) $sql .= " AND username LIKE '%{$username}%' ";
        if ($trackingNumber) $sql .= " AND trackingNumber LIKE '%{$trackingNumber}%' ";
        if ($stockOrderNumber) $sql .= " AND stockOrderNumber LIKE '%{$stockOrderNumber}%' ";
        if (in_array($flag, ['0', '1', '2']) ) $sql .= " AND flag = '{$flag}' ";

        $sql .= " GROUP BY id,trackingNumber,stockOrderNumber,username,flag ORDER BY MAX(createdTime) DESC";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * 包裹扫描统计
     * @param $condition
     * Date: 2021-05-19 14:17
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function getPackageScanningStatistics($condition){
        $begin = $condition['dateRange'][0];
        $end = $condition['dateRange'][1] . " 23:59:59";
        $sql = "SELECT trackingNumber,stockOrderNumber,username,flag,CONVERT(VARCHAR(10), MAX(createdTime),121) AS createdTime 
                FROM oauth_task_package_info 
                WHERE createdTime BETWEEN '{$begin}' AND '{$end}' ";

        $sql .= " GROUP BY trackingNumber,stockOrderNumber,username,flag";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $userList = array_unique(ArrayHelper::getColumn($data, 'username'));
        $timeList = array_unique(ArrayHelper::getColumn($data, 'createdTime'));
        sort($timeList);
//        var_dump($userList);
//        var_dump($timeList);exit;
        $res = [];
        foreach ($timeList as $time){
            foreach ($userList as $user){
                $item = [
                    'dt' => $time,
                    'username' => $user,
                    'scanNum' => 0,
                    'outOfStockNum' => 0,
                    'num' => 0,
                    'errorNum' => 0,
                ];
                foreach ($data as $v){
                    if($v['createdTime'] == $time && $v['username'] == $user){
                        $item['scanNum'] += 1;
                        if($v['flag'] == 1) $item['outOfStockNum'] += 1;  //缺货
                        if($v['flag'] == 0) $item['num'] += 1;            //正常
                        if($v['flag'] == 2) $item['errorNum'] += 1;       //异常
                    }
                }
                if($item['scanNum'] > 0) $res[] = $item;
            }
        }
        return $res;
    }

    /**
     * @brief 包裹扫描结果
     * @param $condition
     * @return array|bool
     */
    public static function getPackageScanningResult($condition){
        if(!$condition['trackingNumber']){
            return [
                'code' => 400,
                'message' => 'tracking number can not be empty!'
            ];
        }
        $sql = "SELECT DISTINCT LogisticOrderNo,stockOrderNumber, ISNULL(b.goodsskuid,0) AS goodsskuid
                      --   CASE WHEN COUNT(a.goodsskuID) = COUNT(DISTINCT b.goodsskuid) THEN 1 ELSE 0 END AS flag
                FROM(
                        SELECT LogisticOrderNo,sm.billNumber AS stockOrderNumber,d.goodsskuID
                        FROM CG_StockOrderM (nolock) sm 
                        LEFT JOIN CG_StockOrderD (nolock) d ON sm.nid = d.stockOrderNID
                        WHERE LogisticOrderNo = '{$condition['trackingNumber']}'
                ) a LEFT JOIN(
                        SELECT sku,goodsskuid,SUM(l_qty) AS num
                        FROM P_TradeDtUn (nolock) dt 
                        LEFT JOIN P_TradeUn (nolock) t ON dt.tradeNID = t.NID
                        WHERE t.FilterFlag = 1 AND orderTime BETWEEN DATEADD(dd, -90, CONVERT(VARCHAR(10),GETDATE(),121)) AND GETDATE()
                        GROUP BY sku,goodsskuid
                ) b ON a.goodsskuID=b.goodsskuid
                ORDER BY ISNULL(b.goodsskuid,0) DESC ";
        try {
            $data = Yii::$app->py_db->createCommand($sql)->queryOne();
            $row = [
                'trackingNumber' => $condition['trackingNumber'],
                'stockOrderNumber' => $data['stockOrderNumber'] ?? '',
                'username' => $condition['username'],
                'createdTime' => date('Y-m-d H:i:s'),
                'flag' => $data ? ($data['goodsskuid'] > 0 ? 1 : 0) : 2,
            ];
            $res = Yii::$app->py_db->createCommand()->insert('oauth_task_package_info', $row)->execute();
            if(!$res){
                throw new Exception('Failed to save info!');
            }
            $data = ['flag' => $row['flag']];
            return  $data;
        } catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief 包裹扫描删除
     * @param $condition
     * @return array|bool
     */
    public static function packageDelete($condition){
        if(!$condition['id']){
            return [
                'code' => 400,
                'message' => 'tracking id can not be empty!'
            ];
        }
        try {
            $res = Yii::$app->py_db->createCommand()->delete(
                'oauth_task_package_info',
                ['id' => $condition['id']])->execute();
            if(!$res){
                throw new Exception('Failed to mark info!');
            }
            return  true;
        } catch (Exception $e){
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * @brief 添加拣货任务
     * @param $condition
     * @return array|bool
     */
    public static function setBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskPick();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 添加分货任务
     * @param $condition
     * @return array|bool
     */
    public static function setSortBatchNumber($condition)
    {
        $row = [
            'batchNumber' => $condition['batchNumber'],
            'picker' => $condition['picker'],
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskSort();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 获取拣货人
     * @return array
     */
    public static function getWarehouseMember($type)
    {
        $query = BPerson::find()->andWhere(['Used' => 0]);
        if ($type == 'all'){
            $data = $query->all();
            return ArrayHelper::getColumn($data, 'PersonName');
        } elseif ($type == 'packageScanning'){
            $query->andWhere(['in', 'Duty', ['快递扫描']]);
        }elseif ($type == 'load'){
            $query->andWhere(['in', 'Duty', ['上架']]);
        } elseif ($type == 'label'){
            $query->andWhere(['in', 'Duty', ['打标', '贴标']]);
            return $query->all();
//            return ArrayHelper::map($ret, 'PersonCode','PersonName');
        }else{
            $query->andWhere(['in', 'Duty', ['拣货', '拣货组长', '拣货-分拣']]);
        }
        $ret = $query->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 获取分拣人
     * @return array
     */
    public static function getSortMember()
    {
        $identity = Yii::$app->request->get('type', 'warehouse');

        $query = BPerson::find();
        if ($identity == 'warehouse') {
            $ret = $query->andWhere(['in', 'Duty', ['入库分拣', '快递扫描']])->all();
        } else {
            $ret = $query->andWhere(['in', 'Duty', ['多品分拣']])->all();
//            $ret = $query->andWhere(['in', 'Duty', ['拣货', '拣货组长', '拣货-分拣']])->all();
        }
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 拣货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getScanningLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' => ['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskPick::find();
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 分货扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getSortLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' => ['batchNumber', 'picker', 'scanningMan'], 'equal' => ['isDone']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = TaskSort::find();
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /**
     * 线下清仓SKU列表
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getCleanOfflineList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $fieldsFilter = ['like' =>['sku'], 'equal' => ['checkStatus']];
        $timeFilter = ['createdTime', 'updatedTime'];
        $query = OaCleanOffline::find();
        $query = Helper::generateFilter($query,$fieldsFilter,$condition);
        $query = Helper::timeFilter($query,$timeFilter,$condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /**
     * 上传清仓SKU
     * @return array
     */
    public static function cleanOfflineImport()
    {
        $file = $_FILES['file'];
        if (!$file) {
            return ['code' => 400, 'message' => 'The file can not be empty!'];
        }
        //判断文件后缀
        $extension = Helper::get_extension($file['name']);
        if($extension !== 'Xlsx') return ['code' => 400, 'message' => "File format error,please upload files in the format of Xlsx"];

        //文件上传
        $result = Helper::file($file, 'cleanOfflineImport');
        $fileName = $file['name'];
        $fileSize = $file['size'];
        if (!$result) {
            return ['code' => 400, 'message' => 'File upload failed'];
        }else{
            static::truncateCleanOffLineData();
            //获取上传excel文件的内容并保存
            return self::saveCleanOfflineData($result, $fileName, $fileSize, $extension);
        }

    }

    /**
     * 清空之前的数据
     */
    private static function truncateCleanOffLineData()
    {
        OaCleanOffline::deleteAll();
    }
    /**
     *读取文件并保存
     */
    private static function saveCleanOfflineData($file, $fileName, $fileSize, $extension)
    {
        $userName = Yii::$app->user->identity->username;
        $reader = IOFactory::createReader($extension);
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $errorRes = [];
        try {
            for ($i = 2; $i <= $highestRow; $i++) {
                $data['sku'] = $sheet->getCell("A" . $i)->getValue();
                $data['creator'] = $userName;
                $data['skuType'] = '导入';
                if (!$data['sku']) break;
                $cleanOffline = new OaCleanOffline();
                $cleanOffline->setAttributes($data);
                $cleanOffline->save();
            }
            return $errorRes;
        }catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }


    /**
     * 清仓模板
     * @return array
     */
    public static function cleanOfflineImportTemplate()
    {
       $rows = [['SKU'=>'']];
       $ret = ['data' => $rows, 'name' =>'SKU-Template'];
       return $ret;

    }


    /**
     *未拣货
     * @return mixed
     */
    public static function cleanOfflineExportUnPicked()
    {

        // 未扫描到，且SKU状态是导入
        $skuRet =OaCleanOffline::find()->select('sku')->where(['checkStatus'=> '初始化','skuType' =>'导入'])->asArray()->all();
        $sku = ArrayHelper::getColumn($skuRet,'sku');
        $sku = implode("','", $sku);
        $sku = "'" . $sku . "'";

        $sql = "SELECT
                gs.sku,
                gs.SkuName,
                kc.Number, 
                s.storeName,
                bsl.LocationName
                FROM
                b_goodsSKU (nolock) gs
                LEFT JOIN kc_currentstock (nolock) kc ON gs.nid = kc.goodsskuid
                LEFT JOIN b_store (nolock) s ON s.nid = kc.storeid
                LEFT JOIN B_GoodsSKULocation (nolock) bgs ON kc.GoodsSKUID = bgs.GoodsSKUID
                AND isNull(bgs.StoreID, 0) = isNull(kc.StoreID, 0)
                LEFT JOIN B_StoreLocation (nolock) bsl ON bsl.StoreID = kc.storeid
                AND bsl.nid = bgs.LocationID
                WHERE 
                isnull(s.used, 0) = 0
                and gs.sku in ($sku)";

        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        $skuFound = ArrayHelper::getColumn($ret,'sku');
        foreach ($skuRet as $su) {
            if(!in_array($su['sku'], $skuFound,true)) {
                $row = [];
                $row['sku'] = $su['sku'];
                $row['SkuName'] = '';
                $row['Number'] = '';
                $row['storeName'] = '';
                $row['LocationName'] = '';
                $ret[] = $row;
            }
        }
        $title = ['SKU', 'SKU名称', '库存数量', '义乌仓','仓位'];
        return ['data'=>$ret,'name' => 'un-picked-sku','title' => $title];

    }

    public static function cleanOfflineImportExportWrongPicked()
    {
        // 拣错货，且是扫描
        $skuRet =OaCleanOffline::find()->select('sku')->where(['checkStatus'=> '拣错货', 'skuType' => '扫描'])->asArray()->all();
        $sku = ArrayHelper::getColumn($skuRet,'sku');
        $sku = implode("','", $sku);
        $sku = "'" . $sku . "'";

        $sql = "SELECT
                gs.sku,
                gs.SkuName,
                kc.Number, 
                s.storeName,
                bsl.LocationName
                FROM
                b_goodsSKU (nolock) gs
                LEFT JOIN kc_currentstock (nolock) kc ON gs.nid = kc.goodsskuid
                LEFT JOIN b_store (nolock) s ON s.nid = kc.storeid
                LEFT JOIN B_GoodsSKULocation (nolock) bgs ON kc.GoodsSKUID = bgs.GoodsSKUID
                AND isNull(bgs.StoreID, 0) = isNull(kc.StoreID, 0)
                LEFT JOIN B_StoreLocation (nolock) bsl ON bsl.StoreID = kc.storeid
                AND bsl.nid = bgs.LocationID
                WHERE 
                isnull(s.used, 0) = 0
                and gs.sku in ($sku)";

        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        $skuFound = ArrayHelper::getColumn($ret,'sku');
        foreach ($skuRet as $su) {
            if(!in_array($su['sku'], $skuFound,true)) {
                $row = [];
                $row['sku'] = $su['sku'];
                $row['SkuName'] = '';
                $row['Number'] = '';
                $row['storeName'] = '';
                $row['LocationName'] = '';
                $ret[] = $row;
            }
        }
        $title = ['SKU', 'SKU名称', '库存数量', '义乌仓','仓位'];
        return ['data'=>$ret,'name' => 'wrong-picked-sku','title' => $title];

    }


    /**
     * 更改SKU的扫描状态
     * @param $condition
     * @return mixed
     * @throws Exception
     */
    public static function cleanOfflineScan($condition)
    {
        if(!isset($condition['sku'])) {
            throw new Exception('parameter of sku is required');
        }
        $sku = $condition['sku'];
        // 只判断导入的SKU
        $checkSku = OaCleanOffline::find()->where(['sku' => $sku, 'skuType' => '导入'])->one();
        if(empty($checkSku)) {
            $checkSkuAgain = OaCleanOffline::find()->where(['sku' => $sku, 'skuType' => '扫描'])->one();
            $oaCleanOffline = new OaCleanOffline();

            if (!empty($checkSkuAgain)) {
                throw new Exception('没有找到相关SKU!');
            }

            $username = Yii::$app->user->identity->username;
            $oaCleanOffline->setAttributes(
                ['sku' =>$sku,'checkStatus'=>'拣错货', 'creator' => $username, 'skuType' => '扫描']
            );
            if(!$oaCleanOffline->save()) {
                throw new Exception('fail to add sku!');
            }
            else {
                throw new Exception('没有找到相关SKU!');
            }



        }
        else {
            $checkSku->setAttributes(['checkStatus' => '已找到']);
            if(!$checkSku->save()) {
                throw new Exception('fail to update sku!');
            }
            return ['已找到相关SKU!'];
        }

    }



    /**
     * @brief 添加入库任务
     * @param $condition
     * @return array|bool
     */
    public static function setWarehouseBatchNumber($condition)
    {
        $row = [
            'logisticsNo' => isset($condition['logisticsNo']) ? $condition['logisticsNo'] : '',
            'user' => $condition['user'],
            'sku' => $condition['sku'],
            'number' => isset($condition['number']) ? $condition['number'] : 1,
            'scanningMan' => Yii::$app->user->identity->username,
        ];

        $task = new TaskWarehouse();
        $task->setAttributes($row);
        if ($task->save()) {
            return true;
        }
        return [
            'code' => 400,
            'message' => 'failed'
        ];
    }

    /**
     * @brief 入库扫描记录
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getWarehouseLog($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $fieldsFilter = ['like' => ['logisticsNo', 'user', 'sku'], 'equal' => ['number']];
        $timeFilter = ['updatedTime'];
        $query = TaskWarehouse::find();
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition);
        $query->orderBy('id DESC');
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }



    /**
     * @brief 入库扫描记录下载
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function warehouseLogExport($condition)
    {
        $fieldsFilter = ['like' => ['logisticsNo', 'user', 'sku'], 'equal' => ['number']];
        $timeFilter = ['updatedTime'];
        $query = TaskWarehouse::find();
        $query = Helper::generateFilter($query, $fieldsFilter, $condition);
        $query = Helper::timeFilter($query, $timeFilter, $condition);
        $data = $query->orderBy('id DESC')->asArray()->all();
        $name = 'warehouseLog';
        ExportTools::toExcelOrCsv($name, $data, 'Xls');
    }


    /**
     * 仓位匹配绩效查询
     * @param $condition
     * @return mixed
     */
    public static function getFreightSpaceMatched($condition)
    {
        $date = $condition['date'];
        $begin = $date[0];
        $end = $date[1];
        $member = isset($condition['member']) ? $condition['member'] : [];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $billNumber = isset($condition['billNumber']) ? $condition['billNumber'] : '';

        if (empty($member)) {
            $sql = "select makeDate,recorder, billNumber  from CG_StockInM(nolock) where convert(varchar(10),makeDate,121) BETWEEN :begin and  :end ";
        } else {
            $member = implode("','", $member);
            $sql = "select makeDate,recorder, billNumber  from CG_StockInM(nolock) where recorder in ('{$member}') and convert(varchar(10),makeDate,121) BETWEEN :begin and  :end ";
        }
        if (!empty($billNumber)) {
            $sql .= "And billNumber='{$billNumber}'";
        }
        $db = Yii::$app->py_db;
        $data = $db->createCommand($sql, [':begin' => $begin, ':end' => $end])->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

    /**
     * 扫描人
     * @return mixed
     */
    public static function getFreightMen()
    {
        $sql = "select distinct recorder from CG_StockInM(nolock) where recorder in (select personCode from B_Person where used=0)";
        $db = Yii::$app->py_db;
        $data = $db->createCommand($sql)->queryAll();
        return ArrayHelper::getColumn($data, 'recorder');

    }


    /**
     * 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getPickStatisticsData($condition, $flag = 0)
    {
        /*$query = TaskPick::find()->select(new Expression("batchNumber,picker,date_format(MAX(createdTime),'%Y-%m-%d') AS createdTime"));
        $query = $query->andWhere(['<>', "IFNULL(batchNumber,'')", '']);
        $query = $query->andWhere(['<>', "IFNULL(picker,'')", '']);
        $query = $query->groupBy(['batchNumber', 'picker']);
        $query = $query->having(['between', "date_format(MAX(createdTime),'%Y-%m-%d')", $condition['createdTime'][0], $condition['createdTime'][1]]);
        $list = $query->asArray()->all();
        //清空临时表数据
        Yii::$app->py_db->createCommand()->truncateTable('guest.oauth_taskPickTmp')->execute();

        $step = 200;
        for ($i = 1; $i <= ceil(count($list) / $step); $i++) {
            Yii::$app->py_db->createCommand()->batchInsert('guest.oauth_taskPickTmp', ['batchNumber', 'picker', 'createdTime'], array_slice($list, ($i - 1) * $step, $step))->execute();
        }*/
        //获取数据
        $sql = "EXEC guest.oauth_getPickStatisticsData '{$condition['dateRange'][0]}','{$condition['dateRange'][1]}','{$flag}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    ///////////////////////////////上架工具//////////////////////////////////////////////////////


    /**
     * 获取异常SKU
     * @param $condition
     * Date: 2021-04-19 16:16
     * Author: henry
     * @return mixed
     */
    public static function getLoadErrorData($condition){
        $beginDate = $condition['dateRange'][0];
        $endDate = $condition['dateRange'][1] . ' 23:59:59';
        //获取数据
//        $sql = "SELECT SKU, recorder, createdDate FROM [dbo].[oauth_load_sku_error] WHERE createdDate BETWEEN '{$beginDate}' AND '{$endDate}'; ";
        return OauthLoadSkuError::find()->andWhere(['BETWEEN','createdDate', $beginDate, $endDate])->all();
    }

    /**
     * 获取上架完成度数据
     * @param $condition
     * Date: 2021-04-19 16:16
     * Author: henry
     * @return mixed
     */
    public static function getLoadRateData($condition){
        //获取数据
        $sql = "EXEC oauth_warehouse_tools_pda_loading_data 0, '{$condition['dateRange'][0]}','{$condition['dateRange'][1]}',
        '{$condition['SKU']}','{$condition['isLoad']}','{$condition['isError']}','{$condition['isNew']}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * 获取上架统计数据
     * @param $condition
     * Date: 2021-04-19 16:16
     * Author: henry
     * @return mixed
     */
    public static function getLoadListData($condition){

        //获取数据
        $sql = "EXEC oauth_warehouse_tools_pda_loading_data 1, '{$condition['dateRange'][0]}','{$condition['dateRange'][1]}'";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * 获取上货统计数据
     * @param $condition
     * Date: 2021-04-19 16:16
     * Author: henry
     * @return mixed
     */
    public static function getLoadStatisticsData($condition, $flag = 0)
    {
//        $user = $condition['scanUser'] ? : self::getWarehouseMember('load');
        $user = $condition['scanUser'] ? : [];
        $scanUser = implode(',', $user);
            //获取数据
        $sql = "EXEC oauth_warehouse_tools_pda_loading_statistics '{$condition['dateRange'][0]}','{$condition['dateRange'][1]}',
        '{$scanUser}','{$condition['sku']}','{$flag}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    public static function getLoadStatisticsDetail($condition, $flag = 0){
        $user = $condition['scanUser'] ? : self::getWarehouseMember('load');
        $scanUser = implode("','", $user);
        if ($flag == 0){
            $sql = "SELECT locationName,sku,CONVERT(VARCHAR(10),ScanTime,121) AS scanTime,COUNT(sku) AS num
                    FROM [dbo].[P_PdaScanLog](nolock)
                    WHERE CONVERT(VARCHAR(10),ScanTime,121) BETWEEN '{$condition['dateRange'][0]}' AND '{$condition['dateRange'][1]} '
                        AND scanUser IN ('{$scanUser}') ";
            if (isset($condition['sku']) && $condition['sku']){
                $sku = str_replace(',', "','", $condition['sku']);
                $sql .= " AND sku IN ('{$sku}')";
            }
            $sql .= " GROUP BY locationName,sku,CONVERT(VARCHAR(10),ScanTime,121) ORDER BY CONVERT(VARCHAR(10),ScanTime,121) DESC";
        }else{
            $sql = "SELECT locationName,sku,scanUser,COUNT(sku) AS num
                    FROM [dbo].[P_PdaScanLog](nolock)
                    WHERE CONVERT(VARCHAR(10),ScanTime,121) BETWEEN '{$condition['dateRange'][0]}' AND '{$condition['dateRange'][1]}'
                    AND scanUser IN ('{$scanUser}') ";
            if (isset($condition['sku']) && $condition['sku']){
                $sku = str_replace(',', "','", $condition['sku']);
                $sql .= " AND sku IN ('{$sku}')";
            }
            $sql .= " GROUP BY locationName,sku,scanUser ORDER BY scanUser";
        }

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }




    /**
     * 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getWareStatisticsData($condition)
    {
        $beginTime = isset($condition['orderTime'][0]) ? $condition['orderTime'][0] : '';
        $endTime = isset($condition['orderTime'][1]) ? $condition['orderTime'][1] : '';
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        //获取数据
        $sql = "SELECT ptd.sku,bgsku.skuName,bg.salerName,bgsku.goodsSkuStatus,bg.purchaser,
			          bs.storeName,sl.locationName,Dateadd(HOUR, 8, MIN(m.ORDERTIME)) AS minOrderTime,
                      DATEDIFF(DAY,Dateadd(HOUR, 8, MIN(ORDERTIME)),GETDATE()) AS maxDelayDays,
                      loseSkuCount = (
	                      SUM(ptd.L_QTY) - (
	                        SELECT d.Number-d.ReservationNum
			                FROM KC_CurrentStock (nolock) d WHERE ptd.GoodsSKUID = d.GoodsSKUID
			                AND d.StoreID = ptd.StoreID
			              )
                      ),
                      unStockNum = (
                        select SUM(isnull(d.Amount,0) - isnull(d.inAmount,0))
	                    FROM CG_StockOrderD(nolock) d
	                    LEFT JOIN CG_StockOrderM(nolock) m ON d.stockOrderNid=m.nid
			            WHERE d.goodsSkuid = ptd.GoodsSKUID
			              AND (m.CheckFlag = 1)   --审核通过的订单
				          AND (m.Archive = 0)
                      )
                FROM P_TradeUn (nolock) m
                LEFT JOIN	P_TradeDtUn (nolock) ptd ON m.nid=ptd.tradenid
                LEFT JOIN B_GoodsSKULocation (nolock) bgs ON ptd.GoodsSKUID = bgs.GoodsSKUID AND ptd.StoreID = bgs.StoreID
                LEFT JOIN B_goodssku (nolock) bgsku ON bgsku.nid = ptd.goodsskuid
                LEFT JOIN b_goods (nolock) bg ON bg.nid = bgsku.goodsid
                LEFT JOIN B_StoreLocation (nolock) sl ON sl.nid = bgs.LocationID
                LEFT JOIN B_Store bs ON ISNULL(ptd.StoreID, 0) = ISNULL(bs.NID, 0)
                WHERE FilterFlag = 1 ";
        if ($condition['sku']) {
            $sql .= " AND ptd.sku LIKE '%{$condition['sku']}%'";
        }
        if ($condition['skuName']) {
            $sql .= " AND bgsku.skuName LIKE '%{$condition['skuName']}%'";
        }
        if ($condition['goodsSKUStatus']) {
            $sql .= " AND bgsku.GoodsSKUStatus LIKE '%{$condition['goodsSKUStatus']}%'";
        }
        if ($condition['purchaser']) {
            $sql .= " AND bg.Purchaser LIKE '%{$condition['purchaser']}%'";
        }

        if ($beginTime && $endTime) {
            $sql .= " AND CONVERT(VARCHAR(10),DATEADD(HH,8,ordertime),121) BETWEEN '{$beginTime}' AND '{$endTime}' ";
        }

        $sql .= " GROUP BY ptd.sku,bgsku.skuName,ptd.GoodsSKUID,ptd.StoreID,bg.SalerName,
			        bgsku.GoodsSKUStatus,bg.Purchaser,bs.StoreName,sl.LocationName";

        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * 仓库仓位SKU对应表
     * @param $condition
     * Date: 2019-09-03 10:23
     * Author: henry
     * @return ArrayDataProvider
     */
    public static function getWareSkuData($condition)
    {
        $sku = $condition['sku'] ? str_replace(",", "','", $condition['sku']) : '';
        $beginTime = isset($condition['changeTime'][0]) ? $condition['changeTime'][0] : '';
        $endTime = isset($condition['changeTime'][1]) ? $condition['changeTime'][1] . ' 23:59:59' : '';
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 20;
        $sql = "SELECT gs.sku,s.storeName,l.locationName,
                        CASE WHEN charindex('->',ll.person) > 0 THEN SUBSTRING(ll.person,1,charindex('->',person) - 1) 
                            ELSE ll.person END AS person, 
                        CASE WHEN charindex('->',ll.person) > 0 THEN 'PDA' 
                              ELSE '' END AS type,changeTime,
                        CASE WHEN SUBSTRING(ISNULL(nowLocation,''),1,4) IN ('0001','0002','0003') THEN '老品' 
                             WHEN (SELECT COUNT(1) FROM B_GoodsSKULocationLog WHERE SKU = gs.SKU) <= 1 THEN '新品'
							ELSE '老品' END AS flag
                FROM [dbo].[B_GoodsSKULocation] gsl
                INNER JOIN B_GoodsSKU gs ON gs.NID=gsl.GoodsSKUID
                LEFT JOIN B_Store s ON s.NID=gsl.StoreID
                INNER JOIN B_StoreLocation l ON l.NID=gsl.LocationID
                INNER JOIN  B_GoodsSKULocationLog ll ON ll.sku=gs.sku AND ll.nowLocation=LocationName 
                WHERE 1=1 ";
        if ($sku) {
            $sql .= " AND gs.SKU IN ('{$sku}') ";
        }
        if ($condition['store']) {
            $sql .= " AND StoreName LIKE '%{$condition['sku']}%' ";
        }
        if ($condition['location']) {
            $sql .= " AND LocationName LIKE '%{$condition['location']}%' ";
        }
        if ($condition['person']) {
            $sql .= " AND person LIKE '%{$condition['person']}%' ";
        }
        if ($beginTime && $endTime) {
            $sql .= " AND changeTime BETWEEN '{$beginTime}' AND '{$endTime}' ";
        }
        $sql .= " ORDER BY gs.sku ASC, s.storeName ASC, changeTime DESC";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();


        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }

////////////////////////仓位明细/////////////////////////////////////////

    /** 获取仓位明细主表
     * getPositionDetails
     * @param $condition
     * Date: 2021-02-23 16:20
     * Author: henry
     * @return mixed
     */
    public static function getPositionDetails($condition)
    {
        $store = $condition['store'] ?: '义乌仓';

        //仓位SKU个数
        $sNum = $condition['number'][0] ?? null;
        $lNum = $condition['number'][1] ?? null;

        $sql = "SELECT aa.*,ISNULL(bb.stockSkuNum,0) stockSkuNum FROM (			
                    SELECT StoreName,LocationName,SUM(skuNum) AS skuNum
				    FROM (
						SELECT StoreName,sl.LocationName,--		isnull(gs.sku,'')--COUNT(DISTINCT isnull(gs.sku,'')) AS skuNum
						CASE WHEN isnull(gs.sku,'') = '' THEN 0 ELSE 1 END AS skuNum
                        FROM [dbo].[B_StoreLocation](nolock) sl
                        LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
						LEFT JOIN B_GoodsSKULocation(nolock) bgs ON sl.NID=bgs.locationID AND sl.StoreID=bgs.StoreID
						LEFT JOIN B_GoodsSKU(nolock) gs ON gs.NID=bgs.GoodsSKUID
                        WHERE s.StoreName='{$store}' 
			        ) a GROUP BY LocationName,StoreName
                ) aa left JOIN (
                    SELECT StoreName,slt.LocationName,COUNT(gst.sku) AS stockSkuNum
                    FROM [dbo].[B_StoreLocation](nolock) slt
										INNER JOIN B_GoodsSKULocation(nolock) gslt ON slt.NID=gslt.locationID AND gslt.StoreID=slt.StoreID
                    INNER JOIN B_Store(nolock) st ON st.NID=slt.StoreID
                    INNER JOIN B_GoodsSKU(nolock) gst ON gst.NID=gslt.GoodsSKUID
                    INNER JOIN KC_CurrentStock(nolock) cst ON gst.NID=cst.GoodsSKUID AND cst.StoreID=slt.StoreID
                    WHERE st.StoreName='{$store}' AND cst.Number > 0 GROUP BY slt.LocationName,StoreName
                ) bb ON ISNULL(aa.LocationName,'')=ISNULL(bb.LocationName,'') WHERE 0=0 ";
        if ($sNum || $sNum === '0') $sql .= " AND ISNULL(bb.stockSkuNum,0) >= '{$sNum}'";
        if ($lNum || $lNum === '0') $sql .= " AND ISNULL(bb.stockSkuNum,0) <= '{$lNum}'";
        $sql .= " ORDER BY ISNULL(bb.stockSkuNum,0) DESC";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
        return Yii::$app->py_db->createCommand($sql)->getRawSql();

    }

    /** 获取仓位明细详情表
     * getPositionDetails
     * @param $condition
     * Date: 2021-02-23 16:20
     * Author: henry
     * @return mixed
     */
    public static function getPositionDetailsView($condition)
    {
        $store = $condition['store'] ?: '义乌仓';
        $location = $condition['location'] ?? '';
        $filterNum = $condition['filterNum'] ?? 0;

        //仓位SKU个数
        $sNum = $condition['number'][0] ?? '';
        $lNum = $condition['number'][1] ?? '';

        $sql = "EXEC oauth_warehousePositionDetail '{$store}','{$location}','{$sNum}','{$lNum}','{$filterNum}'";
        return Yii::$app->py_db->createCommand($sql)->queryAll();

    }


    /** 仓位查询
     * getPositionDetails
     * @param $condition
     * Date: 2021-02-23 16:20
     * Author: henry
     * @return mixed
     */
    public static function getPositionSearchData($condition)
    {
        $store = $condition['store'] ?: '义乌仓';
        $location = $condition['location'];
        $address = $condition['address'] ?? '';
        $sql = "SELECT StoreName,sl.LocationName,gs.sku,skuName,goodsSkuStatus,cs.Number,g.createDate as devDate
                FROM [dbo].[B_StoreLocation](nolock) sl
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID  
                WHERE s.StoreName='{$store}' ";
        if($location) $sql .= " AND sl.LocationName LIKE '%{$location}%' ";
        if($address) $sql .= " AND sl.LocationName LIKE '{$address}%' ";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /** 无库存SKU查询
     * getPositionDetails
     * @param $condition
     * Date: 2021-02-23 16:20
     * Author: henry
     * @return mixed
     */
    public static function getPositionManageData($condition)
    {
        $store = $condition['store'] ?: '义乌仓';
        $status = $condition['status'];
        if (!is_array($status)) $status = [$status];
        $status = implode("','", $status);
        $sql = "SELECT StoreName,LocationName,gs.sku,skuName,goodsSkuStatus,cs.Number, g.devDate,
                        sl.locationID,sl.storeID,gs.NID as goodsSkuNid
                FROM [dbo].[B_GoodsSKU](nolock) gs
                LEFT JOIN KC_CurrentStock(nolock) cs ON cs.GoodsSKUID = gs.NID 
				LEFT JOIN B_GoodsSKULocation(nolock) sl ON sl.goodsSkuID=cs.goodsSkuID AND isNull(sl.StoreID, 0) = isNull(cs.StoreID, 0)
                LEFT JOIN B_StoreLocation(nolock) bsl ON bsl.NID=sl.locationID
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                WHERE s.StoreName='{$store}' AND goodsSkuStatus IN ('{$status}') AND ISNULL(cs.Number,0)=0 
                -- ORDER BY devDate
                ";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    public static function positionSkuDelete($condition)
    {
        $data = self::getPositionManageData($condition);
        $msg = [];
        foreach ($data as $v) {
            $tran = Yii::$app->py_db->beginTransaction();
            try {


                $params = [
                    'USERID' => Yii::$app->user->identity->username,
                    'MODName' => '仓库货位',
                    'DOTYPE' => '删除库位ID为' . $v['NID'] . '的所有绑定',
                    'DOTIME' => date('Y-m-d H:i:s'),
                    'DOContent' => '清除库位绑定操作',
                    'LOGINIP' => Yii::$app->request->userIP,
                ];
                // 插入操作日志
                $r1 = Yii::$app->py_db->createCommand()->insert('S_Log', $params)->execute();
                $r2 = Yii::$app->py_db->createCommand()->insert('S_Logbak', $params)->execute();
                $r3 = Yii::$app->py_db->createCommand("EXEC P_KC_DestoryBindingGoods {$v['LocationID']}, '{$v['goodsSkuNid']}'")->execute();
                $par = [
                    'person' => Yii::$app->user->identity->username,
                    'changeTime' => date('Y-m-d H:i:s'),
                    'OldLocation' => $v['LocationName'],
                    'SKU' => $v['sku'],
                    'NowLocation' => '',
                    'StoreID' => $v['storeID'],
                ];
                $r4 = Yii::$app->py_db->createCommand()->insert('B_GoodsSKULocationLog', $par)->execute();
                $tran->commit();
            } catch (\Exception $e) {
                $tran->rollback();
                $msg[] = $e->getMessage();
            }
        }
        return $msg;
    }

    /**
     * getNotPickingTradeNum
     * @param $condition
     * Date: 2021-04-15 10:25
     * Author: henry
     * @return mixed
     */
    public static function getNotPickingTradeNum($condition){
//        $beginDate = $condition['dateRange'][0] ?: '';
//        $endDate = $condition['dateRange'][1] ?: '';
        $beginDate = date('Y-m-d', strtotime('-60 days'));
        $endDate = date('Y-m-d');
        $sql = "SELECT COUNT (1) AS allcount FROM P_TradeStock (nolock) m
                WHERE FilterFlag = 20 AND CONVERT(VARCHAR(10),dateadd(hh,8,ordertime),121) BETWEEN '{$beginDate}' AND '{$endDate}'";
        return Yii::$app->py_db->createCommand($sql)->queryScalar();
    }

    ###########################贴标###############################

    /**
     * 贴标
     * @param $condition
     * Date: 2021-04-22 14:45
     * Author: henry
     * @return array|bool
     * @throws Exception
     */
    public static function label($condition){
        $batchNumber = $condition['batchNumber'];
        $username = $condition['username'];
        $updateTime = date('Y-m-d H:i:s');
        $sql = "SELECT nid FROM CG_StockInM (nolock) WHERE BillNumber='{$batchNumber}' ";
        $orderIdList = Yii::$app->py_db->createCommand($sql)->queryAll();
//        var_dump($orderIdList);exit;
        $tran = Yii::$app->py_db->beginTransaction();
        try {
            foreach ($orderIdList as $id){
                $updateStockSql = "UPDATE CG_StockInM SET weigher='{$username}', weighingTime='{$updateTime}' WHERE NID = {$id['nid']}";
                $logs = 'oauth-' . $username . ' ' . $updateTime . ' 修改称重信息';
                $logSql = "INSERT INTO CG_StockLogs
                            VALUES('采购入库单', {$id['nid']}, '{$username}', '{$logs}') ";
                $update = Yii::$app->py_db->createCommand($updateStockSql)->execute();
                $insert = Yii::$app->py_db->createCommand($logSql)->execute();
                if(!$update || !$insert){
                    throw new Exception("Failed to save info of '{$batchNumber}'");
                }
            }
            $tran->commit();
            return true;
        }catch (Exception $e){
            $tran->rollBack();
            return [
                'code' => 400,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * 贴标商品详情
     * @param $condition
     * Date: 2021-04-30 10:23
     * Author: henry
     * @return mixed
     */
    public static function getLabelDetail($condition){
        $sql = "SELECT g.goodsCode,goodsName,gs.sku,skuName,amount,ISNULL(rate,1) AS rate 
                FROM CG_StockInD (nolock) d 
                LEFT JOIN CG_StockInM (nolock) m ON m.NID=d.stockInNID
                LEFT JOIN B_Goods (nolock) g ON g.NID=d.goodsid
                LEFT JOIN B_GoodsSKU (nolock) gs ON gs.NID=d.goodsSkuid
                LEFT JOIN oauth_label_goods_rate (nolock) gr ON gr.goodsCode=g.goodsCode
                WHERE BillNumber= '{$condition['batchNumber']}'";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    public static function saveImportLabelGoods($file, $extension){
        if($extension == '.xlsx'){
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        }else{
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        $spreadsheet = $reader->load(\Yii::$app->basePath . $file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        try {
            $result = [];
            for ($i = 2; $i <= $highestRow; $i++) {
                $goodsCode = (string) $sheet->getCell("A" . $i)->getValue();
                $rate = (int) $sheet->getCell("B" . $i)->getValue();
                if(!$goodsCode) break;
                $model = OauthLabelGoodsRate::findOne(['goodsCode' => $goodsCode]);
                if(!$model){
                    $model = new OauthLabelGoodsRate();
                    $model->creator = Yii::$app->user->identity->username;
                }
                $model->goodsCode = $goodsCode;
                $model->rate = $rate;
                if(!$model->save()){
                    $result[] = "Failed to save info of '{$goodsCode}'";
                }else{
                    BGoods::updateAll(['PackingRatio' => $model->rate], ['GoodsCode' => $model->goodsCode]);
                }
            }
            return $result;
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }



    /** 获取贴标统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getLabelStatisticsData($condition, $flag = 0){
//        $member = self::getWarehouseMember('label');
//        $userList = $condition['username'] ? : ArrayHelper::getColumn($member, 'PersonCode');
        $userList = $condition['username'] ? : [];
        $userList = implode(',', $userList);
        //获取数据
        $sql = "EXEC oauth_warehouse_tools_label_statistics '{$condition['dateRange'][0]}', 
                    '{$condition['dateRange'][1]}', '{$userList}','{$flag}'";
        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /** 获取贴标统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getDeliverTimeRateDetail($condition){
        $flag = $condition['flag'] ?: 0;
        $version = $condition['version'] ?: '1.0';
        $opDate = $condition['opDate'] ?: '';
        $sql = "SELECT tradeNid,orderTime,operateTime,scanningDate,storeName,closingDate,
                        ISNULL(updateTime, GETDATE()) as updateTime,
                        CASE WHEN FilterFlag = 5 THEN '等待派单'
                                WHEN FilterFlag = 6 THEN '已派单'
                                WHEN FilterFlag = 20 THEN  '未拣货'
                                WHEN FilterFlag = 22 THEN  '未核单'
                                WHEN FilterFlag = 24 THEN  '未包装'
                                WHEN FilterFlag = 40 THEN  '待发货'
                                WHEN FilterFlag = 26 THEN  '订单缺货(仓库)'
                                WHEN FilterFlag = 28 THEN  '缺货待包装'
                                WHEN FilterFlag = 100 THEN  '已发货'
                                WHEN FilterFlag = 200 THEN  '已归档'
                                WHEN FilterFlag = 0 THEN '等待付款'
                                WHEN FilterFlag = 1 THEN  '订单缺货'
                                WHEN FilterFlag = 2 THEN  '订单退货'
                                WHEN FilterFlag = 3 THEN '订单取消'
                                WHEN FilterFlag = 4 THEN '其它异常单'
                        END AS FilterFlag
                         FROM [dbo].[oauth_cache_trade_id_history] WHERE storeName='义乌仓' ";
        if($version == '2.0'){
            $sql .= " AND CONVERT(VARCHAR(10),dateADD(mi,990,operateTime),121)='{$opDate}' ";
            if ($flag == 0){
                $sql .= " AND DATEDIFF(
                                dd,
                                (CASE WHEN ISNULL(scanningDate,'')='' OR
											CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121) <= CONVERT(VARCHAR(10),scanningDate,121) 
									THEN CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121)
									ELSE CONVERT(VARCHAR(10),operateTime,121) END
								),
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 ))<>0 ";
            }elseif ($flag == 1){
                $sql .= " AND DATEDIFF(
                                dd,
                                (CASE WHEN ISNULL(scanningDate,'')='' OR
											CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121) <= CONVERT(VARCHAR(10),scanningDate,121) 
									THEN CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121)
									ELSE CONVERT(VARCHAR(10),operateTime,121) END
								),
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1) ";
            }elseif ($flag == 2){
                $sql .= " AND DATEDIFF(
                                dd,
                                (CASE WHEN ISNULL(scanningDate,'')='' OR
											CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121) <= CONVERT(VARCHAR(10),scanningDate,121) 
									THEN CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121)
									ELSE CONVERT(VARCHAR(10),operateTime,121) END
								),
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1,2) ";
            }elseif ($flag == 3){
                $sql .= " AND DATEDIFF(
                                dd,
                                (CASE WHEN ISNULL(scanningDate,'')='' OR
											CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121) <= CONVERT(VARCHAR(10),scanningDate,121) 
									THEN CONVERT(VARCHAR(10),DATEADD(mi, 990, operateTime),121)
									ELSE CONVERT(VARCHAR(10),operateTime,121) END
								),
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1,2,3) ";
            }else{
                $sql .= " AND (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END) = '' ";
            }
        }else{
            $sql .= " AND CONVERT(VARCHAR(10),operateTime,121)='{$opDate}' ";
            if ($flag == 0){
                $sql .= " AND DATEDIFF(dd,operateTime,
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 ))<>0 ";
            }elseif ($flag == 1){
                $sql .= " AND DATEDIFF(dd,operateTime,
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1) ";
            }elseif ($flag == 2){
                $sql .= " AND DATEDIFF(dd,operateTime,
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1,2) ";
            }elseif ($flag == 3){
                $sql .= " AND DATEDIFF(dd,operateTime,
                                (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END
                                 )) NOT IN (0,1,2,3) ";
            }else{
                $sql .= " AND (CASE WHEN ISNULL(scanningDate,'')<>'' THEN scanningDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag IN (100,200) THEN closingDate
                                    WHEN ISNULL(scanningDate,'')='' AND filterFlag = 40 AND ISNULL(closingDate,'')<>'' THEN operateTime
                                    ELSE '' END) = '' ";
            }
        }

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }



}
