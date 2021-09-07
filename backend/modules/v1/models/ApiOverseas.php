<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2021-03-31
 * Time: 15:29
 * Author: henry
 */

/**
 * @name ApiOverseas.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2021-03-31 15:29
 */


namespace backend\modules\v1\models;


use backend\models\ShopElf\BGoodsSku;
use backend\models\ShopElf\BStore;
use backend\models\ShopElf\KCStockChangeD;
use backend\models\ShopElf\KCStockChangeM;
use backend\models\ShopElf\OauthStockChangeWytPackageInfo;
use backend\modules\v1\services\WytServices;
use backend\modules\v1\utils\Helper;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use Yii;

class ApiOverseas
{
    /**
     * 获取海外仓调拨单列表（只显示在UR 创建的调拨单）
     * @param $condition
     * Date: 2021-03-31 16:53
     * Author: henry
     * @return array
     */
    public static function getStockChangeList($condition)
    {
        $beginDate = $condition['dateRange'][0] ?? '';
        $endDate = $condition['dateRange'][1] ?? '';
        $inOrOutMan = $condition['inOrOutMan'] ?: '';
        $inOrOutStoreName = $condition['inOrOutStoreName'] ?: '';
        $BillNumber = $condition['BillNumber'] ?: '';
        $logicsWayNumber = $condition['logicsWayNumber'] ?: '';
        $status = $condition['status'] ?: [];
        $sku = $condition['sku'] ?: '';
        $sql = "SELECT c.NID, C.MakeDate,C.Billnumber,C.Memo,
                        BO.StoreName AS StoreOutName,Bi.StoreName AS StoreInName,c.Recorder,
                        CASE C.checkflag WHEN 1 THEN '审核' WHEN 3 THEN '作废' ELSE '未审核' END AS Checkflag,
                        C.Audier,C.AudieDate,C.StoreInMan,c.StoreOutMan,FinancialMan,FinancialTime,
                        PackPersonFee,PackMaterialFee,HeadFreight,Tariff,
                        TotalAmount = (SELECT convert(integer,SUM(IsNull(Amount, 0))) FROM KC_StockChangeD WHERE StockChangeNID = C.Nid),
                        TotalMoney = (SELECT SUM(IsNull(Money, 0)) FROM KC_StockChangeD WHERE StockChangeNID = C.Nid),
                        TotalinMoney = (SELECT SUM(IsNull(inMoney, 0)) FROM KC_StockChangeD WHERE StockChangeNID = C.Nid),
                        BW.name AS logicsWayName,
                        BE.name AS expressName,
                        logicsWayNumber,RealWeight,ThrowWeight,c.Archive
                    FROM KC_StockChangeM C
                    LEFT JOIN B_store BI ON BI.NID = C.StoreInID
                    LEFT JOIN B_store BO ON BO.NID = C.StoreOutID
                    LEFT JOIN B_LogisticWay BW ON BW.NID = C.logicsWayNID
                    LEFT JOIN T_Express BE ON BE.NID = C.ExpressNid                   
                    WHERE ISNULL(Billtype, 0) IN(0, 1)  AND  AddClient = 'UR_CENTER'
                            AND CONVERT(VARCHAR(10), MAkeDate, 121) BETWEEN '{$beginDate}' AND '{$endDate}' ";
        if ($BillNumber) $sql .= " AND BillNumber LIKE '%{$BillNumber}%' ";
        if ($logicsWayNumber) $sql .= " AND logicsWayNumber LIKE '%{$logicsWayNumber}%' ";
        if ($inOrOutMan) $sql .= " AND (StoreInMan LIKE '%{$inOrOutMan}%' OR StoreOutMan LIKE '%{$inOrOutMan}%') ";
        if ($inOrOutStoreName) $sql .= " AND (BI.StoreName = '{$inOrOutStoreName}' OR BO.StoreName = '{$inOrOutStoreName}') ";
        $statusList = [];
        foreach ($status as $v) {
            if ($v == '未审核') $statusList[] = 0;
            if ($v == '已审核') $statusList[] = 1;
            if ($v == '作废') $statusList[] = 3;
        }
        $statusStr = implode(',', $statusList);
        if ($statusList) $sql .= " AND  CheckFlag IN ({$statusStr}) ";
        if ($sku) {
            $sql .= " AND EXISTS(SELECT 1 FROM KC_StockChangeD D LEFT JOIN B_GoodsSKU gs ON d.goodsskuid=gs.nid 
                                       WHERE D.stockchangeNID=C.NID AND sku LIKE '{$sku}') ";
        }
        $sql .= " ORDER BY MakeDate ";
        //var_dump($sql);exit;
        return \Yii::$app->py_db->createCommand($sql)->queryAll();

    }

    /**
     * 批量导入SKU 信息
     * @param $condition
     * Date: 2021-03-31 16:53
     * Author: henry
     * @return array
     */
    public static function getImportData($file, $extension)
    {
        $outStoreName = \Yii::$app->request->get('outStoreName', '');
        if (!$outStoreName) {
            return ['code' => 400, 'message' => '调出仓库不能为空'];
        }
        if ($extension == '.xlsx') {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xlsx();
        } else {
            $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        }
        $spreadsheet = $reader->load(\Yii::$app->basePath . $file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
//        return $highestRow;
        try {
            $result = $skuList = [];
            for ($i = 2; $i <= $highestRow; $i++) {
                $sku = (string)$sheet->getCell("A" . $i)->getValue();
                $amount = (int)$sheet->getCell("B" . $i)->getValue();
                if (!$sku) break;
//                var_dump($sku);
                $skuList[] = $sku;
                $result[] = ['SKU' => $sku, 'Amount' => $amount];
                /*$params = ['sku' => $sku, 'outStoreName' => $outStoreName];
                $list = self::getSkuStockInfo($params);
                if($list){
                    $list[0]['Amount'] = $amount;
                    $result[] = $list[0];
                }*/
            }
            $list = self::getSkuDetail($skuList, $outStoreName);
            foreach ($list as &$v) {
                foreach ($result as $value) {
                    if ($v['SKU'] == $value['SKU']) $v['Amount'] = (string)$value['Amount'];
                }
            }
            return $list;
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }

    private static function getSkuDetail($skuList, $storeName)
    {
        $skuList = implode("','", $skuList);
        $sql = "SELECT TOP 500 g.NID AS GoodsID,g.GoodsCode,G.GoodsName,G.Class,G.Model,G.Unit,S.SKU,S.property1,
                s.property2,s.property3,bs.storename,1 as Amount,0 AS PackPersonFee,0 AS PackMaterialFee,0 AS HeadFreight,0 AS Tariff,
		        s.NID AS GoodsSKUID,convert(integer,cs.Number) as Number,convert(integer,cs.ReservationNum) AS zyNum,
		        convert(integer,(cs.Number - cs.ReservationNum)) AS kyNum,
		        s.SKUName,MAX(isnull(cs.price, 0)) AS Price,MAX(isnull(cs.price, 0)) AS Money,MAX (ss.SupplierName) AS SupplierName,
	            isnull(g.PackageCount, 0) PackageCount,s.GoodsSKUStatus AS GoodsStatus,
	            isnull(cs.sellcount1, 0) AS sellcount1,isnull(cs.sellcount2, 0) AS sellcount2,
	            isnull(cs.sellcount3, 0) AS sellcount3,MAX (s.RetailPrice) AS RetailPrice,g.ItemUrl,g.Style,
                MAX(CASE WHEN isnull(s.Weight, 0) <> 0 THEN s.Weight / 1000.0 ELSE g.Weight / 1000.0 END) AS Weight,
                g.StockMinAmount,IsNull(g.Used, 0) AS Used, 0 AS notinamount, 
                MAX(isnull(cs.price, 0)) AS InPrice,MAX(isnull(cs.price, 0)) AS inmoney
            FROM B_GoodsSKU (nolock) s
            LEFT JOIN B_SysParams (nolock) sys1 ON sys1.ParaCode = 'CalCostFlag'
            INNER JOIN B_Goods (nolock) g ON g.nid = s.goodsID
            LEFT OUTER JOIN B_Supplier (nolock) ss ON ss.nid = g.supplierid
            LEFT OUTER JOIN KC_CurrentStock (nolock) cs ON cs.goodsSKUID = s.NID
            LEFT JOIN B_GoodsSKULocation (nolock) bgs ON bgs.GoodsSKUID = cs.GoodsSKUID AND bgs.storeid = cs.storeid
            LEFT JOIN B_StoreLocation (nolock) bsl ON bsl.NID = bgs.LocationID
            LEFT JOIN b_store (nolock) bs ON bs.NID = cs.storeid
            WHERE isnull(bs.used, 0) = 0 AND bs.StoreName = '{$storeName}' AND s.SKU IN ('{$skuList}')
            GROUP BY g.NID,g.GoodsCode,G.GoodsName,G.Class,G.Model,g.ItemUrl,G.Unit,s.NID,s.SKUName,S.SKU,
            S.property1,s.property2,s.property3,s.CostPrice,g.CostPrice,s.GoodsSKUStatus,sys1.paraValue,
            g.Style,g.StockMinAmount,IsNull(g.Used, 0),cs.Number,cs.ReservationNum,isnull(g.PackageCount, 0),
            cs.sellcount1,cs.sellcount2,cs.sellcount3,cs.price,bs.storename";
        return \Yii::$app->py_db->createCommand($sql)->queryAll();
    }

    /**
     * 查询 SKU 信息
     * @param $condition
     * Date: 2021-03-31 16:53
     * Author: henry
     * @return array
     */
    public static function getSkuStockInfo($condition)
    {
        $outStoreName = $condition['outStoreName'] ?: '';
        $sku = $condition['sku'] ?: '';
        if (!$outStoreName) {
            return ['code' => 400, 'message' => '调出仓库不能为空'];
        }
        if (!$sku) {
            return ['code' => 400, 'message' => '关键字不能为空，请输入SKU或商品编码或商品名称'];
        }
        $sql = "SELECT TOP 500 g.NID AS GoodsID,g.GoodsCode,G.GoodsName,G.Class,G.Model,G.Unit,S.SKU,S.property1,
                s.property2,s.property3,bs.storename,1 as Amount,0 AS PackPersonFee,0 AS PackMaterialFee,0 AS HeadFreight,0 AS Tariff,
		        s.NID AS GoodsSKUID,convert(integer,cs.Number) as Number,convert(integer,cs.ReservationNum) AS zyNum,
		        convert(integer,(cs.Number - cs.ReservationNum)) AS kyNum,
		        s.SKUName,MAX(isnull(cs.price, 0)) AS Price,MAX(isnull(cs.price, 0)) AS Money,MAX (ss.SupplierName) AS SupplierName,
	            isnull(g.PackageCount, 0) PackageCount,s.GoodsSKUStatus AS GoodsStatus,
	            isnull(cs.sellcount1, 0) AS sellcount1,isnull(cs.sellcount2, 0) AS sellcount2,
	            isnull(cs.sellcount3, 0) AS sellcount3,MAX (s.RetailPrice) AS RetailPrice,g.ItemUrl,g.Style,
                MAX(CASE WHEN isnull(s.Weight, 0) <> 0 THEN s.Weight / 1000.0 ELSE g.Weight / 1000.0 END) AS Weight,
                g.StockMinAmount,IsNull(g.Used, 0) AS Used, 0 AS notinamount, 
                MAX(isnull(cs.price, 0)) AS InPrice,MAX(isnull(cs.price, 0)) AS inmoney
            FROM B_GoodsSKU (nolock) s
            LEFT JOIN B_SysParams (nolock) sys1 ON sys1.ParaCode = 'CalCostFlag'
            INNER JOIN B_Goods (nolock) g ON g.nid = s.goodsID
            LEFT OUTER JOIN B_Supplier (nolock) ss ON ss.nid = g.supplierid
            LEFT OUTER JOIN KC_CurrentStock (nolock) cs ON cs.goodsSKUID = s.NID
            LEFT JOIN B_GoodsSKULocation (nolock) bgs ON bgs.GoodsSKUID = cs.GoodsSKUID AND bgs.storeid = cs.storeid
            LEFT JOIN B_StoreLocation (nolock) bsl ON bsl.NID = bgs.LocationID
            LEFT JOIN b_store (nolock) bs ON bs.NID = cs.storeid
            WHERE isnull(bs.used, 0) = 0 AND bs.StoreName = '{$outStoreName}' AND (s.SKU LIKE '%{$sku}%' OR s.SKUName LIKE '%{$sku}%')
            GROUP BY g.NID,g.GoodsCode,G.GoodsName,G.Class,G.Model,g.ItemUrl,G.Unit,s.NID,s.SKUName,S.SKU,
            S.property1,s.property2,s.property3,s.CostPrice,g.CostPrice,s.GoodsSKUStatus,sys1.paraValue,
            g.Style,g.StockMinAmount,IsNull(g.Used, 0),cs.Number,cs.ReservationNum,isnull(g.PackageCount, 0),
            cs.sellcount1,cs.sellcount2,cs.sellcount3,cs.price,bs.storename";
        return \Yii::$app->py_db->createCommand($sql)->queryAll();

    }


    /**
     * 保存调拨单信息（增加、编辑）
     * @param $condition
     * Date: 2021-04-25 17:32
     * Author: henry
     * @return bool
     * @throws Exception
     */
    public static function saveStockChange($condition)
    {
        $nid = $condition['basicInfo']['NID'] ?? 0;
        $model = KCStockChangeM::findOne(['NID' => $nid]);
        if ($model && $model['CheckFlag'] != 0) {
            throw new Exception('Approved order cannot be modified!');
        }
        if (!$model) {
            $model = new KCStockChangeM();
            $condition['basicInfo']['BillNumber'] = \Yii::$app->py_db->createCommand("EXEC P_S_CodeRuleGet 22334,'' ")->queryScalar();
            $condition['basicInfo']['MakeDate'] = date('Y-m-d H:i:s');
            $condition['basicInfo']['Recorder'] = $condition['basicInfo']['Recorder'] ?: \Yii::$app->user->identity->username;
//            var_dump($condition);exit;
        }
        //获取仓库ID
        if (!isset($condition['basicInfo']['StoreInID']) || !$condition['basicInfo']['StoreInID']) {
            $condition['basicInfo']['StoreInID'] = BStore::findOne(['StoreName' => $condition['basicInfo']['StoreInName']])['NID'];
        }
        if (!isset($condition['basicInfo']['StoreOutID']) || !$condition['basicInfo']['StoreOutID']) {
            $condition['basicInfo']['StoreOutID'] = BStore::findOne(['StoreName' => $condition['basicInfo']['StoreOutName']])['NID'];
        }

        $tran = \Yii::$app->py_db->beginTransaction();
        try {
            //保存调拨单主体信息
            $model->setAttributes($condition['basicInfo']);
            if (!$model->save()) {
                //var_dump($model->getErrors());
                throw new Exception('Failed to save main stock change order info!');
            }
            //删除调拨单详细信息
            $oldSkuList = KCStockChangeD::findAll(['StockChangeNID' => $model->NID]);
            $oldSkuIds = ArrayHelper::getColumn($oldSkuList, 'GoodsSKUID');
            $newSkuIds = ArrayHelper::getColumn($condition['skuInfo'], 'GoodsSKUID');
            $deleteSkuIds = array_diff($oldSkuIds, $newSkuIds);

            //删除多余SKU 信息
            KCStockChangeD::deleteAll(['StockChangeNID' => $model->NID, 'GoodsSKUID' => $deleteSkuIds]);

            //保存调拨单详细信息
            foreach ($condition['skuInfo'] as $sku) {
                if (in_array($sku['GoodsSKUID'], $oldSkuIds)) {
                    $model_d = KCStockChangeD::findOne(['GoodsSKUID' => $sku['GoodsSKUID'], 'StockChangeNID' => $model->NID]);
                } else {
                    $model_d = new KCStockChangeD();
                }
                $sku['Money'] = $sku['Amount'] * $sku['Price'];

                $model_d->setAttributes($sku);
                $model_d->StockChangeNID = $model->NID;
                if (!$model_d->save()) {
                    throw new Exception('Failed to save detail stock change order info!');
                }
            }
            $tran->commit();
            return $model->NID;
        } catch (Exception $e) {
            $tran->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 计算调拨单头程运费
     * 按包裹体积计算SKU 头程运费
     * @param $condition
     * Date: 2021-04-25 17:32
     * Author: henry
     * @return bool
     * @throws Exception
     */
    public static function saveStockFreight($stockChangeNID)
    {
        $order = KCStockChangeM::findOne(['NID' => $stockChangeNID]);
        $wytInNo = $order['Memo'];
        $totalFreight = $order['HeadFreight'];
        $wytPackageInfo = self::getWytPackageInfo($wytInNo);
        // 计算每个包裹的运费
        $packageList = self::getWytPackageFreight($wytPackageInfo['packageList'], $totalFreight);
        //计算单个SKU体积
        $skuVolumeList = [];
        foreach ($wytPackageInfo['merchandiseList'] as $v) {
            $skuVolumeList[$v['merchandiseCode']] = $v['actualLength'] * $v['actualWidth'] * $v['actualHeight'];
        }
        //计算每个SKU分摊的运费 并保存数据
        $tran = \Yii::$app->py_db->beginTransaction();
        try {
            foreach ($wytPackageInfo['merchandiseList'] as $v) {
                $sku = $v['merchandiseCode'];
                $qty = $v['actualQuantity'];
//                var_dump($sku);
                $totalSkuFreight = $totalSkuQty = 0;
                foreach ($packageList as $package) {
                    $packageSkuList = ArrayHelper::getColumn($package['merchandiseList'], 'merchandiseCode');
                    $packageSkuCount = count($packageSkuList);
                    if (in_array($sku, $packageSkuList)) {
                        //包裹只有一个SKU
                        if ($packageSkuCount == 1) {
                            $totalSkuFreight += $package['packageFreight'];
                            foreach ($package['merchandiseList'] as $v) {
                                $totalSkuQty += $v['actualQuantity'];
                            }
                        } //包裹有多个SKU
                        else {
                            list($kuFreight, $skuQty) = self::getWytPackageSkuFreight($package['merchandiseList'], $skuVolumeList, $sku, $package['packageFreight']);
                            $totalSkuFreight += $kuFreight;
                            $totalSkuQty += $skuQty;
                        }
                    } else {
                        continue;
                    }
                    // 判断SKU 数量
                    if($qty == $totalSkuQty){
                        break;
                    }
                }
                $goodsSku = BGoodsSku::findOne(['SKU' => $sku]);
//                $res = KCStockChangeD::updateAll(['HeadFreight' => $totalSkuFreight], ['StockChangeNID' => $stockChangeNID, 'GoodsSKUID' => $goodsSku['NID']]);
                $model = KCStockChangeD::findOne(['StockChangeNID' => $stockChangeNID, 'GoodsSKUID' => $goodsSku['NID']]);
                var_dump($model);
                $model->HeadFreight = $totalSkuFreight;
                $res = $model->save();
                if(!$res){
//                    var_dump($sku);
                    var_dump($model->getErrors());exit;
                    throw new Exception('Failed to save sku head freight info!');
                }
            }
            $tran->commit();
            return true;
        } catch (Exception $e) {
            $tran->rollBack();
            throw new Exception($e->getMessage());
        }

    }

    /**
     * 更新调拨单 入库价格
     * @param $stockChangeNID
     * Date: 2021-04-25 17:33
     * Author: henry
     * @return int
     * @throws Exception
     */
    public static function updateStockChangeInPrice($stockChangeNID)
    {
        $sql = "exec oauth_overseas_update_stock_change_order_in_price $stockChangeNID";
        return \Yii::$app->py_db->createCommand($sql)->execute();
    }

    /**
     * 获取调拨单详情
     * @param $condition
     * Date: 2021-04-26 17:33
     * Author: henry
     * @return int
     * @throws Exception
     */
    public static function getStockChange($condition)
    {
        $id = $condition['NID'];
        $basicSql = "SELECT C.NID,C.CheckFlag,C.BillNumber,C.MakeDate,C.StoreInID,C.StoreOutID,C.Memo,C.Recorder,C.Audier,
	                AudieDate = CONVERT (CHAR(10), C.AudieDate, 121),C.StoreInMan,C.StoreOutMan,C.FinancialMan,
	                BO.StoreName AS StoreOutName,BI.StoreName AS StoreInName,PackPersonFee,PackMaterialFee,
	                isnull(ifHeadFreight, 1) AS ifHeadFreight,HeadFreight,Tariff,
	                BW.name AS logicsWayName,BE.name AS expressName,logicsWayNumber,RealWeight,ThrowWeight
                    FROM KC_StockChangeM C
                    LEFT OUTER JOIN B_store BI ON BI.NID = C.StoreInID
                    LEFT OUTER JOIN B_store BO ON BO.NID = C.StoreOutID
                    LEFT OUTER JOIN B_LogisticWay BW ON BW.NID = C.logicsWayNID
                    LEFT OUTER JOIN T_Express BE ON BE.NID = C.expressnid
                    WHERE C.NID = $id";
        $skuSql = "SELECT d.NID,d.StockChangeNID,S.barCode,d.GoodsID,s.GoodsCode,s.GoodsName,s.Class,s.Unit,
                        convert(integer,d.Amount) as Amount,d.StockAmount,d.price AS Price,d.Money,s.Model,gs.SKU,
                        gs.property1,gs.property2,gs.property3,D.Remark,gs.nid AS GoodsSKUID,d.InStockQty,d.InPrice,
                        d.inmoney,gs.SkuName,d.PackPersonFee,d.PackMaterialFee,d.HeadFreight,d.Tariff,gs.Weight,
		                convert(integer,cs.Number) as Number,convert(integer,cs.ReservationNum) AS zyNum,
		                convert(integer,cs.Number - cs.ReservationNum) AS kyNum
	                FROM KC_StockChangeD d
                    INNER JOIN B_GoodsSKU gs ON gs.NID = d.GoodsSKUID
                    INNER JOIN B_Goods s ON s.NID = d.GoodsID
                    INNER JOIN KC_StockChangeM m ON m.NID = d.StockChangeNID
                    INNER JOIN KC_CurrentStock (nolock) cs ON cs.goodsSKUID = d.goodsSKUID and cs.storeID = m.StoreOutID
                    WHERE d.StockChangeNID = $id";
        $res['basicInfo'] = \Yii::$app->py_db->createCommand($basicSql)->queryOne();
        $res['skuInfo'] = \Yii::$app->py_db->createCommand($skuSql)->queryAll();
        return $res;
    }

    /**
     * 审核调拨单
     * @param $condition
     * Date: 2021-04-26 17:33
     * Author: henry
     * @return int
     * @throws Exception
     */
    public static function checkStockChange($condition)
    {
        $id = $condition['NID'];
        $sql = "exec P_KC_OutCheckReservationNum 'KC_StockChangeM', $id ";
        $msg = \Yii::$app->py_db->createCommand($sql)->queryOne();
        if ($msg['errorcount'] > 0) {
            return ['code' => 400, 'message' => $msg['errormsg']];
        } else {
            $user = \Yii::$app->user->identity->username;
            $checkSql = "exec P_KC_CurrentStock 'KC_StockChangeM', $user, 1, $id ";
            $res = \Yii::$app->py_db->createCommand($checkSql)->queryOne();
            if (!$res['errorcount']) {
                return ['code' => 400, 'message' => $res['errormsg']];
            }
            return true;
        }
    }

    /**
     * 获取万邑通包裹信息
     * @param $wytInNo 万邑通入库单号
     * Date: 2021-09-06 16:07
     * Author: henry
     * @return mixed
     * @throws Exception
     */
    public static function getWytPackageInfo($wytInNo)
    {
        $base_url = Yii::$app->params['wyt']['base_url'];
        $action = 'winit.wh.inbound.getOrderDetail';
        $data = [
            'orderNo' => $wytInNo,
            'isIncludePackage' => 'Y',
        ];
        $params = WytServices::get_request_par($data, $action);
        $res = Helper::request($base_url, json_encode($params));
        if ($res[0] == 200) {
            if ($res[1]['code'] == '0') {
//                return $res[1]['data'];
                return self::saveWytPackageInfo($res[1]['data'], $wytInNo);
            } else {
                throw new Exception($res[1]['msg']);
            }
        } else {
            throw new Exception('request error!');
        }
    }

    public static function saveWytPackageInfo($packageList, $wytInNo){
        $tran = \Yii::$app->py_db->beginTransaction();
        try {
            foreach ($packageList['packageList'] as $package){
                $params = [
                    "wyt_in_no" => $wytInNo,
                    "package_no" => $package["packageNo"],
                    "seller_case_no" => $package["sellerCaseNo"],
                    "length" => $package["length"],
                    "width" => $package["width"],
                    "height" => $package["height"],
                    "weight" => $package["weight"],
                ];
                foreach ($package['merchandiseList'] as $v){
                    $params['sku'] = $v['merchandiseCode'];
                    $params['quantity'] = $v['actualQuantity'];
                    $params['update_time'] = date('Y-m-d H:i:s');
                    $model = OauthStockChangeWytPackageInfo::findOne(['wyt_in_no' => $wytInNo, 'sku' => $v['merchandiseCode']]);
                    if(!$model){
                        $model = new OauthStockChangeWytPackageInfo();
                    }
                    $model->setAttributes($params);
                    if(!$model->save()){
                        throw new Exception('Failed to save stock change package info!');
                    }
                }
            }
            $tran->commit();
            return true;
        } catch (Exception $e) {
            $tran->rollBack();
            throw new Exception($e->getMessage());
        }
    }

    /**
     * 计算万邑通订单 单个包裹运费
     * @param $packageList  万邑通入库单包裹列表
     * @param $totalFreight 万邑通入库单总运费
     * Date: 2021-09-06 18:03
     * Author: henry
     * @return mixed
     */
    public static function getWytPackageFreight($packageList, $totalFreight)
    {
        $totalVolume = 0;
        foreach ($packageList as $v) {
            $totalVolume += $v['length'] * $v['width'] * $v['height'];
        }
        foreach ($packageList as &$v) {
            $volume = $v['length'] * $v['width'] * $v['height'];
//            $v['packageFreight'] = round($volume * 1.0 * $totalFreight/ $totalVolume,4);
            $v['packageFreight'] = $volume * 1.0 * $totalFreight / $totalVolume;
        }
        return $packageList;
    }

    /**
     * 计算SKU 在单一包裹中的的运费（按体积占比计算）
     * @param $packageSkuList 包裹中SKU列表
     * @param $skuVolumeList  订单中所有SKU的体积列表
     * @param $sku            要计算的SKU
     * @param $packageFreight 包裹的总运费
     * Date: 2021-09-06 18:09
     * Author: henry
     * @return false|float|int|array
     */
    public static function getWytPackageSkuFreight($packageSkuList, $skuVolumeList, $sku, $packageFreight)
    {
        $totalSkuVolume = $skuFreight = $skuQty = 0;
        foreach ($packageSkuList as $v) {
            $totalSkuVolume += $skuVolumeList[$v['merchandiseCode']];
        }
        foreach ($packageSkuList as $v) {
            if ($v['merchandiseCode'] == $sku) {
                $skuFreight = $skuVolumeList[$v['merchandiseCode']] * $v['actualQuantity'] * $packageFreight / $totalSkuVolume;
                $skuQty = $v['actualQuantity'];
                break;
            }
        }
        return [$skuFreight, $skuQty];
    }


}
