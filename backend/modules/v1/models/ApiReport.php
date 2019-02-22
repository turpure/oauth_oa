<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-06-12 14:22
 */

namespace backend\modules\v1\models;

use Yii;
use yii\data\ArrayDataProvider;
use yii\helpers\ArrayHelper;

class ApiReport
{
    /**
     * @brief sales profit report
     * @param $condition
     * @return array
     */

    public static function getSalesReport($condition)
    {
        $con = Yii::$app->db;
        $sql = 'call report_salesProfit(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse,:exchangeRate);';
        $sqlParams = [
            ':dateType' => $condition['dateType'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':queryType' => $condition['queryType'],
            ':store' => $condition['store'],
            ':warehouse' => $condition['warehouse'],
            ':exchangeRate' => $condition['exchangeRate']
        ];
        try {
            return $con->createCommand($sql)->bindValues($sqlParams)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }


    /**
     * @brief develop profit report
     * @params $condition
     * @return array
     */
    public static function getDevelopReport($condition)
    {
        $sql = "EXEC P_DevNetprofit_advanced @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName=:seller,@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate='',".
        "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':seller' => $condition['seller'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief Purchase profit report
     * @params $condition
     * @return array
     */
    public static function getPurchaseReport($condition)
    {
        $sql = "EXEC z_p_purchaserProfit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName='',@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate='',".
        "@DevDateEnd='',@Purchaser=:purchase,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':purchase' => $condition['purchase'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief Possess profit report
     * @params $condition
     * @return array
     */
    public static function getPossessReport($condition)
    {
        $sql = "EXEC Z_P_PossessNetProfit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,@possessMan1=:possess";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':possess' => $condition['possess'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }



    /**
     * @brief EbaySales profit report
     * @params $condition
     * @return array
     */
    public static function getEbaySalesReport($condition)
    {
        $sql = "EXEC P_YR_PossessMan2Profit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@Sku='',@SalerName='',@SalerName2=0,@chanel='eBay',@SaleType='',@SalerAliasName='',@DevDate='',".
            "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief SalesTrend profit report
     * @params $condition
     * @return array
     */
    public static function getSalesTrendReport($condition)
    {
        $sql = 'call report_salesTrend(:store,:queryType,:showType,:dateFlag,:beginDate,:endDate)';
        $con = Yii::$app->db;
        $params = [
            ':store' => $condition['store'],
            ':queryType' => $condition['queryType'],
            ':showType' => $condition['showType'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    public static function getOrderCountReport($condition)
    {
        $sql = 'call report_orderCount(:store,:queryType,:showType,:dateFlag,:beginDate,:endDate)';
        $con = Yii::$app->db;
        $params = [
            ':store' => $condition['store'],
            ':queryType' => $condition['queryType'],
            ':showType' => $condition['showType'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief  profit report
     * @params $condition
     * @return array
     */
    public static function getProfitReport($condition)
    {
        $sql = "EXEC Z_P_AccountProductProfit @chanel=:chanel,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,".
        "@SalerAliasName=:suffix,@SalerName=:salesman,@StoreName=:storeName,@sku=:sku,@PageIndex=:PageIndex,@PageNum=:PageNum";
        $con = Yii::$app->py_db;
        $params = [
            ':chanel' => $condition['chanel'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':salesman' => $condition['salesman'],
            ':storeName' => $condition['storeName'],
            ':sku' => $condition['sku'],
            ':PageIndex' => $condition['start'],
            ':PageNum' => $condition['limit'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

    /**
     * @brief introduce report
     * @params $condition array
     * @return array
     */
    public static function getIntroduceReport($condition)
    {
        $sql = 'exec P_RefereeProfit_advanced @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,@SalerName=:salerName';
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':salerName' => $condition['member']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }
    }

    /**
     * @param $condition
     * @return array|ArrayDataProvider
     */
    public static function getRefundDetails($condition)
    {
        //美元汇率
        $rate = ApiUkFic::getRateUkOrUs('USD');
        $sql = '';

        //按订单汇总退款
        if ($condition['type'] === 'order') {
            $sql = 'SELECT rd.*,refund*' . $rate . " AS refundZn,u.username AS salesman 
                FROM (
                    SELECT MAX(refMonth) AS refMonth, MAX(dateDelta) as dateDelta, MAX(suffix) AS suffix,MAX(goodsName) AS goodsName,MAX(goodsCode) AS goodsCode,
				    MAX(goodsSku) AS goodsSku, MAX(tradeId) AS tradeId,orderId,MAX(storeName) AS storeName,
				    MAX(refund) AS refund, MAX(currencyCode) AS currencyCode,MAX(refundTime) AS refundTime,
				    MAX(orderTime) AS orderTime, MAX(orderCountry) AS orderCountry,MAX(platform) AS platform,MAX(expressWay) AS expressWay,refundId
                    FROM `cache_refund_details` 
                    WHERE refundTime between '{$condition['beginDate']}' AND DATE_ADD('{$condition['endDate']}', INTERVAL 1 DAY) 
                          AND IFNULL(platform,'')<>'' 
                    GROUP BY refundId,OrderId,refund,refundTime
                ) rd 
                LEFT JOIN auth_store s ON s.store=rd.suffix
                LEFT JOIN auth_store_child sc ON sc.store_id=s.id
                LEFT JOIN user u ON sc.user_id=u.id WHERE u.status=10 ";
            if ($condition['suffix']) {
                $sql .= ' AND suffix IN ('.$condition['suffix'] . ') ';
            }
            $sql .= ' ORDER BY refund DESC;';
        }

        //按SKU汇总退款
        if ($condition['type'] === 'goods') {
            $sql = 'SELECT rd.*,' . "u.username AS salesman 
                FROM (
                    SELECT suffix,goodsName,goodsCode,goodsSku,count(id) as times 
                    FROM `cache_refund_details` 
                    WHERE refundTime between '{$condition['beginDate']}' AND DATE_ADD('{$condition['endDate']}', INTERVAL 1 DAY)
                    GROUP BY suffix,goodsName,goodsCode,goodsSKu
                ) rd 
                LEFT JOIN auth_store s ON s.store=rd.suffix
                LEFT JOIN auth_store_child sc ON sc.store_id=s.id
                LEFT JOIN user u ON sc.user_id=u.id WHERE u.status=10 ";
            if ($condition['suffix']) {
                $sql .= 'AND suffix IN ('.$condition['suffix'] . ') ';
            }
            $sql .= 'ORDER BY times DESC';
        }

        $con = Yii::$app->db;
        try {
            $data = $con->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                    'page' => $condition['page'] -1,
                ],
            ]);

            return $provider;
        }
        catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @param $condition
     * @return array|ArrayDataProvider
     */
    public static function getDeadFee($condition)
    {
        $deadSql = "select suffix,storename, sum(diefeeZn) as total,dateTime 
                from (
                      select  suffix, storename,diefeeZn, convert(varchar(10),clearancedate,121)  as dateTime 
		              from y_offlineclearn 
                      where convert(varchar(10),clearancedate,121) between '" . $condition['beginDate'] . "' and '". $condition['endDate'] . "'";
        if ($condition['suffix']) $deadSql .= ' AND suffix IN ('.$condition['suffix'] . ') ';
        if ($condition['storename']) $deadSql .= ' AND storename IN ('.$condition['storename'] . ') ';
        $deadSql .= " ) ret GROUP by suffix, storename,dateTime;";

        $userSql = "SELECT s.store,s.platform,IFNULL(u.username,'未分配') AS username
                    FROM `auth_store` s 
                    LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                    LEFT JOIN `user` u ON u.id=sc.user_id
                    WHERE u.`status`=10 ";
        if ($condition['suffix']) $userSql .= ' AND store IN ('.$condition['suffix'] . ') ';

        try {
            $deadData = Yii::$app->py_db->createCommand($deadSql)->queryAll();
            $userData = Yii::$app->db->createCommand($userSql)->queryAll();
            $userData = ArrayHelper::map($userData,'store','username');
            $data = [];
            foreach ($deadData as $v){
                $item = $v;
                $item['salesman'] = isset($userData[$v['suffix']]) ? $userData[$v['suffix']] : '未分配';
                $data[] = $item;
            }
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);

            return $provider;
        }
        catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }
 /**
     * @param $condition
     * @return array|ArrayDataProvider
     */
    public static function getExtraFee($condition)
    {
        $sql = "SELECT suffix, SUM(saleOpeFeeZn) as saleOpeFeeZn, dateTime
                    FROM(
                        SELECT suffix, saleOpeFeeZn, saleOpeTime, CONVERT(varchar(10),saleOpeTime,121) as dateTime
                        FROM Y_saleOpeFee
                        WHERE CONVERT(varchar(10),saleOpeTime,121)  BETWEEN '" . $condition['beginDate'] . "' and '". $condition['endDate'] . "'";
        if ($condition['suffix']) $sql .= ' AND suffix IN ('.$condition['suffix'] . ') ';
        $sql .= " ) ret GROUP by suffix,dateTime;";

        $userSql = "SELECT s.store,s.platform,IFNULL(u.username,'未分配') AS username
                    FROM `auth_store` s 
                    LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                    LEFT JOIN `user` u ON u.id=sc.user_id
                    WHERE u.`status`=10 ";
        if ($condition['suffix']) $userSql .= ' AND store IN ('.$condition['suffix'] . ') ';
        try {
            $extraData = Yii::$app->py_db->createCommand($sql)->queryAll();
            $userData = Yii::$app->db->createCommand($userSql)->queryAll();
            $userData = ArrayHelper::map($userData,'store','username');
            $data = [];
            foreach ($extraData as $v){
                $item = $v;
                $item['salesman'] = isset($userData[$v['suffix']]) ? $userData[$v['suffix']] : '未分配';
                $data[] = $item;
            }
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);

            return $provider;
        }
        catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }


}