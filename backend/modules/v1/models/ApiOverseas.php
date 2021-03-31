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
        try {
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
                        CASE C.checkflag WHEN 1 THEN '审核' WHEN 3 THEN '作废' ELSE '未审核' END AS checkflag,
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
                    WHERE ISNULL(Billtype, 0) IN(0, 1)  AND  AddConlit = 'UR_CENTER'
                            AND CONVERT(VARCHAR(10), MAkeDate, 121) BETWEEN '{$beginDate}' AND '{$endDate}' ";
            if ($BillNumber) $sql .= " AND BillNumber LIKE '%{$BillNumber}%' ";
            if ($logicsWayNumber) $sql .= " AND logicsWayNumber LIKE '%{$logicsWayNumber}%' ";
            if ($inOrOutMan) $sql .= " AND (StoreInMan LIKE '%{$inOrOutMan}%' OR StoreOutMan LIKE '%{$inOrOutMan}%') ";
            if ($inOrOutStoreName) $sql .= " AND (bs.StoreName = '{$inOrOutStoreName}' OR s.StoreName '{$inOrOutStoreName}') ";
            $statusList = [];
            foreach ($status as $v){
                if($v == '未审核') $statusList[] = 0;
                if($v == '已审核') $statusList[] = 1;
                if($v == '作废') $statusList[] = 3;
            }
            $statusStr = implode(',', $statusList);
//            var_dump($statusStr);exit;
            if ($statusList) $sql .= " AND  CheckFlag IN ({$statusStr}) ";
//            if ($status == '已审核') $sql .= " AND  CheckFlag = 1 ";
//            if ($status == '作废') $sql .= " AND  CheckFlag = 3 ";
            if ($sku) {
                $sql .= " AND EXISTS(SELECT 1 FROM KC_StockChangeD d LEFT JOIN B_GoodsSKU gs ON d.goodsskuid=gs.nid 
                                       WHERE d.stockchangeNID=k.NID AND sku LIKE '{$sku}') ";
            }
            $sql .= " ORDER BY MakeDate ";
            return \Yii::$app->py_db->createCommand($sql)->bindValues($sql)->queryAll();
        } catch (\Exception $e) {
            return [
                'code' => 400,
                'message' => $e->getMessage()
            ];
        }
    }


}
