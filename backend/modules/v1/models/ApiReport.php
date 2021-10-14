<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-06-12 14:22
 */

namespace backend\modules\v1\models;

use backend\models\EbayRefund;
use backend\models\EbayStoreFee;
use backend\models\OauthClearPlan;
use backend\modules\v1\utils\ExportTools;
use MongoDB\BSON\UTCDateTime;
use Yii;
use yii\data\ActiveDataProvider;
use yii\data\ArrayDataProvider;
use yii\db\Exception;
use yii\helpers\ArrayHelper;
use backend\models\ShopElf\BDictionary;

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
        $sql = 'call report_salesProfit(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse,:exchangeRate, :wishExchangeRate);';
        $sqlParams = [
            ':dateType' => $condition['dateType'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':queryType' => $condition['queryType'],
            ':store' => $condition['store'],
            ':warehouse' => $condition['warehouse'],
            ':exchangeRate' => $condition['exchangeRate'],
            ':wishExchangeRate' => $condition['wishExchangeRate']
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
            "@DevDateEnd='',@Purchaser=0,@SupplierName=0,@possessMan1=0,@possessMan2=0,@flag=:flag";
        $con = Yii::$app->py_db;
        $params = [
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':seller' => $condition['seller'],
            ':flag' => isset($condition['flag']) && $condition['flag'] ? $condition['flag'] : 0,
        ];
        try {
//            return $con->createCommand($sql)->bindValues($params)->getRawSql();
            $list = $con->createCommand($sql)->bindValues($params)->queryAll();
            //获取现有开发以及部门
            $userList = self::getAllDeveloper();
            $result = [];
            foreach ($list as $value) {
                $item = $value;
                $rate = self::getDeveloperRate($userList, $value['salernameZero']);

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

    /** 开发毛利明细
     * @param $condition
     * Date: 2020-06-09 14:10
     * Author: henry
     * @return array
     */
    public static function getDevelopProfitDetailReport($condition)
    {

        $sql = "EXEC oauth_developer_sku_profit_detail @DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,@SalerName=:seller";
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
            $result = [];
            foreach ($list as $value) {
                $item = $value;
                $rate = self::getDeveloperRate($userList, $value['salerName']);

                //print_r($rate);exit;
                //重新计算各时间段销售额（￥）、pp交易费（￥）、毛利润、毛利率

                $item['saleMoneyRmbZn'] *= $rate;
                $item['ppEbayZn'] *= $rate;
                $item['profit'] = $item['saleMoneyRmbZn'] - $item['costMoneyRmb'] - $item['ppEbayZn']
                    - $item['packageFeeRmb'] - $item['expressFareRmb'];
                $item['rate'] = $item['saleMoneyRmbZn'] == 0 ? 0 : round($item['profit'] / $item['saleMoneyRmbZn'], 4) * 100;
                $result[] = $item;
            }
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
            $result = $data = [];
            foreach ($list as $value) {
                $item = $value;
                $rate = self::getDeveloperRate($userList, $value['salerName']);

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

    /**  获取开发汇率
     * getDeveloperRate
     * @param $userList
     * @param $username
     * Date: 2020-09-12 8:52
     * Author: henry
     * @return mixed
     */
    public static function getDeveloperRate($userList, $username)
    {
        $rateArr = Yii::$app->py_db->createCommand("select * from Y_Ratemanagement")->queryOne();
        $rate = 0;
        foreach ($userList as $u) {
            if ($username === $u['username']) {
                if ($u['departId'] == 1) {   //一部
                    $rate = $rateArr['devRate1'];
                } elseif ($u['departId'] == 4) {   //五部
                    $rate = $rateArr['devRate5'];
                } elseif ($u['departId'] == 40) { //七部
                    $rate = $rateArr['devRate7'];
                } else {
                    $rate = $rateArr['devRate'];
                }
                break;//跳出内层循环
            } else {
                $rate = $rateArr['devRate'];
            }
        }
        return $rate;
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
            $result = $data = [];
            //return $con->createCommand($sql)->bindValues($params)->queryAll();
            foreach ($list as $value) {
                $item = $value;
                $rate = self::getDeveloperRate($userList, $value['salerNameZero']);

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
                array_walk($res, function (&$v, $k) {
                    $v = 0;
                });
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
                $res['netrateZero'] = $res['salemoneyrmbznZero'] == 0 ? 0 : round($res['netprofitZero'] / $res['salemoneyrmbznZero'], 4) * 100;
                //6-12月
                $res['netprofitSix'] = $res['salemoneyrmbznSix'] - $res['costmoneyrmbSix'] - $res['ppebayznSix']
                    - $res['inpackagefeermbSix'] - $res['expressfarermbSix'] - $res['possessofflinefeeSix'] - $res['possessOpeFeeSix'];
                $res['netrateSix'] = $res['salemoneyrmbznSix'] == 0 ? 0 : round($res['netprofitSix'] / $res['salemoneyrmbznSix'], 4) * 100;
                //12月以上
                $res['netprofitTwe'] = $res['salemoneyrmbznTwe'] - $res['costmoneyrmbTwe'] - $res['ppebayznTwe']
                    - $res['inpackagefeermbTwe'] - $res['expressfarermbTwe'] - $res['possessofflinefeeTwe'] - $res['possessOpeFeeTwe'];
                $res['netrateTwe'] = $res['salemoneyrmbznTwe'] == 0 ? 0 : round($res['netprofitTwe'] / $res['salemoneyrmbznTwe'], 4) * 100;
                //总计
                $res['netprofittotal'] = $res['netprofitZero'] + $res['netprofitSix'] + $res['netprofitTwe'];
                $res['netratetotal'] = $res['salemoneyrmbtotal'] == 0 ? 0 : round($res['netprofittotal'] / $res['salemoneyrmbtotal'], 4) * 100;

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
        $userSql = "SELECT u.username,CASE WHEN IFNULL(p.department,'')<>'' THEN p.department ELSE d.department END as depart,
                        CASE WHEN IFNULL(p.id,'')<>'' THEN p.id ELSE d.id END as departId
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
            $ret = $con->createCommand($sql)->bindValues($params)->queryAll();
            return !empty($ret) ? $ret : [];
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
     * Date: 2019-10-11 15:39
     * Author: henry
     * @return array|ArrayDataProvider
     * @throws \yii\db\Exception
     */
    public static function getProfitReport($condition)
    {
        $sql = "CALL report_suffixSkuProfit(:dateFlag,:beginDate,:endDate,:devBeginDate,:devEndDate,:chanel,:suffix,:salesman,:storeName,:sku,:goodsName)";
        $params = [
            ':chanel' => $condition['chanel'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':devBeginDate' => $condition['devBeginDate'],
            ':devEndDate' => $condition['devEndDate'],
            ':suffix' => $condition['suffix'],
            ':salesman' => $condition['salesman'],
            ':storeName' => $condition['storeName'],
            ':sku' => $condition['sku'],
            ':goodsName' => $condition['goodsName'],
        ];
        $key = Yii::$app->db->createCommand($sql)->bindValues($params)->getRawSql();
        //获取缓存
        $res = Yii::$app->cache->get($key);
//        if($res){
//            $list = $res;
//        }else{
        $list = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
//            Yii::$app->cache->set($key, $list, 3600*12);
//        }
        try {
            $provider = new ArrayDataProvider([
                'allModels' => $list,
                'sort' => [
                    'attributes' => [
                        'salesman', 'suffix', 'pingtai', 'GoodsCode', 'GoodsName', 'SalerName',
                        'storeName', 'SKUQty', 'SaleMoneyRmb', 'refund', 'ProfitRmb', 'rate', 'refundRate'
                    ],
                ],
                'pagination' => [
                    'page' => $condition['start'] - 1,
                    'pageSize' => isset($condition['limit']) && $condition['limit'] ? $condition['limit'] : 20,
                ],
            ]);
            $totalQty = array_sum(ArrayHelper::getColumn($list, 'SKUQty'));
            $totalSaleMoney = round(array_sum(ArrayHelper::getColumn($list, 'SaleMoneyRmb')), 2);
            $totalRefund = round(array_sum(ArrayHelper::getColumn($list, 'refund')), 2);
            $totalProfitRmb = round(array_sum(ArrayHelper::getColumn($list, 'ProfitRmb')), 2);
            $totalRate = $totalSaleMoney == 0 ? 0 : round($totalProfitRmb / $totalSaleMoney * 100, 2);
            $totalRefundRate = $totalProfitRmb == 0 ? 0 : round($totalRefund / $totalProfitRmb * 100, 2);
            return [
                'provider' => $provider,
                'extra' => [
                    'totalQty' => $totalQty,
                    'totalSaleMoney' => $totalSaleMoney,
                    'totalRefund' => $totalRefund,
                    'totalProfitRmb' => $totalProfitRmb,
                    'totalRate' => $totalRate,
                    'totalRefundRate' => $totalRefundRate,
                ]
            ];

        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }

    }

    /** 账号产品毛利导出
     * @param $condition
     * Date: 2019-10-14 9:41
     * Author: henry
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getProfitReportExport($condition)
    {
        $sql = "CALL report_suffixSkuProfit(:dateFlag,:beginDate,:endDate,:devBeginDate,:devEndDate,:chanel,:suffix,:salesman,:storeName,:sku,:goodsName)";
        $params = [
            ':chanel' => $condition['chanel'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            'devBeginDate' => isset($condition['devBeginDate']) ? $condition['devBeginDate'] : '',
            'devEndDate' => isset($condition['devEndDate']) ? $condition['devEndDate'] : '',
            ':suffix' => $condition['suffix'],
            ':salesman' => $condition['salesman'],
            ':storeName' => $condition['storeName'],
            ':sku' => $condition['sku'],
            ':goodsName' => $condition['goodsName'],
        ];
        $key = Yii::$app->db->createCommand($sql)->bindValues($params)->getRawSql();

        $list = Yii::$app->cache->get($key);
        if (!$list) {
            $list = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
        }
        try {
            //["suffix","pingtai", "GoodsCode","GoodsName", "SalerName", "SKUQty", "SaleMoneyRmb","ProfitRmb", "rate","salesman"];
            $title = ['销售员', '卖家简称', '平台', '商品编码', '主图', '商品名称', '开发员', '开发日期', '仓库', '销量', '销售额￥', '退款￥', '利润￥', '利润率%', '退款利润占比'];
            return [$title, $list];

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
                ['goodsCode', 'salerName', 'createDate', 'costMoneyRmb', 'saleMoneyRmb', 'ppEbayRmb',
                    'inPackageFeeRmb', 'expressFareRmb', 'devRateUs', 'devRate', 'devRate1', 'devRate5', 'devRate7'],
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
            foreach ($dataList as $value) {
                $item = $value;
                $item['devOpeFeeZero'] = $item['devofflinefeeZero'] =
                $item['devOpeFeeSix'] = $item['devofflinefeeSix'] =
                $item['devOpeFeeTwe'] = $item['devofflinefeeTwe'] = 0;
                foreach ($operateList as $val) {
                    if ($value['salernameZero'] == $val['introducer'] && $value['timegroupZero'] == $val['timegroup']) {
                        $item['devOpeFeeZero'] = $val['amount'];
                    }
                    if ($value['salernameZero'] == $val['introducer'] && $value['timegroupSix'] == $val['timegroup']) {
                        $item['devOpeFeeSix'] = $val['amount'];
                    }
                    if ($value['salernameZero'] == $val['introducer'] && $value['timegroupTwe'] == $val['timegroup']) {
                        $item['devOpeFeeTwe'] = $val['amount'];
                    }
                }
                foreach ($offlineList as $v) {
                    if ($value['salernameZero'] == $v['introducer'] && $value['timegroupZero'] == $v['timegroup']) {
                        $item['devofflinefeeZero'] = $v['amount'];
                    }
                    if ($value['salernameZero'] == $v['introducer'] && $value['timegroupSix'] == $v['timegroup']) {
                        $item['devofflinefeeSix'] = $v['amount'];
                    }
                    if ($value['salernameZero'] == $v['introducer'] && $value['timegroupTwe'] == $v['timegroup']) {
                        $item['devofflinefeeTwe'] = $v['amount'];
                    }
                }
                //筛选推荐人
                if (!$condition['member'] || in_array($value['salernameZero'], $condition['member'])) {
                    //0-6月
                    $item['netprofitZero'] = $item['salemoneyrmbznZero'] - $item['costmoneyrmbZero'] - $item['ppebayznZero']
                        - $item['inpackagefeermbZero'] - $item['expressfarermbZero'] - $item['devofflinefeeZero'] - $item['devOpeFeeZero'];
                    $item['netrateZero'] = $item['salemoneyrmbznZero'] == 0 ? 0 : round($item['netprofitZero'] / $item['salemoneyrmbznZero'], 4) * 100;
                    //6-12月
                    $item['netprofitSix'] = $item['salemoneyrmbznSix'] - $item['costmoneyrmbSix'] - $item['ppebayznSix']
                        - $item['inpackagefeermbSix'] - $item['expressfarermbSix'] - $item['devofflinefeeSix'] - $item['devOpeFeeSix'];
                    $item['netrateSix'] = $item['salemoneyrmbznSix'] == 0 ? 0 : round($item['netprofitSix'] / $item['salemoneyrmbznSix'], 4) * 100;
                    //12月以上
                    $item['netprofitTwe'] = $item['salemoneyrmbznTwe'] - $item['costmoneyrmbTwe'] - $item['ppebayznTwe']
                        - $item['inpackagefeermbTwe'] - $item['expressfarermbTwe'] - $item['devofflinefeeTwe'] - $item['devOpeFeeTwe'];
                    $item['netrateTwe'] = $item['salemoneyrmbznTwe'] == 0 ? 0 : round($item['netprofitTwe'] / $item['salemoneyrmbznTwe'], 4) * 100;
                    //总计
                    $item['salemoneyrmbtotal'] = $item['salemoneyrmbznZero'] + $item['salemoneyrmbznSix'] + $item['salemoneyrmbznTwe'];
                    $item['netprofittotal'] = $item['netprofitZero'] + $item['netprofitSix'] + $item['netprofitTwe'];
                    $item['netratetotal'] = $item['salemoneyrmbtotal'] == 0 ? 0 : round($item['netprofittotal'] / $item['salemoneyrmbtotal'], 4) * 100;
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

    /** 托管前退款
     * @param $condition
     * @return array|ArrayDataProvider
     */
    public static function getRefundDetails($condition)
    {
        //美元汇率
        $rate = ApiUkFic::getRateUkOrUs('USD');
        $exchangeRate = ApiSettings::getExchangeRate();
        $wishSalerRate = $exchangeRate['wishSalerRate'];
        $sql = '';

        //按订单汇总退款
        if ($condition['type'] === 'order') {
            $sql = 'SELECT rd.*,refund* (case when s.platform=' . "'Wish'" . ' and ows.localCurrency=' . "'CNY'" . ' then ' . $wishSalerRate . ' else ' . $rate . " end)   AS refundZn,u.username AS salesman 
                FROM (
                    SELECT MAX(refMonth) AS refMonth, MAX(dateDelta) as dateDelta, MAX(suffix) AS suffix,
                    MAX(goodsName) AS goodsName,MAX(goodsCode) AS goodsCode,MAX(goodsSku) AS goodsSku, 
				    MAX(tradeId) AS tradeId,orderId,mergeBillId,MAX(storeName) AS storeName,MAX(refund) AS refund, 
				    MAX(currencyCode) AS currencyCode,MAX(refundTime) AS refundTime,MAX(orderTime) AS orderTime, 
				    MAX(orderCountry) AS orderCountry,MAX(platform) AS platform,MAX(expressWay) AS expressWay,refundId,type
                    FROM `cache_refund_details` 
                    WHERE refundTime between '{$condition['beginDate']}' AND '" . $condition['endDate'] . " 23:59:59" . "' 
                          AND IFNULL(platform,'')<>'' 
                    GROUP BY refundId,OrderId,mergeBillId,refund,refundTime,type
                ) rd 
                left join  proCenter.oa_wishSuffix as ows on ows.shortName =  rd.suffix
                LEFT JOIN auth_store s ON s.store=rd.suffix
                LEFT JOIN auth_store_child sc ON sc.store_id=s.id
                LEFT JOIN user u ON sc.user_id=u.id WHERE u.status=10 ";
            if ($condition['suffix']) {
                $sql .= ' AND rd.suffix IN (' . $condition['suffix'] . ') ';
            }
            $sql .= ' ORDER BY refund DESC,goodsSku ASC;';
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
            $sql .= 'ORDER BY times DESC,goodsSku ASC';
        }

        $con = Yii::$app->db;
        try {
            $data = $con->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);
            $totalRefundZn = round(array_sum(ArrayHelper::getColumn($data, 'refundZn')), 2);
            $totalRefundUs = round(array_sum(ArrayHelper::getColumn($data, 'refund')), 2);
            return ['provider' => $provider, 'extra' => ['totalRefundZn' => $totalRefundZn, 'totalRefundUs' => $totalRefundUs]];
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /** 托管后退款
     * getEbayRefundDetails
     * @param $condition
     * Date: 2021-08-17 17:54
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function getEbayRefundDetails($condition)
    {
        $rate = ApiUkFic::getRateUkOrUs('USD');
        $sql = "SELECT rd.*, refund * {$rate} AS refundZn,u.username AS salesman 
                FROM (
                    SELECT MAX(refMonth) AS refMonth, MAX(dateDelta) as dateDelta, MAX(suffix) AS suffix,
                    MAX(goodsName) AS goodsName,MAX(goodsCode) AS goodsCode,MAX(goodsSku) AS goodsSku, 
                    MAX(tradeId) AS tradeId,orderId,mergeBillId,MAX(storeName) AS storeName, MAX(refund) AS refund, 
				    MAX(currencyCode) AS currencyCode,MAX(refundTime) AS refundTime, MAX(orderTime) AS orderTime, 
				    MAX(orderCountry) AS orderCountry,MAX(platform) AS platform,MAX(expressWay) AS expressWay,
				    refundId
				    -- ,MAX(refundZn) AS refundZn
                    -- FROM `cache_refund_details_ebay_new` 
                    FROM `cache_refund_details` 
                    WHERE refundTime between '{$condition['beginDate']}' AND '" . $condition['endDate'] . " 23:59:59" . "' 
                          AND IFNULL(platform,'')='eBay' and type = '托管后'
                    GROUP BY refundId,OrderId,mergeBillId,refund,refundTime
                ) rd 
                LEFT JOIN auth_store s ON s.store=rd.suffix
                LEFT JOIN auth_store_child sc ON sc.store_id=s.id
                LEFT JOIN user u ON sc.user_id=u.id WHERE u.status=10 ";
        if ($condition['suffix']) {
            $sql .= 'AND suffix IN (' . $condition['suffix'] . ') ';
        }
        $sql .= 'ORDER BY refund DESC,goodsSku ASC';
//        $data = Yii::$app->db->createCommand($sql)->getRawSql();
//        var_dump($data);exit;
        $data = Yii::$app->db->createCommand($sql)->queryAll();

        try {
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);
            $totalRefundZn = round(array_sum(ArrayHelper::getColumn($data, 'refundZn')), 2);
            $totalRefundUs = round($totalRefundZn / $rate, 2);
            return ['provider' => $provider, 'extra' => ['totalRefundZn' => $totalRefundZn, 'totalRefundUs' => $totalRefundUs]];
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /** 托管后店铺杂费
     * getEbayRefundDetails
     * @param $condition
     * Date: 2021-08-17 17:54
     * Author: henry
     * @return array
     * @throws Exception
     */
    public static function getEbayStoreFee($condition)
    {
        $beginDate = $condition['beginDate'];
        $endDate = $condition['endDate'];
        $suffix = $condition['suffix'];
        $usRate = ApiUkFic::getRateUkOrUs('USD');
        $gbpRate = ApiUkFic::getRateUkOrUs('GBP');
        $data = EbayStoreFee::getCollection()->aggregate([
            [
                '$match' => [
                    'suffix' => ['$in' => array_values($suffix)],
                    'transactionDate' => ['$gte' => $beginDate, '$lte' => $endDate]
                ]
            ],
            [
                '$group' => [
                    '_id' => ['suffix' => '$suffix', 'currency' => '$currency', 'feeType' => '$feeType'],
                    'sum' => ['$sum' => ['$toDouble' => '$amountValue']]
                ]
            ],
            [
                '$project' => [
                    '_id' => 0, 'suffix' => '$_id.suffix', 'currency' => '$_id.currency', 'feeType' => '$_id.feeType', 'sum' => '$sum'
                ]
            ]
        ]);
//        $suffixArr = ArrayHelper::getColumn($data, 'sum');
//        var_dump($suffixArr);exit;
        $res = [];
        $totalFeeUs = $totalFeeGbp = 0;
        foreach ($data as $v) {
            $sql = "SELECT username FROM `user` u
                    LEFT JOIN auth_store_child l ON l.user_id = u.id
                    LEFT JOIN auth_store s ON l.store_id = s.id
                    WHERE s.store = '{$v['suffix']}' ";
            $item['salerman'] = Yii::$app->db->createCommand($sql)->queryScalar();
            $item['suffix'] = $v['suffix'];
            $item['feeType'] = $v['feeType'];
            $item['currency'] = $v['currency'];
            $item['value'] = $v['sum'];

//            $item['valueZn'] = $v['sum'] * ($v['currency'] == 'USD' ? $usRate : ($v['currency'] == 'GBP' ? $gbpRate : ApiUkFic::getRateUkOrUs($v['currency'])));
            if ($v['currency'] == 'USD') {
                $item['valueZn'] = $v['sum'] * $usRate;
                $totalFeeUs += $v['sum'];
            } elseif ($v['currency'] == 'GBP') {
                $item['valueZn'] = $v['sum'] * $gbpRate;
                $totalFeeGbp += $v['sum'];
            } else {
                $item['valueZn'] = $v['sum'] * ApiUkFic::getRateUkOrUs($v['currency']);
            }
            $item['valueZn'] = round($item['valueZn'], 2);
            $res[] = $item;
        }
        try {
            $provider = new ArrayDataProvider([
                'allModels' => $res,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);
            $totalFeeZn = round(array_sum(ArrayHelper::getColumn($res, 'valueZn')), 2);
            $totalFeeUs = round($totalFeeUs, 2);
            $totalFeeGbp = round($totalFeeGbp, 2);
            return ['provider' => $provider, 'extra' => ['totalFeeZn' => $totalFeeZn, 'totalFeeUs' => $totalFeeUs, 'totalFeeGbp' => $totalFeeGbp]];
        } catch (\Exception $why) {
            return [
                'code' => 400,
                'message' => $why->getMessage()
            ];
        }
    }

    /**
     * wish 退款明细
     * @param $condition
     * @return array|ArrayDataProvider
     */
    public static function getWishRefundDetails($condition)
    {
        //美元汇率
        $rate = ApiUkFic::getRateUkOrUs('USD');
        $sql = '';

        //按订单汇总退款
        if ($condition['type'] === 'wishOrder') {
            $sql = "SELECT rd.*,u.username AS salesman 
                FROM (
                    SELECT MAX(refMonth) AS refMonth, MAX(dateDelta) as dateDelta, MAX(suffix) AS suffix,MAX(goodsName) AS goodsName,MAX(goodsCode) AS goodsCode,
				    MAX(goodsSku) AS goodsSku, MAX(tradeId) AS tradeId,orderId,mergeBillId,MAX(storeName) AS storeName,
				    MAX(refund) AS refund, MAX(currencyCode) AS currencyCode,MAX(refundTime) AS refundTime,
				    MAX(orderTime) AS orderTime, MAX(orderCountry) AS orderCountry,MAX(platform) AS platform,MAX(expressWay) AS expressWay,refundId
                    FROM `cache_refund_details_wish` 
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
            $sql .= ' ORDER BY refund DESC,goodsSku ASC;';
        }

        $con = Yii::$app->db;
        try {
            $data = $con->createCommand($sql)->queryAll();
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);
            $totalRefundZn = 0;
            $totalRefundUs = 0;
            foreach ($data as $row) {
                if ($row['currencyCode'] == 'USD') {
                    $totalRefundUs += $row['refund'];
                }
                if ($row['currencyCode'] == 'CNY') {
                    $totalRefundZn += $row['refund'];
                }
            }
            return ['provider' => $provider, 'extra' => ['totalRefundZn' => round($totalRefundZn, 2), 'totalRefundUs' => round($totalRefundUs, 2)]];
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

    public static function getTrusteeshipFee($condition)
    {
        $sql = "SELECT notename as suffix,fee_type,total,CONVERT(DECIMAL(6,2),total * ExchangeRate) AS totalRmb,
                        currency_code,fee_time,orderId
                FROM [dbo].[y_fee] LEFT JOIN B_CurrencyCode  ON currency_code=CURRENCYCODE
                WHERE fee_type='CreditCard' AND 
                CONVERT(varchar(10),fee_time,121)  BETWEEN '" . $condition['beginDate'] . "' and '" . $condition['endDate'] . "'";
        if ($condition['suffix']) $sql .= ' AND notename IN (' . $condition['suffix'] . ') ';

        $userSql = "SELECT s.store,s.platform,IFNULL(u.username,'未分配') AS username
                    FROM `auth_store` s 
                    LEFT JOIN `auth_store_child` sc ON s.id=sc.store_id
                    LEFT JOIN `user` u ON u.id=sc.user_id
                    WHERE u.`status`=10 ";
        if ($condition['suffix']) $userSql .= ' AND store IN (' . $condition['suffix'] . ') ';
        try {
            $dataTmp = Yii::$app->py_db->createCommand($sql)->queryAll();
            $userData = Yii::$app->db->createCommand($userSql)->queryAll();
            $userData = ArrayHelper::map($userData, 'store', 'username');
            $data = [];
            foreach ($dataTmp as $v) {
                $item = $v;
                $item['salesman'] = isset($userData[$v['suffix']]) ? $userData[$v['suffix']] : '未分配';
                $data[] = $item;
            }
            $totalAmount = array_sum(ArrayHelper::getColumn($data, 'total'));
            $totalAmountRmb = array_sum(ArrayHelper::getColumn($data, 'totalRmb'));
            $provider = new ArrayDataProvider([
                'allModels' => $data,
                'pagination' => [
                    'pageSize' => $condition['pageSize'],
                ],
            ]);

            return ['provider' => $provider, 'extra' => ['totalAmount' => round($totalAmount, 2), 'totalAmountRmb' => round($totalAmountRmb, 2)]];
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
            $totalAveAmount = array_sum(ArrayHelper::getColumn($data, 'saleOpeFeeZn'));
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
     * @return mixed
     * @throws \Exception
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
     * @brief 获取账号退款率
     * @param $condition
     * @return array
     * @throws \yii\db\Exception
     */
    public static function getRefundSuffixRate($condition)
    {
        $suffix = $condition['account'];
        $dateFlag = $condition['dateType'];
        list($beginDate, $endDate) = $condition['dateRange'];
        $sql = 'call report_refundSuffixRateAPI (:suffix, :beginDate, :endDate, :dateFlag)';
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
        $minNumber = isset($condition['minNumber']) && !empty($condition['minNumber']) ? $condition['minNumber'] : 200;
        $minAvgNumber = isset($condition['minAvgNumber']) && !empty($condition['minNumber']) ? $condition['minNumber'] : 300;
        $sql = 'call report_devNumLimit (:developer,:beginDate,:endDate,:dateFlag, :minNumber, :minAvgNumber)';
        $param = [
            ':developer' => implode(',', $developer),
            ':beginDate' => $beginDate,
            ':endDate' => $endDate,
            ':dateFlag' => $dateFlag,
            ':minNumber' => $minNumber,
            ':minAvgNumber' => $minAvgNumber,
        ];
        $db = Yii::$app->db;
        return $db->createCommand($sql)->bindValues($param)->queryAll();
    }

    /**
     * @brief 获取开发产品利润
     * @param $condition
     * @return ArrayDataProvider
     * @throws \Exception
     */
    public static function getDevGoodsProfit($condition)
    {
        $developer = isset($condition['developer']) ? $condition['developer'] : [];
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        $introducer = isset($condition['introducer']) ? $condition['introducer'] : '';
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        list($beginDate, $endDate) = $condition['dateRange'];
        list($devBeginDate, $devEndDate) = isset($condition['devDateRange']) && $condition['devDateRange'] ? $condition['devDateRange'] : ['', ''];
        $dateFlag = $condition['dateType'];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $sql = 'call report_devGoodsProfitAPI (:developer,:introducer,:goodsCode,:goodsStatus, :beginDate, :endDate, :dateFlag, :devBeginDate, :devEndDate)';
        $params = [':developer' => implode(',', $developer), ':introducer' => $introducer,
            ':goodsCode' => $goodsCode, ':goodsStatus' => implode(',', $goodsStatus),
            ':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => (int)$dateFlag,
            ':devBeginDate' => $devBeginDate, ':devEndDate' => $devEndDate,];
        $query = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $query,
            'sort' => ['attributes' =>
                [
                    'developer', 'introducer', 'goodsCode', 'goodsName', 'devDate', 'goodsStatus',
                    'sold', 'amt', 'profit', 'rate', 'ebaySold', 'ebayProfit',
                    'wishSold', 'wishProfit', 'smtSold', 'smtProfit',
                    'joomSold', 'joomProfit', 'amazonSold', 'amazonProfit',
                    'vovaSold', 'vovaProfit', 'lazadaSold', 'lazadaProfit'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /**
     * @brief 获取开发汇率产品利润
     * @param $condition
     * @return ArrayDataProvider
     * @throws \Exception
     */
    public static function getDevRateGoodsProfit($condition)
    {
        $developer = isset($condition['developer']) ? $condition['developer'] : [];
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        $introducer = isset($condition['introducer']) ? $condition['introducer'] : '';
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        list($beginDate, $endDate) = $condition['dateRange'];
        list($devBeginDate, $devEndDate) = isset($condition['devDateRange']) && $condition['devDateRange'] ? $condition['devDateRange'] : ['', ''];
        $dateFlag = $condition['dateType'];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $sql = 'call report_devRateGoodsProfitAPI (:developer,:introducer,:goodsCode,:goodsStatus, :beginDate, :endDate, :dateFlag, :devBeginDate, :devEndDate)';
        $params = [':developer' => implode(',', $developer), ':introducer' => $introducer,
            ':goodsCode' => $goodsCode, ':goodsStatus' => implode(',', $goodsStatus),
            ':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => (int)$dateFlag,
            ':devBeginDate' => $devBeginDate, ':devEndDate' => $devEndDate,];
        $query = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $query,
            'sort' => ['attributes' =>
                [
                    'developer', 'introducer', 'goodsCode', 'goodsName', 'devDate', 'goodsStatus',
                    'sold', 'amt', 'profit', 'rate', 'maxMonthProfit'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }


    /**
     * @brief 获取开发汇率开发利润
     * @param $condition
     * @return ArrayDataProvider
     * @throws \Exception
     */
    public static function getDevRateDeveloperGoodsProfit($condition)
    {
        $developer = isset($condition['developer']) ? $condition['developer'] : [];
        $homeGreatProfit = isset($condition['homeGreatProfit']) && !empty($condition['homeGreatProfit']) ? $condition['homeGreatProfit'] : 3000;
        $overseaGreatProfit = isset($condition['overseaGreatProfit']) && !empty($condition['$overseaGreatProfit']) ? $condition['overseaGreatProfit'] : 5000;
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];
        list($devBeginDate, $devEndDate) = isset($condition['devDateRange']) && $condition['devDateRange'] ? $condition['devDateRange'] : ['', ''];
        $dateFlag = $condition['dateType'];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $sql = 'call report_devRateDeveloperGoodsProfitAPI (:developer,:goodsStatus, :beginDate,
        :endDate, :dateFlag, :devBeginDate, :devEndDate, :homeGreatProfit, :overseaGreatProfit)';
        $params = [':developer' => implode(',', $developer),
            ':goodsStatus' => implode(',', $goodsStatus),
            ':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => (int)$dateFlag,
            ':devBeginDate' => $devBeginDate, ':devEndDate' => $devEndDate,
            ':homeGreatProfit' => $homeGreatProfit, ':overseaGreatProfit' => $overseaGreatProfit,
        ];

        $oneMonthAgo = static::getPreMonth($beginDate, 1);
        $twoMonthAgo = static::getPreMonth($beginDate, 2);
        $threeMonthAgo = static::getPreMonth($beginDate, 3);

        $current = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();

        $params[':beginDate'] = $oneMonthAgo[0];
        $params[':endDate'] = $oneMonthAgo[1];
        $oneMonthData = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();

        $params[':beginDate'] = $twoMonthAgo[0];
        $params[':endDate'] = $twoMonthAgo[1];
        $twoMonthData = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();

        $params[':beginDate'] = $threeMonthAgo[0];
        $params[':endDate'] = $threeMonthAgo[1];
        $threeMonthData = Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
        $ret = [];
        foreach ($current as &$cur) {
            foreach ($oneMonthData as $od) {
                if ($cur['developer'] === $od['developer']) {
                    $cur['oneMonthProfit'] = $od['profit'];
                }
            }
            foreach ($twoMonthData as $td) {
                if ($cur['developer'] === $td['developer']) {
                    $cur['twoMonthProfit'] = $td['profit'];
                }
            }

            foreach ($threeMonthData as $hd) {
                if ($cur['developer'] === $hd['developer']) {
                    $cur['threeMonthProfit'] = $hd['profit'];
                }
            }

        }
        $ret = [];
        foreach ($current as &$cur) {
            $row = [];
            if (!isset($cur['oneMonthProfit'])) {
                $cur['oneMonthProfit'] = 0;
            }

            if (!isset($cur['twoMonthProfit'])) {
                $cur['twoMonthProfit'] = 0;
            }

            if (!isset($cur['threeMonthProfit'])) {
                $cur['threeMonthProfit'] = 0;
            }
            $cur['maxMonthProfit'] = max([$cur['oneMonthProfit'], $cur['twoMonthProfit'], $cur['threeMonthProfit']]);
            $cur['profitGrowth'] = $cur['profit'] - $cur['maxMonthProfit'];
            $row['developer'] = $cur['developer'];
            $row['amt'] = $cur['amt'];
            $row['sold'] = $cur['sold'];
            $row['profit'] = $cur['profit'];
            $row['maxMonthProfit'] = $cur['maxMonthProfit'];
            $row['profitGrowth'] = $cur['profitGrowth'];
            $ret[] = $row;
        }

        $provider = new ArrayDataProvider([
            'allModels' => $ret,
            'sort' => ['attributes' =>
                [
                    'developer', 'profitGrowth', 'maxMonthProfit',
                    'sold', 'amt', 'profit', 'rate'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    //获取指定日期上个月的第一天和最后一天
    private static function getPreMonth($date, $m)
    {
        $time = strtotime($date);
        $firstDay = date('Y-m-01', strtotime(date('Y', $time) . '-' . (date('m', $time) - $m) . '-01'));
        $lastDay = date('Y-m-d', strtotime("$firstDay +1 month -1 day"));
        return [$firstDay, $lastDay];
    }

    /**
     * @brief 导出开发汇率产品利润
     * @param $condition
     * @return ArrayDataProvider
     * @throws \Exception
     */
    public static function exportDevRateGoodsProfit($condition)
    {
        $developer = isset($condition['developer']) ? $condition['developer'] : [];
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        $introducer = isset($condition['introducer']) ? $condition['introducer'] : '';
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        list($beginDate, $endDate) = $condition['dateRange'];
        list($devBeginDate, $devEndDate) = isset($condition['devDateRange']) && $condition['devDateRange'] ? $condition['devDateRange'] : ['', ''];
        $dateFlag = $condition['dateType'];
        $sql = 'call report_devRateGoodsProfitAPI (:developer,:introducer,:goodsCode,:goodsStatus, :beginDate, :endDate, :dateFlag, :devBeginDate, :devEndDate)';
        $params = [':developer' => implode(',', $developer), ':introducer' => $introducer,
            ':goodsCode' => $goodsCode, ':goodsStatus' => implode(',', $goodsStatus),
            ':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => (int)$dateFlag,
            ':devBeginDate' => $devBeginDate, ':devEndDate' => $devEndDate,];
        return Yii::$app->db->createCommand($sql)->bindValues($params)->queryAll();
    }


    /**
     * @brief 获取开发汇率账号产品利润
     * @param $condition
     * @return mixed
     * @throws \Exception
     */

    public static function getDevRateSuffixGoodsProfit($condition)
    {
        $sql = 'call  report_devRateSuffixGoodsProfitAPI(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse);';
        $sqlParams = [
            ':dateType' => $condition['dateType'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':queryType' => $condition['queryType'],
            ':store' => $condition['store'],
            ':warehouse' => $condition['warehouse'],
        ];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $ret = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
        $clearGoodsList = static::currentClearList();
        $data = [];
        if (!empty($clearGoodsList)) {
            foreach ($ret as $row) {
                if (in_array($row['goodsCode'], $clearGoodsList, true)) {
                    $data[] = $row;
                }
            }
        }
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => ['attributes' =>
                [
                    'sold', 'salemoney', 'grossprofit', 'grossprofitRate'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    ////////////////////////////////////////海外仓清仓列表//////////////////////////////////////////////////

    /**
     * @brief 获取开发汇率开发利润
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    public static function getEbayClearDevProfit($condition)
    {
        $sql = 'call  report_devRateEbayClearDeveloperProfitAPI(:dateType,:beginDate,:endDate,:developer);';
        $sqlParams = [
            ':dateType' => $condition['dateType'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':developer' => $condition['developer'],
        ];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $data = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => [
                'attributes' => ['develop', 'sold', 'costMoney', 'saleMoney', 'profit', 'profitRate']
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * @brief 获取开发汇率账号产品利润
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    public static function getEbayClearSkuProfit($condition)
    {
        $sql = 'call  report_devRateEbayClearSkuProfitAPI(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse);';
        $sqlParams = [
            ':dateType' => $condition['dateType'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':queryType' => $condition['queryType'],
            ':store' => $condition['store'],
            ':warehouse' => $condition['warehouse'],
        ];
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
//        $ret = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->getRawSql();
//        var_dump($ret);exit;
        $ret = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
        $clearGoodsList = static::currentEbayClearList();
        $data = [];
        if (!empty($clearGoodsList)) {
            foreach ($ret as $row) {
                if (in_array($row['sku'], $clearGoodsList, true)) {
                    $data[] = $row;
                }
            }
        }
        $provider = new ArrayDataProvider([
            'allModels' => $data,
            'sort' => ['attributes' =>
                [
                    'sold', 'costmoney', 'salemoney', 'grossprofit', 'grossprofitRate'
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;
    }

    /**
     * 清仓计划里面的商品编码
     * @return array
     */
    private static function currentEbayClearList()
    {
        $sql = 'select sku from oauth_clearPlanEbay where isRemoved = 0';
        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        $data = [];
        if (empty($ret)) {
            return $data;
        }
        return ArrayHelper::getColumn($ret, 'sku');
    }


    /**
     * 返回清仓列表
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    public static function getEbayClearList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $stores = isset($condition['stores']) ? $condition['stores'] : [];
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        $sku = isset($condition['sku']) ? $condition['sku'] : '';
        $sellers = isset($condition['sellers']) ? $condition['sellers'] : [];
        if (!is_array($stores)) {
            throw new Exception('stores should be an array');
        }
        if (!is_array($sellers)) {
            throw new Exception('sellers should be an array');
        }
        $sql = "SELECT  cp.sku,bgs.goodsSkuStatus,bs.storeName,cp.planNumber,cp.createdTime,skuName,
                bgs.bmpFileName AS img,bc.categoryParentName,bc.categoryName,number AS stockNumber,money AS stockMoney,
                bg.salername AS developer -- ,cp.sellers AS seller
            FROM  oauth_clearPlanEbay(nolock) AS cp
            LEFT JOIN b_goodsSku(nolock) AS bgs ON   cp.sku = bgs.sku
            LEFT JOIN b_goods(nolock) AS bg ON   bg.NID = bgs.goodsID
            LEFT JOIN b_goodsCats(nolock) AS bc ON bg.goodsCategoryId = bc.nid
            LEFT JOIN KC_CurrentStock(nolock) AS ks ON ks.goodsskuid = bgs.nid
            LEFT JOIN b_store(nolock) AS bs ON bs.nid = ks.storeId 
            WHERE cp.isRemoved = 0 AND number > 0 
            AND bs.StoreName IN ('万邑通UK','万邑通UK-MA仓','万邑通UKTW','谷仓UK') ";
        if (!empty($stores)) {
            $stores = implode("','", $stores);
            $sql .= " and bs.StoreName in ('" . $stores . "')";
        }
        if (!empty($goodsStatus)) {
            $goodsStatus = implode("','", $goodsStatus);
            $sql .= " and bgs.goodsSkuStatus in ('" . $goodsStatus . "')";
        }

        if (!empty($sellers)) {
            $sellers[] = 'all';
            $sellers = implode("','", $sellers);
            $sql .= " and cp.sellers in ('" . $sellers . "') ";
        }

        if (!empty($sku)) {
            $sql .= " and cp.sku LIKE '%" . $sku . "%' ";
        }
        $query = Yii::$app->py_db->createCommand($sql)->queryAll();
        $stockMoney = ArrayHelper::getColumn($query, 'stockMoney');
        $totalStockMoney = round(array_sum($stockMoney),2);
        $provider = new ArrayDataProvider([
            'allModels' => $query,
            'sort' => ['attributes' =>
                [
                    'stockNumber', 'stockMoney',
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return ['provider' => $provider, 'extra' => ['totalStockMoney' => $totalStockMoney]];

    }

    /**
     * 清仓产品导入模板
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportEbayClearListTemplate()
    {
        $fileName = 'ebay-clear-products-template';
        $titles = ['sku'];
        $data = [['sku' => 'UK-A000305']];
        ExportTools::toExcelOrCsv($fileName, $data, 'Xls', $titles);

    }

    public static function importEbayClearList()
    {
        if (Yii::$app->request->isPost) {
            //判断文件后缀
            $extension = ApiSettings::get_extension($_FILES['file']['name']);
            if (strtolower($extension) != '.xls') return ['code' => 400, 'message' => "File format error,please upload files in 'xls' format"];

            //文件上传
            $result = ApiSettings::file($_FILES['file'], 'ebayClearList');
            if (!$result) {
                return ['code' => 400, 'message' => 'File upload failed'];
            } else {
                //获取上传excel文件的内容并保存
                $res = static::saveEbayClearProduct($result);
                return $res;
            }
        }
        return ['上传成功'];
    }

    public static function saveEbayClearProduct($file)
    {
        $planNumber = 'EBAY-QC-' . (string)date('Y-m');
        $reader = new \PhpOffice\PhpSpreadsheet\Reader\Xls();
        $spreadsheet = $reader->load(Yii::$app->basePath . $file);
        $sheet = $spreadsheet->getSheet(0);
        $highestRow = $sheet->getHighestRow(); // 取得总行数
        $errArr = [];
        for ($i = 2; $i <= $highestRow; $i++) {
            $data['sku'] = $sheet->getCell("A" . $i)->getValue();
            //$data['sellers'] = $sheet->getCell("B" . $i)->getValue();
            $data['createdTime'] = date('Y-m-d H:i:s');
            $data['planNumber'] = $planNumber;
            $data['isRemoved'] = 0;

            if (!$data['sku']) break;//取到数据为空时跳出循环
//            var_dump($data);exit;
            $sql = "SELECT sku FROM oauth_clearPlanEbay WHERE sku = '{$data['sku']}'";
            $res = Yii::$app->py_db->createCommand($sql)->queryOne();
            if ($res) {
                Yii::$app->py_db->createCommand()->update('oauth_clearPlanEbay', $data, ['sku' => $data['sku']])->execute();
            } else {
                Yii::$app->py_db->createCommand()->insert('oauth_clearPlanEbay', $data)->execute();
            }
        }
        return $errArr;
    }


////////////////////////////////////////清仓列表//////////////////////////////////////////////////

    /**
     * 清仓计划里面的商品编码
     * @return array
     */
    private static function currentClearList()
    {
        $sql = 'select goodsCode from oauth_clearPlan where isRemoved = 0';
        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        $data = [];
        if (empty($ret)) {
            return $data;
        }
        return ArrayHelper::getColumn($ret, 'goodsCode');
    }


    /**
     * 返回清仓列表
     * @param $condition
     * @return mixed
     * @throws \Exception
     */
    public static function getClearList($condition)
    {
        $pageSize = isset($condition['pageSize']) ? $condition['pageSize'] : 10;
        $stores = isset($condition['stores']) ? $condition['stores'] : [];
        $goodsStatus = isset($condition['goodsStatus']) ? $condition['goodsStatus'] : [];
        $goodsCode = isset($condition['goodsCode']) ? $condition['goodsCode'] : '';
        $sellers = isset($condition['sellers']) ? $condition['sellers'] : [];
        if (!is_array($stores)) {
            throw new Exception('stores should be an array');
        }
        if (!is_array($sellers)) {
            throw new Exception('sellers should be an array');
        }
        $sql = 'select  cp.goodsCode, bg.goodsStatus, bs.storeName, cp.planNumber,cp.createdTime,goodsName, (select top 1 bmpFileName from b_goodsSku(nolock)  where goodsId= bg.nid) as img, bc.categoryParentName,bc.categoryName,
            stockNumber, stockMoney,
            bg.salername as developer, cp.sellers as seller
            from  oauth_clearPlan(nolock) as cp
            LEFT JOIN b_goods(nolock) as bg on   cp.goodsCode = bg.goodsCode
            LEFT JOIN b_goodsCats(nolock) as bc on bg.goodsCategoryId = bc.nid
            LEFT JOIN (select storeId, goodsId, sum(number) as stockNumber,sum(money) as stockMoney  from   KC_CurrentStock(nolock) as kcs GROUP BY kcs.storeId, kcs.goodsId)  as ks on ks.goodsid = bg.nid
            LEFT JOIN b_store(nolock) as bs on bs.nid = ks.storeId where bs.storeName=cp.storeName and cp.isRemoved = 0 ';
        if (!empty($stores)) {
            $stores = implode("','", $stores);
            $sql .= " and bs.StoreName in ('" . $stores . "')";
        }
        if (!empty($goodsStatus)) {
            $goodsStatus = implode("','", $goodsStatus);
            $sql .= " and bg.goodsStatus in ('" . $goodsStatus . "')";
        }

        if (!empty($sellers)) {
            $sellers[] = 'all';
            $sellers = implode("','", $sellers);
            $sql .= " and (cp.sellers in ('" . $sellers . "')) ";
        }

        if (!empty($goodsCode)) {
            $sql .= " and (cp.goodsCode = '" . $goodsCode . "') ";
        }
        $query = Yii::$app->py_db->createCommand($sql)->queryAll();
        $provider = new ArrayDataProvider([
            'allModels' => $query,
            'sort' => ['attributes' =>
                [
                    'stockNumber', 'stockMoney',
                ]
            ],
            'pagination' => [
                'pageSize' => $pageSize,
            ],
        ]);
        return $provider;

    }


    /**
     * 逻辑清空清仓计划表
     */
    public static function truncateClearList()
    {
        OauthClearPlan::updateAll(['isRemoved' => 1]);
    }

    /**
     * 清仓产品导入模板
     * @param $condition
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportClearListTemplate()
    {
        $fileName = 'clear-products-template';
        $titles = ['商品编码', '仓库名称'];
        $data = [['商品编码' => '7A0001', '仓库名称' => '义乌仓']];
        ExportTools::toExcelOrCsv($fileName, $data, 'Csv', $titles);

    }

    public static function importClearList()
    {
        $fields = ['goodsCode', 'storeName'];
        $planNumber = 'QC-' . (string)date('Y-m');
        try {
            if (Yii::$app->request->isPost) {
                $tmpName = $_FILES['file']['tmp_name'];
                $csvAsArray = array_map('str_getcsv', file($tmpName));

                // 删除列名
                array_shift($csvAsArray);
                $products = [];
                foreach ($csvAsArray as &$row) {
                    foreach ($row as &$ceil) {
                        //检测编码方式
                        $encode = mb_detect_encoding($ceil, array('ASCII', 'UTF-8', 'GB2312', 'GBK', 'BIG5'));
                        // 转换编码方式
                        $ceil = iconv($encode, 'UTF-8', $ceil);
                    }
                    //释放
                    unset($ceil);

                    //生产新产品
                    $product = array_combine($fields, $row);


                    //更新状态
                    $product['planNumber'] = $planNumber;

                    $products[] = $product;

                }
                static::saveNewClearProduct($products);
            }
            return ['上传成功'];
        } catch (\Exception $why) {
            throw new Exception('上传失败');
        }
    }

    /**
     * 创建新计划
     * @param $products
     * @throws \Exception
     */
    public static function saveNewClearProduct($products)
    {
        $sellers = static::getMainSellers($products);

        # 销售账号表
        $accountsMap = static::getSellerSuffixMap();


        $trans = Yii::$app->py_db->beginTransaction();

        try {

            foreach ($products as $product) {
                $plan = new OauthClearPlan();

                # 根据历史表算出的主销售
                $theSeller = $sellers[$product['goodsCode']];

                $product['sellers'] = $theSeller;
                $product['createdTime'] = date('Y-m-d H:i:s');
                $plan->setAttributes($product);
                if (!$plan->save()) {
                    throw new \Exception('Create new clear plan failed!');
                }
            }
            $trans->commit();
        } catch (\Exception $why) {
            $trans->rollBack();
            throw new \Exception('Create new clear plan failed!');
        }
    }

    /**
     * 获取主销售人
     * @param $products
     * @return mixed
     * @throws \Exception
     */
    public static function getMainSellers($products)
    {
        $goodsCode = [];
        foreach ($products as $pt) {
            $goodsCode[] = $pt['goodsCode'];
        }
        $goodsCodeString = implode(',', $goodsCode);
        $sellers = [];
        $goodsCodeSellerMap = static::getMainSellerMap($goodsCodeString);
        foreach ($goodsCode as $gc) {
            if (array_key_exists($gc, $goodsCodeSellerMap)) {
                $goodsInfo = $goodsCodeSellerMap[$gc];
                if ($goodsInfo['stockNumber'] >= 20) {
                    $sellers[$gc] = 'all';
                } else {
                    $sellers[$gc] = !empty($goodsInfo['username']) ? $goodsInfo['username'] : 'all';
                }
            } else {
                $sellers[$gc] = 'all';
            }
        }
        return $sellers;
    }

    /**
     * 获取所有的销售
     * @return mixed
     * @throws Exception
     */
    private static function getAllSeller()
    {
        $sql = 'SELECT distinct username,ats.store FROM `auth_store` ats LEFT JOIN auth_store_child AS atc ON atc.store_id = ats.id LEFT JOIN `user` AS u ON u.id = atc.user_id LEFT JOIN auth_department_child AS adpc ON adpc.user_id = u.id LEFT JOIN auth_department AS adp ON adpc.department_id = adp.id LEFT JOIN auth_department AS adpp ON adp.parent = adpp.id';
        $ret = Yii::$app->db->createCommand($sql)->queryAll();
        return implode(',', array_unique(ArrayHelper::getColumn($ret, 'username')));

    }

    /**
     * 获取所有的账号销售表
     * @return mixed
     * @throws Exception
     */
    private static function getSellerSuffixMap()
    {
        $sql = 'SELECT username,ats.store FROM `auth_store` ats LEFT JOIN auth_store_child AS atc ON atc.store_id = ats.id LEFT JOIN `user` AS u ON u.id = atc.user_id LEFT JOIN auth_department_child AS adpc ON adpc.user_id = u.id LEFT JOIN auth_department AS adp ON adpc.department_id = adp.id LEFT JOIN auth_department AS adpp ON adp.parent = adpp.id';
        $ret = Yii::$app->db->createCommand($sql)->queryAll();
        $map = [];
        foreach ($ret as $row) {
            $username = $row['username'];
            $map[$username][] = $row['store'];
        }
        return $map;
    }


    /**
     * 获取所有的账号对应的销售
     * @return mixed
     * @throws Exception
     */
    private static function getSuffixSellerMap()
    {
        $sql = 'SELECT username,ats.store FROM `auth_store` ats LEFT JOIN auth_store_child AS atc ON atc.store_id = ats.id LEFT JOIN `user` AS u ON u.id = atc.user_id LEFT JOIN auth_department_child AS adpc ON adpc.user_id = u.id LEFT JOIN auth_department AS adp ON adpc.department_id = adp.id LEFT JOIN auth_department AS adpp ON adp.parent = adpp.id';
        $ret = Yii::$app->db->createCommand($sql)->queryAll();
        $map = [];
        foreach ($ret as $row) {
            $map[$row['store']] = $row['username'];
        }
        return $map;
    }


    /**
     * 根据商品编码获取主责任人
     * @param $goodsCodes
     * @return array
     */
    private static function getMainSellerMap($goodsCodes)
    {
        $sql = "oauth_goodsCodeSuffixSold '$goodsCodes'";
        $ret = Yii::$app->py_db->createCommand($sql)->queryAll();
        $sellerMap = [];
        foreach ($ret as $row) {
            $sellerMap[$row['goodsCode']] = $row;
        }
        return $sellerMap;

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
            $params = [':goodsCode' => $goodsCode, ':beginDate' => $beginDate, ':endDate' => $endDate, ':dateFlag' => $dateFlag];
            $db = Yii::$app->db;
            return $db->createCommand($sql)->bindValues($params)->queryAll();
        } catch (\Exception $why) {
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
        return ArrayHelper::getColumn($status, 'DictionaryName');
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
        $query = (new yii\db\Query())->select('*')->from('cache_historySalesProfit')->andWhere(['in', 'username', $salesMan])
            ->andWhere(['between', 'monthName', $beginDate, $endDate])
            ->andWhere(['in', 'plat', $plat]);
        if (!empty($plat)) {
            $query->andWhere(['in', 'plat', $plat]);
        }
        $query = $query->orderBy('monthName asc')->all();
        $out = [];
        $monthList = static::getMonth($beginDate, $endDate);
        foreach ($query as $row) {
            $unique = $row['plat'] . '-' . $row['username'];
            $ret = [];
            if (in_array($unique, ArrayHelper::getColumn($out, 'unique'), true)) {
                foreach ($out as &$ele) {
                    if ($ele['unique'] === $unique) {
                        $ele['historyProfit'][] = ['month' => $row['monthName'], 'profit' => $row['profit']];
                        $ele['historyRank'][] = ['month' => $row['monthName'], 'rank' => $row['rank']];
                        break;
                    }
                }
            } else {
                $ret['unique'] = $unique;
                $ret['username'] = $row['username'];
                $ret['department'] = $row['department'];
                $ret['plat'] = $row['plat'];
                $ret['hireDate'] = $row['hireDate'];
                $ret['avgProfit'] = $row['avgProfit'];
                $ret['rank'] = $row['rank'];
                $ret['departmentTotal'] = $row['departmentTotal'];
                $ret['historyProfit'] = [['month' => $row['monthName'], 'profit' => $row['profit']]];
                $ret['historyRank'] = [['month' => $row['monthName'], 'rank' => $row['rank']]];
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

        foreach ($out as &$item) {
            $myHistoryProfit = $item['historyProfit'];
            $myHistoryRank = $item['historyRank'];
            $hProfit = $historyProfit;
            $hRank = $historyRank;

            //填充历史利润
            foreach ($hProfit as &$pro) {
                foreach ($myHistoryProfit as $oldEle) {
                    if ($pro['month'] === $oldEle['month']) {
                        $pro['profit'] = $oldEle['profit'];
                    }
                }
            }

            //填充历史排名
            foreach ($hRank as &$rank) {
                foreach ($myHistoryRank as $oldEle) {
                    if ($rank['month'] === $oldEle['month']) {
                        $rank['rank'] = $oldEle['rank'];
                    }
                }
            }

            //修正排名
            foreach ($hRank as &$rank) {
                if ($rank['rank'] === 0 && strtotime($rank['month']) < strtotime(date('Y-m'))) {
                    $rank['rank'] = $item['departmentTotal'];
                }
            }

            $item['historyProfit'] = $hProfit;
            $item['historyRank'] = $hRank;


        }
        return $out;
    }

    /** 导出历史利润汇总表
     * exportHistorySalesProfit
     * @param $condition
     * Date: 2020-12-09 14:51
     * Author: henry
     * @throws \PhpOffice\PhpSpreadsheet\Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     */
    public static function exportHistorySalesProfit($condition)
    {
        $plat = isset($condition['plat']) ? $condition['plat'] : [];
        $salesMan = isset($condition['member']) ? $condition['member'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];

        $userList = (new yii\db\Query())->select("username,plat,hireDate,departmentTotal")
            ->from('cache_historySalesProfit')
            ->andFilterWhere(['in', 'username', $salesMan])
            ->andFilterWhere(['between', 'monthName', $beginDate, $endDate])
            ->andFilterWhere(['in', 'plat', $plat])
            ->orderBy('username asc')->distinct()->all();

        $monthList = static::getMonth($beginDate, $endDate);
        $out = [];
        foreach ($userList as $value) {
            $depart = (new yii\db\Query())->select('department')
                ->from('cache_historySalesProfit')
                ->andWhere(['username' => $value['username']])
                ->orderBy('monthName DESC')->one();
            $item['人员'] = $value['username'];
            $item['部门'] = isset($depart['department']) ? $depart['department'] : '';
            $item['销售平台'] = $value['plat'];
            $item['入职日期'] = $value['hireDate'];
            // 每月毛利
            foreach ($monthList as $v) {
                $userData = (new yii\db\Query())->select('profit')
                    ->from('cache_historySalesProfit')
                    ->andWhere(['username' => $value['username'], 'monthName' => $v])->one();
                if ($userData) {
                    $item['利润-' . $v] = $userData['profit'];
                } else {
                    $item['利润-' . $v] = 0;
                }
            }

            // 每月排名
            foreach ($monthList as $v) {
                $userData = (new yii\db\Query())->select('rank')
                    ->from('cache_historySalesProfit')
                    ->andWhere(['username' => $value['username'], 'monthName' => $v])->one();
                if ($userData) {
                    $item['排名-' . $v] = $userData['rank'] . '/' . $value['departmentTotal'];
                } else {
                    $item['排名-' . $v] = $value['departmentTotal'] . '/' . $value['departmentTotal'];
                }
            }
            $out[] = $item;
        }
        ExportTools::toExcelOrCsv('HistorySalesProfit', $out, 'Xls');

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
        $query = (new yii\db\Query())->select('username,plat,profit as profit, monthName,')
            ->from('cache_historySalesProfit')->andWhere(['in', 'username', $salesMan])
            ->andWhere(['between', 'monthName', $beginDate, $endDate]);
        if (!empty($plat)) {
            $query->andWhere(['in', 'plat', $plat]);
        }
        $query = $query->orderBy('monthName desc')->all();
        $monthList = static::getMonth($beginDate, $endDate);
        $out = [];
        $userList = [];
        foreach ($query as $row) {
            $row['title'] = $row['plat'] . '-' . $row['username'];
            unset($row['username'], $row['plat']);
            $out[] = $row;
            if (!in_array($row['title'], $userList, true)) {
                $userList[] = $row['title'];
            }
        }
        // 补充空数据
        $ret = [];
        foreach ($monthList as $month) {
            foreach ($userList as $title) {
                $ele = [];
                $ele['monthName'] = $month;
                $ele['title'] = $title;
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
     * @throws \Exception
     */
    public static function getHistoryRank($condition)
    {
        $plat = isset($condition['plat']) ? $condition['plat'] : [];
        $salesMan = isset($condition['member']) ? $condition['member'] : [];
        list($beginDate, $endDate) = $condition['dateRange'];
        $query = (new yii\db\Query())->select('username,plat,rank, monthName,')
            ->from('cache_historySalesProfit')->andWhere(['in', 'username', $salesMan])
            ->andWhere(['between', 'monthName', $beginDate, $endDate]);
        if (!empty($plat)) {
            $query->andWhere(['in', 'plat', $plat]);
        }
        $query = $query->orderBy('monthName desc')->all();
        $monthList = static::getMonth($beginDate, $endDate);
        $out = [];
        $userList = [];
        foreach ($query as $row) {
            $row['title'] = $row['plat'] . '-' . $row['username'];
            unset($row['username'], $row['plat']);
            $out[] = $row;
            if (!in_array($row['title'], $userList, true)) {
                $userList[] = $row['title'];
            }
        }

        // 补充空数据
        $ret = [];
        foreach ($monthList as $month) {
            foreach ($userList as $title) {
                $ele = [];
                $ele['monthName'] = $month;
                $ele['title'] = $title;
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

        //修正排名
        foreach ($ret as &$rank) {
            if ($rank['rank'] === 0) {
                $rank['rank'] = static::getDepartmentTotal($rank['title']);
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

    /**
     * @param $title
     * @return false|null|string
     * @throws \yii\db\Exception
     */
    private static function getDepartmentTotal($title)
    {
        $plat = explode('-', $title)[0];
        $sql = 'select departmentTotal from cache_historySalesProfit where plat=:plat limit 1';
        $ret = Yii::$app->db->createCommand($sql)->bindValues([':plat' => $plat])->queryScalar();
        return $ret;
    }

    /**
     * 采购账期 数据
     * @param $condition
     * Date: 2021-07-06 9:19
     * Author: henry
     * @return array
     */
    public static function getPurchaseAccountPeriod()
    {
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
        $userStr = implode("','", $userList);
//        var_dump($userList);exit;
        $beginDate = date('Y-m-01', strtotime('-4 month'));
        $sql = "SELECT dt,purchaser,SUM(orderMoney) AS orderMoney
                FROM (
                    SELECT p.personName AS purchaser,CONVERT(VARCHAR(7),c.AudieDate,121) AS dt,
                                orderMoney = (SELECT SUM (ISNULL(allmoney, 0)) FROM	CG_StockInD (nolock) WHERE	StockInNID = c.nid)
                    FROM [dbo].[CG_StockInM] c
                    LEFT JOIN B_Person (nolock) p ON p.NID = C.salerID
                    LEFT JOIN B_Dictionary(nolock) d ON d.NID= C.BalanceID
                    WHERE BillType = 1 AND p.used = 0 AND c.AudieDate >= '{$beginDate}' AND dictionaryName IN('账期付款','线下交易') 
                                AND c.SupplierID<>35383 AND ISNULL(personName,'')<>'' AND StoreID IN(2,7,36,13)
                                AND p.personName IN ('{$userStr}')
                ) aa GROUP BY dt,purchaser
        UNION 
                SELECT dt,purchaser,orderMoney FROM [dbo].[oauth_purchaser_account_period_tmp_data]
                WHERE CONVERT(VARCHAR(10),dt,121) >= '{$beginDate}' AND purchaser IN ('{$userStr}')
                ORDER BY purchaser,dt ";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        $purchaserList = array_unique(ArrayHelper::getColumn($data, 'purchaser'));
        $dateList = array_unique(ArrayHelper::getColumn($data, 'dt'));
        sort($dateList);
        $row = [];
        foreach ($dateList as $v) {
            $row[] = ['dt' => $v, 'orderMoney' => 0];
        }

        $res = [];
        foreach ($purchaserList as $val) {
            $item = [];
            $item['purchaser'] = $val;
            $item['value'] = $row;
            foreach ($data as $v) {
                foreach ($item['value'] as &$value) {
                    if ($value['dt'] == $v['dt'] && $v['purchaser'] == $val) {
                        $value['orderMoney'] = $v['orderMoney'];
                    }
                }
            }
            $res[] = $item;
        }
        return $res;
    }

    /**
     * 采购议价
     * @param $condition
     * Date: 2021-09-29 15:58
     * Author: henry
     * @return mixed
     */
    public static function getPurchaseBargaining($condition)
    {
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
        $userStr = implode("','", $userList);
        $month = $condition['month'] ?: date('Y-m');
        $person = isset($condition['person']) && $condition['person'] ? $condition['person'] : '';
        $sql = "SELECT  bargainTimes,bargainedNum,isBargained,stockOrder,stockInNumber,goodsCode,sku,checkQty,realPrice,
            CASE WHEN prePrice > 0 THEN prePrice
			WHEN preTwoPrice > 0 THEN preTwoPrice
			WHEN preThreePrice > 0 THEN preThreePrice
			WHEN preFourPrice > 0 THEN preFourPrice
			WHEN preFivePrice > 0 THEN preFivePrice
			WHEN preSixPrice > 0 THEN preSixPrice
			WHEN preSevenPrice > 0 THEN preSevenPrice
			WHEN preEightPrice > 0 THEN preEightPrice
			WHEN preNinePrice > 0 THEN preNinePrice
			WHEN preTenPrice > 0 THEN preTenPrice
			WHEN preElevenPrice > 0 THEN preElevenPrice
			WHEN preTwelvePrice > 0 THEN preTwelvePrice
			ELSE targetPrice END AS prePrice
            ,person,deltaPrice,deltaPrice*checkQty AS totalDeltaPrice,doDate
            FROM [dbo].[CG_StockOrderBargainStatistics]
            WHERE CONVERT(VARCHAR(7),doDate,121) = '{$month}' 
                AND person IN ('{$userStr}') ";
        if($person) $sql .= " AND person = '{$person}' ";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        return $data;
    }


    /**
     * 运营KPI 数据
     * @param $condition
     * Date: 2021-07-06 9:19
     * Author: henry
     * @return array
     */
    public static function getOperatorKpi($condition)
    {
        $name = isset($condition['name']) ? $condition['name'] : [];
        $name = $name ? [$name] : [];
        $depart = isset($condition['depart']) ? $condition['depart'] : '';
        $secDepartment = isset($condition['secDepartment']) ? $condition['secDepartment'] : '';
        $plat = isset($condition['plat']) ? $condition['plat'] : '';
        $month = isset($condition['month']) ? $condition['month'] : '';
        if (!$name && $depart) {
            $name = ApiCondition::getUserByDepart($depart, $secDepartment);
        }
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
//        var_dump($userList);exit;
        $query = (new yii\db\Query())//->select('*')
        ->from('cache_kpi_saler_and_dev_tmp_data')
            ->andFilterWhere(['in', 'name', $name])
            ->andFilterWhere(['in', 'name', $userList])
            ->andFilterWhere(['=', 'depart', $depart])
            ->andFilterWhere(['=', 'plat', $plat])
            ->andFilterWhere(['=', 'month', $month])
            ->orderBy('totalScore DESC')->all();
        foreach ($query as &$v) {
            $v['profitRate'] .= '%';
            $v['salesRate'] .= '%';
        }
        return $query;
    }

    /**
     * 运营KPI其他平台 数据
     * @param $condition
     * Date: 2021-07-06 9:19
     * Author: henry
     * @return array
     */
    public static function getOperatorKpiOther($condition)
    {
        $name = isset($condition['name']) ? $condition['name'] : [];
        $name = $name ? [$name] : [];
        $depart = isset($condition['depart']) ? $condition['depart'] : '';
        $secDepartment = isset($condition['secDepartment']) ? $condition['secDepartment'] : '';
        $plat = isset($condition['plat']) ? $condition['plat'] : '';
        $month = isset($condition['month']) ? $condition['month'] : '';
        if (!$name && $depart) {
            $name = ApiCondition::getUserByDepart($depart, $secDepartment);
        }
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
//        var_dump($userList);exit;
        $query = (new yii\db\Query())//->select('*')
        ->from('cache_kpi_saler_and_dev_tmp_data_other_plat')
            ->andFilterWhere(['in', 'name', $name])
            ->andFilterWhere(['in', 'name', $userList])
            ->andFilterWhere(['=', 'depart', $depart])
            ->andFilterWhere(['=', 'plat', $plat])
            ->andFilterWhere(['=', 'month', $month])
            ->orderBy('totalScore DESC')->all();
        foreach ($query as &$v) {
            $v['profitRate'] .= '%';
            $v['salesRate'] .= '%';
        }
        return $query;
    }

    /**
     * 运营KPI 历史数据
     * @param $condition
     * Date: 2021-07-06 9:19
     * Author: henry
     * @return array
     */
    public static function getOperatorKpiHistory($condition)
    {
        $name = isset($condition['name']) ? $condition['name'] : '';
        $name = $name ? [$name] : [];
        $depart = isset($condition['depart']) ? $condition['depart'] : '';
        $secDepartment = isset($condition['secDepartment']) ? $condition['secDepartment'] : '';
        $plat = isset($condition['plat']) ? $condition['plat'] : '';
        $beginMonth = isset($condition['dateRange'][0]) ? $condition['dateRange'][0] : '';
        $endMonth = isset($condition['dateRange'][1]) ? $condition['dateRange'][1] : '';
        if (!$name && $depart) {
            $name = ApiCondition::getUserByDepart($depart, $secDepartment);
        }
        //获取当前用户信息
        $username = Yii::$app->user->identity->username;
        $userList = ApiUser::getUserList($username);
        $query = (new yii\db\Query())//->select('*')
        ->from('cache_kpi_saler_and_dev_tmp_data')
            ->andFilterWhere(['between', 'month', $beginMonth, $endMonth])
            ->andFilterWhere(['in', 'name', $name])
            ->andFilterWhere(['in', 'name', $userList])
            ->andFilterWhere(['=', 'depart', $depart])
            ->andFilterWhere(['=', 'plat', $plat])
            ->orderBy('name, month')->all();
        $userList = array_unique(ArrayHelper::getColumn($query, 'name'));
        $dateList = array_unique(ArrayHelper::getColumn($query, 'month'));
        sort($dateList);
        $row = [];
        foreach ($dateList as $v) {
            $row[] = ['month' => $v, 'rank' => ''];
        }
//        var_dump($row);exit;
        $data = [];
        foreach ($userList as $user) {
            $item = [];
            $item['name'] = $user;
            $item['numA'] = $item['numB'] = $item['numC'] = $item['numD'] =
            $item['testNumA'] = $item['testNumB'] = $item['testNumC'] = $item['testNumD'] =
            $item['totalRate'] = $item['totalSort'] = 0;
            $item['value'] = $row;
            foreach ($query as $v) {
                if ($v['name'] == $user) {
                    $item['depart'] = $v['depart'];
                    $item['hireDate'] = $v['hireDate'];
                    foreach ($item['value'] as &$value) {
                        if ($value['month'] == $v['month'] && $v['month'] >= substr($v['hireDate'], 0, 7)) {
                            $value['rank'] = $v['rank'];
                        }
                    }
                    if ($v['rank'] == 'A') $item['numA'] += 1;
                    if ($v['rank'] == 'B') $item['numB'] += 1;
                    if ($v['rank'] == 'C') $item['numC'] += 1;
                    if ($v['rank'] == 'D') $item['numD'] += 1;
                    if ($v['rank'] == '保护期-A') $item['testNumA'] += 1;
                    if ($v['rank'] == '保护期-B') $item['testNumB'] += 1;
                    if ($v['rank'] == '保护期-C') $item['testNumC'] += 1;
                    if ($v['rank'] == '保护期-D') $item['testNumD'] += 1;

                }
            }
            $item['totalRate'] = round((1 + $item['numA'] * 0.1 + $item['testNumA'] * 0.1 - $item['numC'] * 0.05 - $item['numD'] * 0.1) * 100, 2);
            $data[] = $item;
        }
        $totalRate = ArrayHelper::getColumn($data, 'totalRate');
        array_multisort($totalRate, SORT_DESC, $data);
        foreach ($data as $k => &$v) {
            $v['totalSort'] = ($k + 1) . '/' . count($totalRate);
            $v['totalRate'] .= '%';
        }
        return $data;
    }


}
