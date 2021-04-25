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
                        TotalAmount = (SELECT SUM(IsNull(Amount, 0)) FROM KC_StockChangeD WHERE StockChangeNID = C.Nid),
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
                    WHERE ISNULL(Billtype, 0) IN(0, 1) -- AND  AddClient = 'UR_CENTER'
                            AND CONVERT(VARCHAR(10), MAkeDate, 121) BETWEEN '{$beginDate}' AND '{$endDate}' ";
        if ($BillNumber) $sql .= " AND BillNumber LIKE '%{$BillNumber}%' ";
        if ($logicsWayNumber) $sql .= " AND logicsWayNumber LIKE '%{$logicsWayNumber}%' ";
        if ($inOrOutMan) $sql .= " AND (StoreInMan LIKE '%{$inOrOutMan}%' OR StoreOutMan LIKE '%{$inOrOutMan}%') ";
        if ($inOrOutStoreName) $sql .= " AND (BI.StoreName = '{$inOrOutStoreName}' OR BO.StoreName '{$inOrOutStoreName}') ";
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
        $sql = "SELECT TOP 500 g.NID AS GoodsID,g.GoodsCode,G.GoodsName,G.Class,G.Model,G.Unit,
		        isnull(cs.KcMaxNum, 0) AS MaxNum,isnull(cs.KcMinNum, 0) AS MinNum,S.SKU,S.property1,s.property2,s.property3,
		        s.NID AS GoodsSKUID,cs.Number,cs.ReservationNum AS zyNum,(cs.Number - cs.ReservationNum) AS kyNum,
		        s.SKUName,g.PackMsg,MAX(isnull(cs.price, 0)) AS CostPrice,g.MinPrice,g.salePrice,bs.storename,
		        CASE WHEN isnull(s.CostPrice, 0) = 0 THEN IsNull(g.CostPrice, 0) ELSE s.CostPrice END AS StockPrice,
	            isnull(g.PackageCount, 0) PackageCount,s.GoodsSKUStatus AS GoodsStatus,MAX (bsl.LocationName) AS LocationName,
	            MAX (ss.SupplierName) AS SupplierName,MAX (ss.OfficePhone) AS OfficePhone,MAX (ss.Mobile) AS Mobile,
	            MAX (ss.Address) AS stockAddress,isnull(cs.sellcount1, 0) AS sellcount1,isnull(cs.sellcount2, 0) AS sellcount2,
	            isnull(cs.sellcount3, 0) AS sellcount3,
	            CASE WHEN isNull(sys1.paraValue, '0') = '1' THEN
	                CASE WHEN ISNULL(s.CostPrice, 0) <> 0 THEN s.CostPrice ELSE IsNull(g.CostPrice, 0) END
                ELSE isnull(cs.price, 0) END AS CostPriceOther,
                CASE WHEN isNull(sys1.paraValue, '0') = '1' THEN
	                CASE WHEN ISNULL(s.CostPrice, 0) <> 0 THEN s.CostPrice ELSE IsNull(g.CostPrice, 0) END
                ELSE
                    CASE WHEN isnull(cs.price, 0) = 0 THEN
	                    CASE WHEN ISNULL(s.CostPrice, 0) <> 0 THEN s.CostPrice ELSE IsNull(g.CostPrice, 0) END
                    ELSE isnull(cs.price, 0) END
                END AS CostPriceotherout,
                MAX (s.RetailPrice) AS RetailPrice,g.ItemUrl,g.Style,
                MAX(CASE WHEN isnull(s.Weight, 0) <> 0 THEN s.Weight / 1000.0 ELSE g.Weight / 1000.0 END) AS Weight,
                g.StockMinAmount,IsNull(g.Used, 0) AS Used, 0 AS notinamount
            FROM B_GoodsSKU (nolock) s
            LEFT JOIN B_SysParams (nolock) sys1 ON sys1.ParaCode = 'CalCostFlag'
            INNER JOIN B_Goods (nolock) g ON g.nid = s.goodsID
            LEFT OUTER JOIN B_Supplier (nolock) ss ON ss.nid = g.supplierid
            LEFT OUTER JOIN KC_CurrentStock (nolock) cs ON cs.goodsSKUID = s.NID
            LEFT JOIN B_GoodsSKULocation (nolock) bgs ON bgs.GoodsSKUID = cs.GoodsSKUID AND bgs.storeid = cs.storeid
            LEFT JOIN B_StoreLocation (nolock) bsl ON bsl.NID = bgs.LocationID
            LEFT JOIN b_store (nolock) bs ON bs.NID = cs.storeid
            WHERE isnull(bs.used, 0) = 0 AND bs.StoreName = '{$outStoreName}' AND (s.SKU LIKE '%{$sku}%' OR s.SKUName LIKE '%{$sku}%')
            GROUP BY g.NID,g.GoodsCode,G.GoodsName,G.Class,G.Model,g.ItemUrl,G.Unit,isnull(cs.kcMaxNum, 0),isnull(cs.kcMinNum, 0),
            s.NID,s.SKUName,g.PackMsg,S.SKU,S.property1,s.property2,s.property3,s.CostPrice,g.CostPrice,g.MinPrice,g.salePrice,
            g.PackageCount,s.GoodsSKUStatus,sys1.paraValue,g.Style,g.StockMinAmount,IsNull(g.Used, 0),cs.Number,cs.ReservationNum,
            cs.sellcount1,cs.sellcount2,cs.sellcount3,cs.price,bs.storename";
        return \Yii::$app->py_db->createCommand($sql)->queryAll();

    }


}
