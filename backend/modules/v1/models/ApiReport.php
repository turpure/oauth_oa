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
        } catch (\Exception $why) {
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
        $sql = "EXEC P_DevNetprofit_advanced @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate," .
            "@Sku='',@SalerName=:seller,@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate=''," .
            "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':seller' => $condition['seller'],
        ];
        try {
            //return $con->createCommand($sql)->bindValues($params)->queryAll();
            $list = $con->createCommand($sql)->bindValues($params)->queryAll();
            //获取现有开发以及部门
            $userList = self::getAllDeveloper();
            $rateArr = Yii::$app->py_db->createCommand("select * from Y_Ratemanagement")->queryOne();
            $result = [];
            foreach ($list as $value) {
                $item = $value;
                foreach ($userList as $u) {
                    if($value['salernameZero'] === $u['username']){
                        if ($u['depart'] === '运营一部') {
                            $rate = $rateArr['devRate1'];
                        } elseif ($u['depart'] === '运营五部') {
                            $rate = $rateArr['devRate5'];
                        } else {
                            $rate = $rateArr['devRate'];
                        }
                        break;//跳出内层循环
                    }else{
                        $rate = $rateArr['devRate'];
                    }
                }
                //print_r($rate);exit;
                //重新计算各时间段销售额（￥）、pp交易费（￥）、毛利润、毛利率
                //0-6月
                $item['salemoneyrmbznZero'] *= $rate;
                $item['ppebayznZero'] *= $rate;
                $item['netprofitZero'] = $item['salemoneyrmbznZero'] - $item['costmoneyrmbZero'] - $item['ppebayznZero']
                    - $item['inpackagefeermbZero'] - $item['expressfarermbZero'] - $item['devofflinefeeZero'] - $item['devOpeFeeZero'];
                $item['netrateZero'] = $item['salemoneyrmbznZero'] == 0 ? 0 : round($item['netprofitZero']/$item['salemoneyrmbznZero'], 4)*100;
                //6-12月
                $item['salemoneyrmbznSix'] *= $rate;
                $item['ppebayznSix'] *= $rate;
                $item['netprofitSix'] = $item['salemoneyrmbznSix'] - $item['costmoneyrmbSix'] - $item['ppebayznSix']
                    - $item['inpackagefeermbSix'] - $item['expressfarermbSix'] - $item['devofflinefeeSix'] - $item['devOpeFeeSix'];
                $item['netrateSix'] = $item['salemoneyrmbznSix'] == 0 ? 0 : round($item['netprofitSix']/$item['salemoneyrmbznSix'], 4)*100;
                //12月以上
                $item['salemoneyrmbznTwe'] *= $rate;
                $item['ppebayznTwe'] *= $rate;
                $item['netprofitTwe'] = $item['salemoneyrmbznTwe'] - $item['costmoneyrmbTwe'] - $item['ppebayznTwe']
                    - $item['inpackagefeermbTwe'] - $item['expressfarermbTwe'] - $item['devofflinefeeTwe'] - $item['devOpeFeeTwe'];
                $item['netrateTwe'] = $item['salemoneyrmbznTwe'] == 0 ? 0 : round($item['netprofitTwe']/$item['salemoneyrmbznTwe'], 4)*100;
                //汇总
                $item['salemoneyrmbtotal'] *= $rate;
                $item['netprofittotal'] = $item['netprofitZero'] + $item['netprofitSix'] + $item['netprofitTwe'];
                $item['netratetotal'] = $item['salemoneyrmbtotal'] == 0 ? 0 : round($item['netprofittotal']/$item['salemoneyrmbtotal'], 4)*100;
                $result[] = $item;
            }
            //print_r($result);exit;
            return $result;
        } catch (\Exception $why) {
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
        $sql = "EXEC z_p_purchaserProfit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate," .
            "@Sku='',@SalerName='',@SalerName2='',@chanel='',@SaleType='',@SalerAliasName='',@DevDate=''," .
            "@DevDateEnd='',@Purchaser=:purchase,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':purchase' => $condition['purchase'],
        ];
        try {
            //return $con->createCommand($sql)->bindValues($params)->queryAll();
            $userList = self::getAllDeveloper();
            $list = $con->createCommand($sql)->bindValues($params)->queryAll();
            $purchaser = array_unique(ArrayHelper::getColumn($list,'purchaser'));//获取采购员数组并去重
            $rateArr = Yii::$app->py_db->createCommand("select * from Y_Ratemanagement")->queryOne();
            $result = $data = [];
            foreach ($list as $value) {
                $item = $value;
                foreach ($userList as $u) {
                    if($value['salerName'] === $u['username']){
                        if ($u['depart'] === '运营一部') {
                            $rate = $rateArr['devRate1'];
                        } elseif ($u['depart'] === '运营五部') {
                            $rate = $rateArr['devRate5'];
                        } else {
                            $rate = $rateArr['devRate'];
                        }
                        break;//跳出内层循环
                    }else{
                        $rate = $rateArr['devRate'];
                    }
                }
                //重新计算各时间段销售额（￥）、pp交易费（￥）
                $item['salemoneyrmbzn'] *= $rate;
                $item['ppebayzn'] *= $rate;
                $data[] = $item;
            }
            foreach ($purchaser as $value){
                $res['purchaser'] = $value;
                $res['salemoneyrmbus'] = $res['salemoneyrmbzn'] = $res['ppebayus'] = $res['ppebayzn'] =
                $res['costmoneyrmb'] = $res['expressfarermb'] = $res['inpackagefeermb'] = 0;
                foreach ($data as $v){
                    if($value === $v['purchaser']){
                        $res['salemoneyrmbus'] += $v['salemoneyrmbus'];
                        $res['salemoneyrmbzn'] += $v['salemoneyrmbzn'];
                        $res['ppebayus'] += $v['ppebayus'];
                        $res['ppebayzn'] += $v['ppebayzn'];
                        $res['costmoneyrmb'] += $v['costmoneyrmb'];
                        $res['expressfarermb'] += $v['expressfarermb'];
                        $res['inpackagefeermb'] += $v['inpackagefeermb'];
                        $res['devofflinefee'] = $v['devofflinefee'];
                        $res['devopefee'] = $v['devopefee'];
                        $res['totalamount'] = $v['totalamount'];
                    }
                }
                $res['netprofit'] = $res['salemoneyrmbzn'] - $res['ppebayzn'] - $res['costmoneyrmb']
                    - $res['expressfarermb'] - $res['inpackagefeermb'] - $res['devofflinefee'] - $res['devopefee'];
                $res['netrate'] = $res['salemoneyrmbzn'] == 0 ? 0 : round($res['netprofit']/$res['salemoneyrmbzn'], 4)*100;
                $result[] = $res;
            }
            return $result;
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
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
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /** 获取现有开发人员及部门
     * Date: 2019-05-17 14:44
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    private static function getAllDeveloper(){
        //获取现有开发以及部门
        $userSql = "SELECT u.username,CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart
                        FROM user u
                        LEFT JOIN auth_department_child dc ON dc.user_id=u.id
                        LEFT JOIN auth_department d ON d.id=dc.department_id
                        LEFT JOIN auth_department p ON p.id=d.parent
                        LEFT JOIN auth_assignment a ON a.user_id=u.id
                        WHERE u.`status`=10 AND a.item_name='产品开发'";
         return Yii::$app->db->createCommand($userSql)->queryAll();
    }


    /**
     * @brief EbaySales profit report
     * @params $condition
     * @return array
     */
    public static function getEbaySalesReport($condition)
    {
        $sql = "EXEC P_YR_PossessMan2Profit @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate," .
            "@Sku='',@SalerName='',@SalerName2=0,@chanel='eBay',@SaleType='',@SalerAliasName='',@DevDate=''," .
            "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
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
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /**
     * @brief profit Trend  report
     * @params $condition
     * @return array
     */
    public static function getProfitTrendReport($condition)
    {
        $sql = 'call report_profitTrend(:store,:queryType,:showType,:dateFlag,:beginDate,:endDate,:exchangeRate)';
        $con = Yii::$app->db;
        $params = [
            ':store' => $condition['store'],
            ':queryType' => $condition['queryType'],
            ':showType' => $condition['showType'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':exchangeRate' => $condition['exchangeRate']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /**
     * @brief 订单销量统计
     * @param $condition
     * @return array
     */
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
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /**
     * @brief SKU销量统计
     * @param $condition
     * @return array
     */

    public static function getSkuCountReport($condition)
    {
        $sql = 'call report_SkuCount(:store,:queryType,:showType,:dateFlag,:beginDate,:endDate)';
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
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /**
     * @brief  profit report
     * @params $condition
     * @return array
     */
    public static function getProfitReport($condition)
    {
        $sql = "EXEC Z_P_AccountProductProfit @chanel=:chanel,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate," .
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
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
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
        } catch (\Exception $why) {
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
				    MAX(goodsSku) AS goodsSku, MAX(tradeId) AS tradeId,orderId,mergeBillId,MAX(storeName) AS storeName,
				    MAX(refund) AS refund, MAX(currencyCode) AS currencyCode,MAX(refundTime) AS refundTime,
				    MAX(orderTime) AS orderTime, MAX(orderCountry) AS orderCountry,MAX(platform) AS platform,MAX(expressWay) AS expressWay,refundId
                    FROM `cache_refund_details` 
                    WHERE refundTime between '{$condition['beginDate']}' AND '" . $condition['endDate'] . " 23:59:59" . "' 
                          AND IFNULL(platform,'')<>'' 
                    GROUP BY refundId,OrderId,mergeBillId,refund,refundTime
                ) rd 
                LEFT JOIN auth_store s ON s.store=rd.suffix
                LEFT JOIN auth_store_child sc ON sc.store_id=s.id
                LEFT JOIN user u ON sc.user_id=u.id WHERE u.status=10 ";
            if ($condition['suffix']) {
                $sql .= ' AND suffix IN (' . $condition['suffix'] . ') ';
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
                $sql .= 'AND suffix IN (' . $condition['suffix'] . ') ';
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
                    'page' => $condition['page'] - 1,
                ],
            ]);
            return $provider;
        } catch (\Exception $why) {
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
        try {
            $deadSql = "SELECT * FROM oauth_salesOfflineClearn 
                      WHERE convert(VARCHAR(10),importDate,121) BETWEEN '" . $condition['beginDate'] . "' AND '" . $condition['endDate'] . "'";
            if ($condition['suffix']) $deadSql .= ' AND suffix IN (' . $condition['suffix'] . ') ';
            if ($condition['storename']) $deadSql .= ' AND storeName IN (' . $condition['storename'] . ') ';

            $userSql = "SELECT s.store,s.platform,IFNULL(u.username,'未分配') AS username
                    FROM `auth_store` s 
                    LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                    LEFT JOIN `user` u ON u.id=sc.user_id
                    WHERE u.`status`=10 ";
            if ($condition['suffix']) $userSql .= ' AND store IN (' . $condition['suffix'] . ') ';

            $deadData = Yii::$app->py_db->createCommand($deadSql)->queryAll();
            $userData = Yii::$app->db->createCommand($userSql)->queryAll();
            $userData = ArrayHelper::map($userData, 'store', 'username');
            $data = [];
            foreach ($deadData as $v) {
                $item = $v;
                $item['salesman'] = isset($userData[$v['suffix']]) ? $userData[$v['suffix']] : '未分配';
                $data[] = $item;
            }
            $totalAveAmount = array_sum(ArrayHelper::getColumn($data, 'aveAmount'));

            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);

            return ['provider' => $provider, 'extra' => ['totalAveAmount' => $totalAveAmount]];
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /** 其他死库明细
     * @param $condition
     * Date: 2019-04-04 10:07
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getOtherDeadFee($condition)
    {
        $deadSql = "SELECT * FROM oauth_otherOfflineClearn 
                      WHERE convert(VARCHAR(10),importDate,121) BETWEEN '" . $condition['beginDate'] . "' AND '" . $condition['endDate'] . "'";
        if ($condition['member']) {
            if ($condition['role'] == 'purchaser') {
                $deadSql .= ' AND purchaser IN (' . $condition['member'] . ') ';
            } elseif ($condition['role'] == 'possessMan') {
                $deadSql .= ' AND possessMan IN (' . $condition['member'] . ') ';
            } elseif ($condition['role'] == 'introducer') {
                $deadSql .= ' AND introducer IN (' . $condition['member'] . ') ';
            } else {
                $deadSql .= ' AND (developer IN (' . $condition['member'] . ') OR developer2 IN (' . $condition['member'] . ')) ';
            }
        } else {
            if ($condition['role'] == 'purchaser') {
                $deadSql .= " AND ISNULL(purchaser,'')<>'' ";
            } elseif ($condition['role'] == 'possessMan') {
                $deadSql .= " AND ISNULL(possessMan,'')<>'' ";
            } elseif ($condition['role'] == 'introducer') {
                $deadSql .= " AND ISNULL(introducer,'')<>'' ";
            } else {
                $deadSql .= " AND (ISNULL(developer,'')<>'' OR ISNULL(developer2,'')<>'') ";
            }
        }
        try {
            $data = Yii::$app->py_db->createCommand($deadSql)->queryAll();
            $totalAveAmount = array_sum(ArrayHelper::getColumn($data, 'aveAmount'));
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => isset($condition['pageSize']) && $condition['pageSize'] ? $condition['pageSize'] : 20,
                ],
            ]);
            return ['provider' => $provider, 'extra' => ['totalAveAmount' => $totalAveAmount]];
        } catch (\Exception $why) {
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
        $sql = "SELECT suffix, saleOpeFeeZn, comment, CONVERT(varchar(10),saleOpeTime,121) as dateTime
                FROM Y_saleOpeFee
                WHERE CONVERT(varchar(10),saleOpeTime,121)  BETWEEN '" . $condition['beginDate'] . "' and '" . $condition['endDate'] . "'";
        if ($condition['suffix']) $sql .= ' AND suffix IN (' . $condition['suffix'] . ') ';

        $userSql = "SELECT s.store,s.platform,IFNULL(u.username,'未分配') AS username
                    FROM `auth_store` s 
                    LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                    LEFT JOIN `user` u ON u.id=sc.user_id
                    WHERE u.`status`=10 ";
        if ($condition['suffix']) $userSql .= ' AND store IN (' . $condition['suffix'] . ') ';
        try {
            $extraData = Yii::$app->py_db->createCommand($sql)->queryAll();
            $userData = Yii::$app->db->createCommand($userSql)->queryAll();
            $userData = ArrayHelper::map($userData, 'store', 'username');
            $data = [];
            foreach ($extraData as $v) {
                $item = $v;
                $item['salesman'] = isset($userData[$v['suffix']]) ? $userData[$v['suffix']] : '未分配';
                $data[] = $item;
            }
            $totalAveAmount = array_sum(ArrayHelper::getColumn($data, 'aveAmount'));
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);

            return ['provider' => $provider, 'extra' => ['totalAveAmount' => $totalAveAmount]];
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * @brief 获取退款分析数据
     * @param $condition
     * @return array
     */
    public static function getRefundAnalysisData($condition)
    {
        return static::getRefundDetails($condition)->allModels;
    }

}