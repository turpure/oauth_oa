<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-06-12 14:22
 */

namespace backend\modules\v1\models;

use Yii;
use yii\data\ArrayDataProvider;
use yii\db\ActiveQuery;
use yii\db\Query;
use yii\helpers\ArrayHelper;
use backend\models\ShopElf\BDictionary;
use yii\data\ActiveDataProvider;

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
                    if ($value['salernameZero'] === $u['username']) {
                        if ($u['depart'] === '运营一部') {
                            $rate = $rateArr['devRate1'];
                        } elseif ($u['depart'] === '运营五部') {
                            $rate = $rateArr['devRate5'];
                        } else {
                            $rate = $rateArr['devRate'];
                        }
                        break;//跳出内层循环
                    } else {
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
                $item['netrateZero'] = $item['salemoneyrmbznZero'] == 0 ? 0 : round($item['netprofitZero'] / $item['salemoneyrmbznZero'], 4) * 100;
                //6-12月
                $item['salemoneyrmbznSix'] *= $rate;
                $item['ppebayznSix'] *= $rate;
                $item['netprofitSix'] = $item['salemoneyrmbznSix'] - $item['costmoneyrmbSix'] - $item['ppebayznSix']
                    - $item['inpackagefeermbSix'] - $item['expressfarermbSix'] - $item['devofflinefeeSix'] - $item['devOpeFeeSix'];
                $item['netrateSix'] = $item['salemoneyrmbznSix'] == 0 ? 0 : round($item['netprofitSix'] / $item['salemoneyrmbznSix'], 4) * 100;
                //12月以上
                $item['salemoneyrmbznTwe'] *= $rate;
                $item['ppebayznTwe'] *= $rate;
                $item['netprofitTwe'] = $item['salemoneyrmbznTwe'] - $item['costmoneyrmbTwe'] - $item['ppebayznTwe']
                    - $item['inpackagefeermbTwe'] - $item['expressfarermbTwe'] - $item['devofflinefeeTwe'] - $item['devOpeFeeTwe'];
                $item['netrateTwe'] = $item['salemoneyrmbznTwe'] == 0 ? 0 : round($item['netprofitTwe'] / $item['salemoneyrmbznTwe'], 4) * 100;
                //汇总
                $item['salemoneyrmbtotal'] *= $rate;
                $item['netprofittotal'] = $item['netprofitZero'] + $item['netprofitSix'] + $item['netprofitTwe'];
                $item['netratetotal'] = $item['salemoneyrmbtotal'] == 0 ? 0 : round($item['netprofittotal'] / $item['salemoneyrmbtotal'], 4) * 100;
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
            $purchaser = array_unique(ArrayHelper::getColumn($list, 'purchaser'));//获取采购员数组并去重
            $rateArr = Yii::$app->py_db->createCommand("select * from Y_Ratemanagement")->queryOne();
            $result = $data = [];
            foreach ($list as $value) {
                $item = $value;
                foreach ($userList as $u) {
                    if ($value['salerName'] === $u['username']) {
                        if ($u['depart'] === '运营一部') {
                            $rate = $rateArr['devRate1'];
                        } elseif ($u['depart'] === '运营五部') {
                            $rate = $rateArr['devRate5'];
                        } else {
                            $rate = $rateArr['devRate'];
                        }
                        break;//跳出内层循环
                    } else {
                        $rate = $rateArr['devRate'];
                    }
                }
                //重新计算各时间段销售额（￥）、pp交易费（￥）
                $item['salemoneyrmbzn'] *= $rate;
                $item['ppebayzn'] *= $rate;
                $data[] = $item;
            }
            foreach ($purchaser as $value) {
                $res['purchaser'] = $value;
                $res['salemoneyrmbus'] = $res['salemoneyrmbzn'] = $res['ppebayus'] = $res['ppebayzn'] =
                $res['costmoneyrmb'] = $res['expressfarermb'] = $res['inpackagefeermb'] = 0;
                foreach ($data as $v) {
                    if ($value === $v['purchaser']) {
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
                $res['netrate'] = $res['salemoneyrmbzn'] == 0 ? 0 : round($res['netprofit'] / $res['salemoneyrmbzn'], 4) * 100;
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
            $userList = self::getAllDeveloper();
            //print_r($con->createCommand($sql)->bindValues($params)->getRawSql());exit;
            $list = $con->createCommand($sql)->bindValues($params)->queryAll();
            $possess = array_unique(ArrayHelper::getColumn($list, 'possessman1Zero'));//获取美工数组并去重
            $rateArr = Yii::$app->py_db->createCommand("select * from Y_Ratemanagement")->queryOne();
            $result = $data = [];
            //return $con->createCommand($sql)->bindValues($params)->queryAll();
            foreach ($list as $value) {
                $item = $value;
                foreach ($userList as $u) {
                    if ($value['salerNameZero'] === $u['username']) {
                        if ($u['depart'] === '运营一部') {
                            $rate = $rateArr['devRate1'];
                        } elseif ($u['depart'] === '运营五部') {
                            $rate = $rateArr['devRate5'];
                        } else {
                            $rate = $rateArr['devRate'];
                        }
                        break;//跳出内层循环
                    } else {
                        $rate = $rateArr['devRate'];
                    }
                }
                //重新计算各时间段销售额（￥）、pp交易费（￥）
                //0-6月
                $item['salemoneyrmbznZero'] *= $rate;
                $item['ppebayznZero'] *= $rate;
                //6-12月
                $item['salemoneyrmbznSix'] *= $rate;
                $item['ppebayznSix'] *= $rate;
                //12月以上
                $item['salemoneyrmbznTwe'] *= $rate;
                $item['ppebayznTwe'] *= $rate;
                //汇总
                $item['salemoneyrmbtotal'] *= $rate;
                $data[] = $item;
            }
            //print_r(array_keys($data[0]));exit;
            foreach ($possess as $value) {
                $res = $list[0];
                array_walk($res, function (&$v,$k){$v = 0;});
                unset($res['salerNameZero']);
                $res['possessman1Zero'] = $value;
                $possessofflinefeeZero = $possessOpeFeeZero =
                $possessofflinefeeSix = $possessOpeFeeSix =
                $possessofflinefeeTwe = $possessOpeFeeTwe = 0;
                foreach ($data as $v) {
                    if ($value === $v['possessman1Zero']) {
                        $res['tableType'] = $v['tableType'];
                        $res['timegroupZero'] = $v['timegroupZero'];
                        $res['timegroupSix'] = $v['timegroupSix'];
                        $res['timegroupTwe'] = $v['timegroupTwe'];
                        //0-6月
                        $res['salemoneyrmbusZero'] += $v['salemoneyrmbusZero'];
                        $res['salemoneyrmbznZero'] += $v['salemoneyrmbznZero'];
                        $res['costmoneyrmbZero'] += $v['costmoneyrmbZero'];
                        $res['ppebayusZero'] += $v['ppebayusZero'];
                        $res['ppebayznZero'] += $v['ppebayznZero'];
                        $res['inpackagefeermbZero'] += $v['inpackagefeermbZero'];
                        $res['expressfarermbZero'] += $v['expressfarermbZero'];
                        $res['possessofflinefeeZero'] = $v['possessofflinefeeZero'];
                        $res['possessOpeFeeZero'] = $v['possessOpeFeeZero'];
                        //$possessofflinefeeZero = max($v['possessofflinefeeZero'],$possessofflinefeeZero);
                        //$possessOpeFeeZero = max($v['possessOpeFeeZero'],$possessOpeFeeZero);
                        //6-12月
                        $res['salemoneyrmbusSix'] += $v['salemoneyrmbusSix'];
                        $res['salemoneyrmbznSix'] += $v['salemoneyrmbznSix'];
                        $res['costmoneyrmbSix'] += $v['costmoneyrmbSix'];
                        $res['ppebayusSix'] += $v['ppebayusSix'];
                        $res['ppebayznSix'] += $v['ppebayznSix'];
                        $res['inpackagefeermbSix'] += $v['inpackagefeermbSix'];
                        $res['expressfarermbSix'] += $v['expressfarermbSix'];
                        $res['possessofflinefeeSix'] = $v['possessofflinefeeSix'];
                        $res['possessOpeFeeSix'] = $v['possessOpeFeeSix'];
                        //12月以上
                        $res['salemoneyrmbusTwe'] += $v['salemoneyrmbusTwe'];
                        $res['salemoneyrmbznTwe'] += $v['salemoneyrmbznTwe'];
                        $res['costmoneyrmbTwe'] += $v['costmoneyrmbTwe'];
                        $res['ppebayusTwe'] += $v['ppebayusTwe'];
                        $res['ppebayznTwe'] += $v['ppebayznTwe'];
                        $res['inpackagefeermbTwe'] += $v['inpackagefeermbTwe'];
                        $res['expressfarermbTwe'] += $v['expressfarermbTwe'];
                        $res['possessofflinefeeTwe'] = $v['possessofflinefeeTwe'];
                        $res['possessOpeFeeTwe'] = $v['possessOpeFeeTwe'];
                        //总计
                        $res['salemoneyrmbtotal'] += $v['salemoneyrmbtotal'];
                    }
                }
                //0-6月
                $res['netprofitZero'] = $res['salemoneyrmbznZero'] - $res['costmoneyrmbZero'] - $res['ppebayznZero']
                    - $res['inpackagefeermbZero'] - $res['expressfarermbZero'] - $res['possessofflinefeeZero'] - $res['possessOpeFeeZero'];
                $res['netrateZero'] = $res['salemoneyrmbznZero'] == 0 ? 0 : round($res['netprofitZero']/$res['salemoneyrmbznZero'], 4)*100;
                //6-12月
                $res['netprofitSix'] = $res['salemoneyrmbznSix'] - $res['costmoneyrmbSix'] - $res['ppebayznSix']
                    - $res['inpackagefeermbSix'] - $res['expressfarermbSix'] - $res['possessofflinefeeSix'] - $res['possessOpeFeeSix'];
                $res['netrateSix'] = $res['salemoneyrmbznSix'] == 0 ? 0 : round($res['netprofitSix']/$res['salemoneyrmbznSix'], 4)*100;
                //12月以上
                $res['netprofitTwe'] = $res['salemoneyrmbznTwe'] - $res['costmoneyrmbTwe'] - $res['ppebayznTwe']
                    - $res['inpackagefeermbTwe'] - $res['expressfarermbTwe'] - $res['possessofflinefeeTwe'] - $res['possessOpeFeeTwe'];
                $res['netrateTwe'] = $res['salemoneyrmbznTwe'] == 0 ? 0 : round($res['netprofitTwe']/$res['salemoneyrmbznTwe'], 4)*100;
                //总计
                $res['netprofittotal'] = $res['netprofitZero'] + $res['netprofitSix'] + $res['netprofitTwe'];
                $res['netratetotal'] = $res['salemoneyrmbtotal'] == 0 ? 0 : round($res['netprofittotal']/$res['salemoneyrmbtotal'], 4)*100;

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

    /** 获取现有开发人员及部门
     * Date: 2019-05-17 14:44
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    private static function getAllDeveloper()
    {
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

    /** profit report
     * @param $condition
     * Date: 2019-05-24 11:51
     * Author: henry
     * @return array|ArrayDataProvider
     */
    public static function getProfitReport($condition)
    {
        $salesman = $condition['salesman'] ? "'" . implode(',', $condition['salesman']) . "'" : '';
        $sql = "EXEC Z_P_AccountProductProfit @chanel=:chanel,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate," .
            "@SalerAliasName=:suffix,@SalerName=:salesman,@StoreName=:storeName,@sku=:sku,@PageIndex=:PageIndex,@PageNum=:PageNum";
        $params = [
            ':chanel' => $condition['chanel'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':salesman' => $salesman,
            ':storeName' => $condition['storeName'],
            ':sku' => $condition['sku'],
            ':PageIndex' => $condition['start'],
            ':PageNum' => $condition['limit'],
        ];
        try {
            //return Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
            $list = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
            $data = [];
            foreach ($list as $value){
                $item = $value;
                $saler = Yii::$app->db->createCommand("SELECT u.username,d.store AS suffix,d.platform
                        FROM user u
                        LEFT JOIN auth_store_child dc ON dc.user_id=u.id
                        LEFT JOIN auth_store d ON d.id=dc.store_id
                       WHERE u.`status`=10 AND d.store='{$value['suffix']}'")->queryOne();
                //print_r($saler);exit;
                if($saler && in_array($saler['username'], $condition['salesman'])){
                    $item['salesman'] = $saler['username'];
                    $item['pingtai'] = $saler['platform'];
                    $data[] = $item;
                }

            }
            return new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'page' => $condition['start'] - 1,
                    'pageSize' => isset($condition['limit']) && $condition['limit'] ? $condition['limit'] : 20,
                ],
            ]);

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
        $member = $condition['member'] ? implode(',', $condition['member']) : '';
        $sql = 'exec P_RefereeProfit_advanced @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,@SalerName=:salerName';
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':salerName' => $member
        ];
        try {
            //return Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();

            $list = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
            //插入MySql数据库进行进一步计算
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_introduceProfitTmp;")->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_introduceProfitTmp',
                ['goodsCode','salerName','createDate','costMoneyRmb','saleMoneyRmb','ppEbayRmb',
                    'inPackageFeeRmb','expressFareRmb','devRateUs','devRate','devRate1','devRate5'],
                $list
            )->execute();
            //获取初步计算结果
            $dataList = Yii::$app->db->createCommand("CALL report_introduceProfitTmp('{$condition['endDate']}');")->queryAll();
            //获取 运营费用
            $operateSql = "select dev1.salername as introducer,dev1.timegroup,sum(dev1.amount)as amount
                          from (
                              SELECT
                               CASE WHEN ISNULL(SalerName,'')='' THEN '无人' ELSE SalerName END AS SalerName,
                               timegroup,
                               sum(amount) as amount,
                               devOperateTime
                             FROM Y_devOperateFee
                             WHERE devOperateTime  BETWEEN '{$condition['beginDate']}'  AND '{$condition['endDate']}'
                             group by salername,timegroup,devOperateTime) dev1
                          group  by dev1.salername,dev1.timegroup";
            $operateList = Yii::$app->py_db->createCommand($operateSql)->queryAll();
            //获取 清仓
            $offlineSql = "select dev1.introducer,dev1.timegroup,sum(dev1.amount)as amount
                          from (
                            SELECT
                               CASE WHEN ISNULL(introducer,'')='' THEN '无人' ELSE introducer END AS introducer,
                               timegroup,
                               sum(amount) as amount,
                               clearnTime
                             FROM Y_introOfflineClearn
                             WHERE clearnTime  BETWEEN '{$condition['beginDate']}'  AND '{$condition['endDate']}'
                             group by introducer,timegroup,clearnTime) dev1
                          group  by dev1.introducer,dev1.timegroup";
            $offlineList = Yii::$app->py_db->createCommand($offlineSql)->queryAll();
            $data = [];
            foreach ($dataList as $value){
                $item = $value;
                $item['devOpeFeeZero'] = $item['devofflinefeeZero'] =
                $item['devOpeFeeSix'] = $item['devofflinefeeSix'] =
                $item['devOpeFeeTwe'] = $item['devofflinefeeTwe'] = 0;
                foreach ($operateList as $val){
                    if($value['salernameZero'] == $val['introducer'] && $value['timegroupZero'] == $val['timegroup']){
                        $item['devOpeFeeZero'] = $val['amount'];
                    }
                    if($value['salernameZero'] == $val['introducer'] && $value['timegroupSix'] == $val['timegroup']){
                        $item['devOpeFeeSix'] = $val['amount'];
                    }
                    if($value['salernameZero'] == $val['introducer'] && $value['timegroupTwe'] == $val['timegroup']){
                        $item['devOpeFeeTwe'] = $val['amount'];
                    }
                }
                foreach ($offlineList as $v){
                    if($value['salernameZero'] == $v['introducer'] && $value['timegroupZero'] == $v['timegroup']){
                        $item['devofflinefeeZero'] = $v['amount'];
                    }
                    if($value['salernameZero'] == $v['introducer'] && $value['timegroupSix'] == $v['timegroup']){
                        $item['devofflinefeeSix'] = $v['amount'];
                    }
                    if($value['salernameZero'] == $v['introducer'] && $value['timegroupTwe'] == $v['timegroup']){
                        $item['devofflinefeeTwe'] = $v['amount'];
                    }
                }
                //筛选推荐人
                if(!$condition['member'] || in_array($value['salernameZero'], $condition['member'])){
                    //0-6月
                    $item['netprofitZero'] = $item['salemoneyrmbznZero'] - $item['costmoneyrmbZero'] - $item['ppebayznZero']
                        - $item['inpackagefeermbZero'] - $item['expressfarermbZero'] - $item['devofflinefeeZero'] - $item['devOpeFeeZero'];
                    $item['netrateZero'] = $item['salemoneyrmbznZero'] == 0 ? 0 : round($item['netprofitZero']/$item['salemoneyrmbznZero'],4)*100;
                    //6-12月
                    $item['netprofitSix'] = $item['salemoneyrmbznSix'] - $item['costmoneyrmbSix'] - $item['ppebayznSix']
                        - $item['inpackagefeermbSix'] - $item['expressfarermbSix'] - $item['devofflinefeeSix'] - $item['devOpeFeeSix'];
                    $item['netrateSix'] = $item['salemoneyrmbznSix'] == 0 ? 0 : round($item['netprofitSix']/$item['salemoneyrmbznSix'],4)*100;
                    //12月以上
                    $item['netprofitTwe'] = $item['salemoneyrmbznTwe'] - $item['costmoneyrmbTwe'] - $item['ppebayznTwe']
                        - $item['inpackagefeermbTwe'] - $item['expressfarermbTwe'] - $item['devofflinefeeTwe'] - $item['devOpeFeeTwe'];
                    $item['netrateTwe'] = $item['salemoneyrmbznTwe'] == 0 ? 0 : round($item['netprofitTwe']/$item['salemoneyrmbznTwe'],4)*100;
                    //总计
                    $item['salemoneyrmbtotal'] = $item['salemoneyrmbznZero'] + $item['salemoneyrmbznSix'] + $item['salemoneyrmbznTwe'];
                    $item['netprofittotal'] = $item['netprofitZero'] + $item['netprofitSix'] +$item['netprofitTwe'];
                    $item['netratetotal'] = $item['salemoneyrmbtotal'] == 0 ? 0 : round($item['netprofittotal']/$item['salemoneyrmbtotal'],4)*100;
                    //print_r($item);exit;
                    $data[] = $item;
                }

            }

            //print_r($data);exit;
            return $data;
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

    /**
     * @brief 获取退款物流所占比例
     * @param $condition
     * @throws \Exception
     * @return mixed
     */
    public static function getRefundExpressRate($condition)
    {
        $suffix = $condition['account'];
        $dateFlag = $condition['dateType'];
        list($beginDate, $endDate) = $condition['dateRange'];
        $sql = 'call report_refundExPressRateAPI (:suffix, :beginDate, :endDate, :dateFlag)';
        $query = Yii::$app->db->createCommand($sql)->bindValues([
            ':suffix' => implode(',', $suffix),
            ':beginDate' => $beginDate,
            ':endDate' => $endDate,
            ':dateFlag' => $dateFlag
        ])->queryAll();
        return $query;


    }

    /**
     * @brief 获取开发数量限制表
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    public static function getDevLimit($condition)
    {
        $developer = $condition['developer'];
        list($beginDate, $endDate) = $condition['dateRange'];
        $dateFlag = $condition['dateType'];
        $sql = 'call report_devNumLimit (:developer,:beginDate,:endDate,:dateFlag)';
        $param = [
            ':developer' => implode(',', $developer),
            ':beginDate' => $beginDate,
            ':endDate' => $endDate,
            ':dateFlag' => $dateFlag
        ];
        $db = Yii::$app->db;
        return $db->createCommand($sql)->bindValues($param)->queryAll();
    }

    /**
     * @brief 获取开发产品利润
     * @param $condition
     * @return ActiveDataProvider
     */
    public static function getDevGoodsProfit($condition)
    {

        $developer = $condition['developer'];
        $goodsStatus = $condition['goodsStatus'];
        $dateRange = $condition['dateRange'];
        $dateFlag = $condition['dateType'];
        $sortField = isset($condition['sortField']) ? $condition['sortField'] : 'id';
        $sortOrder = isset($condition['sortOrder']) ? $condition['sortOrder'] : 'desc';
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $query = (new yii\db\Query())
            ->select('*')->from('cache_devGoodsProfit')
            ->where(['in','developer',$developer])
            ->andWhere(['in','goodsStatus', $goodsStatus])
            ->andWhere(['between','date_format(orderTime,"%Y-%m-%d")',$dateRange[0], $dateRange[1]])
            ->andWhere(['dateFlag' => $dateFlag]);
        $query->orderBy($sortField . ' ' . $sortOrder);
        $provider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 获取开发产品利润明细
     * @param $condition
     * @return array
     * @throws \Exception
     */
     public static function getDevGoodsProfitDetail($condition)
    {

        try {
        list($beginDate, $endDate) = $condition['dateRange'];
        $dateFlag = $condition['dateType'];
        $goodsCode = $condition['goodsCode'];
        $sql = 'call report_devGoodsProfitDetailAPI (:goodsCode,:beginDate,:endDate,:dateFlag)';
        $params = [':goodsCode' => $goodsCode,':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => $dateFlag];
        $db = Yii::$app->db;
        return $db->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            throw  new \Exception($why->getMessage(), '400');
        }
    }

    /**
     * @brief 获取开发状态
     * @return array
     */
    public static function getDevStatus()
    {
        $status = BDictionary::findAll(['CategoryID' => 15]);
        return ArrayHelper::getColumn($status,'DictionaryName');
    }

    /**
     * @brief 获取历史利润汇总表
     * @param $condition
     * @return array
     */
    public static function getHistorySalesProfit($condition)
    {
        $plat = isset($condition['plat']) ? $condition['plat'] : [];
        $salesMan = isset($condition['member']) ? $condition['member'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];
        $query = (new yii\db\Query())->select('*')->from('cache_historySalesProfit')->andWhere(['in','username',$salesMan])
        ->andWhere(['between','monthName',$beginDate, $endDate]);
        if(!empty($plat)) {
            $query->andWhere(['in','plat',$plat]);
        }
        $query = $query->orderBy('monthName asc')->all();
        $out = [];
        $monthList = static::getMonth($beginDate, $endDate);
        foreach ($query as $row) {
            $unique = $row['username'].'-'.$row['plat'];
            $ret = [];
            if (in_array($unique,ArrayHelper::getColumn($out,'unique'),true)) {
                foreach ($out as &$ele) {
                    if($ele['unique'] === $unique) {
                        $ele['historyProfit'][] = ['month' => $row['monthName'],'profit' =>$row['profit']];
                        $ele['historyRank'][] = ['month' => $row['monthName'],'rank' =>$row['rank']];
                        break;
                    }
                    }
                }

            else {
                $ret['unique'] = $unique;
                $ret['username'] = $row['username'];
                $ret['department'] = $row['department'];
                $ret['plat'] = $row['plat'];
                $ret['hireDate'] = $row['hireDate'];
                $ret['avgProfit'] = $row['avgProfit'];
                $ret['rank'] = $row['rank'];
                $ret['departmentTotal'] = $row['departmentTotal'];
                $ret['historyProfit'] = [['month' => $row['monthName'],'profit' =>$row['profit']]];
                $ret['historyRank'] = [['month' => $row['monthName'],'rank' =>$row['rank']]];
                $out[] = $ret;
            }

        }
        $historyProfit = [];
        $historyRank = [];
        foreach ($monthList as $month) {
            $row = [];
            $row['month'] = $month;
            $row['profit'] = 0;
            $historyProfit[] = $row;
        }

        foreach ($monthList as $month) {
            $row = [];
            $row['month'] = $month;
            $row['rank'] = 0;
            $historyRank[] = $row;
        }

        foreach ($out as  &$item) {
            $myHistoryProfit= $item['historyProfit'];
            $myHistoryRank= $item['historyRank'];
            $hProfit = $historyProfit;
            $hRank = $historyRank;

            //填充历史利润
            foreach($hProfit as &$pro) {
                foreach ($myHistoryProfit as $oldEle) {
                    if ($pro['month'] === $oldEle['month']) {
                        $pro['profit'] = $oldEle['profit'];
                    }
                }
            }

            //填充历史排名
            foreach($hRank as &$rank) {
                foreach ($myHistoryRank as $oldEle) {
                    if ($rank['month'] === $oldEle['month']) {
                        $rank['rank'] = $oldEle['rank'];
                    }
                }
            }

            $item['historyProfit'] = $hProfit;
            $item['historyRank'] = $hRank;


        }
        return $out;
    }

    /**
     * @brief 获取历史利润走势
     * @param $condition
     * @return array
     */
    public static function getHistoryProfit($condition)
    {
        $plat = isset($condition['plat']) ? $condition['plat'] : [];
        $salesMan = isset($condition['member']) ? $condition['member'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];
        $query = (new yii\db\Query())->select('username,plat,profit as profit, monthName,')->from('cache_historySalesProfit')->andWhere(['in','username',$salesMan])
            ->andWhere(['between','monthName',$beginDate, $endDate]);
        if(!empty($plat)) {
            $query->andWhere(['in','plat',$plat]);
        }
        $query = $query->orderBy('monthName desc')->all();
        $monthList = static::getMonth($beginDate, $endDate);
        $out = [];
        $userList = [];
        foreach ($query as $row) {
            $row['title'] = $row['username'] . '-' . $row['plat'];
            unset($row['username'],$row['plat']);
            $out[] = $row;
            if(!in_array($row['title'], $userList,true)) {
                $userList[] =  $row['title'];
            }
        }
        // 补充空数据
        $ret = [];
        foreach ($monthList as $month) {
            foreach ($userList as $title) {
                $ele = [];
                $ele['monthName'] = $month;
                $ele['title'] = $title ;
                $ele['profit'] = 0;
                $ret[] = $ele;
            }
        }

        //重新计算数据
        foreach ($ret as &$item) {
            foreach ($out as $row) {
                if ($row['monthName'] === $item['monthName'] && $row['title'] === $item['title']) {
                    $item['profit'] = $row['profit'];
                }
            }
        }
        return $ret;
    }

    /**
     * @brief 获取历史排名走势
     * @param $condition
     * @return array
     */
    public static function getHistoryRank($condition)
    {
        $plat = isset($condition['plat']) ? $condition['plat'] : [];
        $salesMan = isset($condition['member']) ? $condition['member'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];
        $query = (new yii\db\Query())->select('username,plat,rank, monthName,')
            ->from('cache_historySalesProfit')->andWhere(['in','username',$salesMan])
            ->andWhere(['between','monthName',$beginDate, $endDate]);
        if(!empty($plat)) {
            $query->andWhere(['in','plat',$plat]);
        }
        $query = $query->orderBy('monthName desc')->all();
        $monthList = static::getMonth($beginDate, $endDate);
        $out = [];
        $userList = [];
        foreach ($query as $row) {
            $row['title'] = $row['username'] . '-' . $row['plat'];
            unset($row['username'],$row['plat']);
            $out[] = $row;
            if(!in_array($row['title'], $userList,true)) {
                $userList[] =  $row['title'];
            }
        }

        // 补充空数据
        $ret = [];
        foreach ($monthList as $month) {
            foreach ($userList as $title) {
                $ele = [];
                $ele['monthName'] = $month;
                $ele['title'] = $title ;
                $ele['rank'] = 0;
                $ret[] = $ele;
            }
        }

        //重新计算数据
        foreach ($ret as &$item) {
            foreach ($out as $row) {
                if ($row['monthName'] === $item['monthName'] && $row['title'] === $item['title']) {
                    $item['rank'] = $row['rank'];
                }
            }
        }
        return $ret;
    }


    /**
     * @brief 获取月份
     * @param $start
     * @param $end
     * @return array
     */
    private static function getMonth($start, $end)
    {
        $month = strtotime($start);
        $end = strtotime($end);
        $ret = [];
        while ($month <= $end) {
            $thisMonth = date('Y-m', $month);
            $ret[] = $thisMonth;
            $month = strtotime('+1 month', $month);
        }
        return $ret;
    }
}