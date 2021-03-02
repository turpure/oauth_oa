<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-04-02 10:13
 */

namespace backend\modules\v1\models;

use backend\models\OaCleanOffline;
use backend\models\ShopElf\BPerson;
use backend\models\ShopElf\KCCurrentStock;
use backend\models\TaskPick;
use backend\models\TaskSort;
use backend\models\TaskWarehouse;
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
    public static function getPickMember()
    {
        $ret = BPerson::find()
            ->andWhere(['in', 'Duty', ['拣货', '拣货组长', '拣货-分拣']])->all();
        return ArrayHelper::getColumn($ret, 'PersonName');
    }

    /**
     * @brief 获取分拣人
     * @return array
     */
    public static function getSortMember()
    {
        $identity = Yii::$app->request->get('type', '');

        $query = BPerson::find();
        if ($identity == 'warehouse') {
            $ret = $query->andWhere(['in', 'Duty', ['入库分拣', '快递扫描']])->all();
        } else {
            $ret = $query->andWhere(['in', 'Duty', ['拣货', '拣货组长', '拣货-分拣']])->all();
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
        $ret =OaCleanOffline::find()->select('sku')->where(['checkStatus'=> '初始化','skuType' =>'导入'])->asArray()->all();
        $sku = ArrayHelper::getColumn($ret,'sku');
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
        $title = ['SKU', 'SKU名称', '库存数量', '义乌仓','仓位'];
        return ['data'=>$ret,'name' => 'un-picked-sku','title' => $title];

    }

    public static function cleanOfflineImportExportWrongPicked()
    {
        // 未找到，且是扫描
        $skuRet =OaCleanOffline::find()->select('sku')->where(['checkStatus'=> '未找到', 'skuType' => '扫描'])->asArray()->all();
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
        if(empty($ret)) {
            $out = [];
            foreach ($skuRet as $su) {
                $row = [];
                $row['SKU'] = $su['sku'];
                $row['SKU名称'] = '';
                $row['库存数量'] = '';
                $row['义乌仓'] = '';
                $row['仓位'] = '';
                $out[] = $row;
            }
            $ret = $out;
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
        $checkSkuAgain = OaCleanOffline::find()->where(['sku' => $sku, 'skuType' => '扫描'])->one();
        if(empty($checkSku) && empty($checkSkuAgain)) {
            $oaCleanOffline = new OaCleanOffline();
            $username = Yii::$app->user->identity->username;
            $oaCleanOffline->setAttributes(
                ['sku' =>$sku,'checkStatus'=>'未找到', 'creator' => $username, 'skuType' => '扫描']
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

    /** 获取拣货统计数据
     * @param $condition
     * Date: 2019-08-23 16:16
     * Author: henry
     * @return mixed
     */
    public static function getPickStatisticsData($condition)
    {
        $query = TaskPick::find()->select(new Expression("batchNumber,picker,date_format(MAX(createdTime),'%Y-%m-%d') AS createdTime"));
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
        }
        //获取数据
        $sql = "EXEC guest.oauth_getPickStatisticsData '{$condition['createdTime'][0]}','{$condition['createdTime'][1]}'";

        return Yii::$app->py_db->createCommand($sql)->queryAll();
    }


    /** 获取拣货统计数据
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

    /** 仓库仓位SKU对应表
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
                              ELSE '' END AS type,changeTime
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
                    SELECT StoreName,sl.LocationName,COUNT(gs.sku) AS skuNum
                    FROM [dbo].[B_StoreLocation](nolock) sl
                    LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                    LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                    LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID
                    WHERE s.StoreName='{$store}' GROUP BY sl.LocationName,StoreName 
                ) aa left JOIN (
                    SELECT StoreName,slt.LocationName,COUNT(gst.sku) AS stockSkuNum
                    FROM [dbo].[B_StoreLocation](nolock) slt
                    LEFT JOIN B_Store(nolock) st ON st.NID=slt.StoreID
                    LEFT JOIN B_GoodsSKU(nolock) gst ON slt.NID=gst.LocationID
                    LEFT JOIN KC_CurrentStock(nolock) cst ON gst.NID=cst.GoodsSKUID AND cst.StoreID=slt.StoreID
                    WHERE st.StoreName='{$store}' AND cst.Number > 0 GROUP BY slt.LocationName,StoreName
                ) bb ON aa.LocationName=bb.LocationName WHERE 0=0 ";
        if ($sNum || $sNum === 0) $sql .= " AND ISNULL(bb.stockSkuNum,0) >= '{$sNum}'";
        if ($lNum || $lNum === 0) $sql .= " AND ISNULL(bb.stockSkuNum,0) <= '{$lNum}'";
        $sql .= " ORDER BY ISNULL(bb.stockSkuNum,0) DESC";
        return Yii::$app->py_db->createCommand($sql)->queryAll();

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

        //仓位SKU个数
        $sNum = $condition['number'][0] ?? null;
        $lNum = $condition['number'][1] ?? null;
        $skuNum = $condition['type'] == 'export' ? 'aa.skuNum' : 0;

        $sql = "SELECT StoreName,sl.LocationName,{$skuNum} as skuNum,gs.sku,skuName,goodsskustatus,cs.number,g.devDate,
                CASE WHEN ISNULL(bb.orderNum, 0) > 0 OR ISNULL(cc.orderNum, 0) > 0 THEN '是' ELSE '否' END AS hasPurchaseOrder
                FROM [dbo].[B_StoreLocation](nolock) sl
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID        
                LEFT JOIN (
	                SELECT gs.sku,COUNT (cm.Nid) AS orderNum
                    FROM CG_StockOrderM (nolock) cm
                    JOIN CG_StockOrderD (nolock) D ON cm.Nid = D.StockOrderNid
                    LEFT JOIN B_goodsSKU (nolock) gs ON GoodsSKUID = gs.nid
                    WHERE cm.Archive = 0 AND cm.checkflag = 1 AND cm.inflag = 0 
                        AND d.amount > (d.inamount + isnull(d.inQtyDNoCheck, 0))
                    GROUP BY sku
                ) bb ON bb.sku = gs.sku
                LEFT JOIN (
                    SELECT gs.sku,COUNT (gm.NID) AS orderNum
                    FROM CG_StockInM (nolock) gm
                    INNER JOIN CG_StockInD (nolock) gd ON gm.NID = gd.StockInNID
                    LEFT JOIN B_GoodsSKU (nolock) gs ON gs.NID = gd.GoodsSKUID
                    WHERE gm.makeDate BETWEEN CONVERT (VARCHAR (10),DATEADD(dd, - 5, GETDATE()),121) AND GETDATE()
                    GROUP BY sku
                ) cc ON cc.sku = gs.sku ";
        if($condition['type'] == 'export'){
            $sql .= " LEFT JOIN(			
                    SELECT slt.StoreID,slt.LocationName,COUNT(gst.sku) AS skuNum
                    FROM [dbo].[B_StoreLocation](nolock) slt
                    LEFT JOIN B_GoodsSKU(nolock) gst ON slt.NID=gst.LocationID
                    LEFT JOIN KC_CurrentStock(nolock) cst ON gst.NID=cst.GoodsSKUID AND cst.StoreID=slt.StoreID
                    WHERE cst.Number > 0
                    GROUP BY slt.LocationName,slt.StoreID 
                ) aa ON aa.LocationName=sl.LocationName AND aa.StoreID=sl.StoreID ";
        }
        $sql .= " WHERE s.StoreName='{$store}' ";
        if ($condition['type'] == 'view') $sql .= " AND sl.LocationName='{$location}'";
        if ($sNum || $sNum === 0) $sql .= " AND isNULL(aa.skuNum,0) >= '{$sNum}'";
        if ($lNum || $lNum === 0) $sql .= " AND isNULL(aa.skuNum,0) <= '{$lNum}'";
        $sql .= " ORDER BY cs.Number DESC";
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
        $sql = "SELECT StoreName,sl.LocationName,gs.sku,skuName,goodsSkuStatus,cs.Number,g.createDate as devDate
                FROM [dbo].[B_StoreLocation](nolock) sl
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID  
                WHERE s.StoreName='{$store}' AND sl.LocationName LIKE '%{$location}%'";
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
        $sql = "SELECT StoreName,sl.LocationName,gs.sku,skuName,goodsSkuStatus,cs.Number,g.devDate,
                        sl.NID,sl.storeID,gs.NID as goodsSkuNid
                FROM [dbo].[B_StoreLocation](nolock) sl
                LEFT JOIN B_Store(nolock) s ON s.NID=sl.StoreID
                LEFT JOIN B_GoodsSKU(nolock) gs ON sl.NID=gs.LocationID
                LEFT JOIN B_Goods(nolock) g ON g.NID=gs.goodsID
                LEFT JOIN KC_CurrentStock(nolock) cs ON gs.NID=cs.GoodsSKUID AND cs.StoreID=sl.StoreID  
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
                $r3 = Yii::$app->py_db->createCommand("EXEC P_KC_DestoryBindingGoods {$v['NID']}, '{$v['goodsSkuNid']}'")->execute();
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

}
