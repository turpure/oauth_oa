<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use backend\models\EbayRefund;
use backend\modules\v1\models\ApiReport;
use backend\modules\v1\models\ApiSettings;
use backend\modules\v1\models\ApiUk;
use backend\modules\v1\models\ApiUkFic;
use backend\modules\v1\models\ApiUser;
use backend\modules\v1\utils\Handler;
use console\models\ConScheduler;
use yii\console\Controller;

use Yii;
use yii\db\Exception;
use yii\helpers\ArrayHelper;

class SchedulerController extends Controller
{
    /**
     * @brief sale report scheduler
     */
    public function actionSaleReport()
    {
        $clearSql = 'delete from oauth_saleReport';
        $con = \Yii::$app->py_db;
        $trans = $con->beginTransaction();
        try {
            $ret = $con->createCommand($clearSql)->execute();
            if (!$ret) {
                throw new \Exception('fail to truncate table');
            }
            $dateFlags = [0, 1];
            $dateRanges = [0, 1, 2];
            foreach ($dateFlags as $flag) {
                foreach ($dateRanges as $range) {
                    $updateSql = "exec meta_saleProfit $flag, $range";
                    $re = $con->createCommand($updateSql)->execute();
                    if (!$re) {
                        throw new \Exception('fail to update data');
                    }
                }
            }
            print date('Y-m-d H:i:s') . "INFO:success to get sale-report data\n";
            $trans->commit();
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . "INFO:fail to get sale-report data cause of $why \n";
            $trans->rollback();
        }
    }


    public function actionBackupSuffix()
    {
        try {
            $sql = 'SELECT DictionaryName AS suffix,FitCode AS plat FROM B_Dictionary WHERE CategoryID=12 AND Used=0';
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();
            Yii::$app->db->createCommand()->truncateTable('cache_suffix')->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_suffix', ['suffix', 'plat'], $list)->execute();
            print date('Y-m-d H:i:s') . "INFO:success to backup py suffix data!\n";
        } catch (Exception $e) {
            print date('Y-m-d H:i:s') . "INFO:fail to  to backup py suffix data cause of $e \n";
        }


    }


    /**
     * @brief display info of sku are out of stock
     */
    public function actionOutOfStockSku()
    {
        $con = \Yii::$app->py_db;
        $sql = "EXEC oauth_outOfStockSku @GoodsState='',@MoreStoreID='',@GoodsUsed='0',@SupplierName='',@WarningCats='',@MoreSKU='',
        @cg=0,@GoodsCatsCode='',@index='1',@KeyStr='',
        @PageNum='100',@PageIndex='1',@Purchaser='',@LocationName='',@Used=''";
        try {
            $con->createCommand($sql)->execute();
            print date('Y-m-d H:i:s') . "INFO:success to get sku out of stock!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . "INFO:fail to get sku out of stock cause of $why \n";
        }
    }

    /**
     * @brief 更新主页各人员目标完成度
     */
    public function actionSite()
    {
        $beginDate = '2021-09-01';//date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d', strtotime('-1 days'));//昨天时间
//        $endDate = '2021-09-01';//昨天时间
        $dateRate = round(((strtotime($endDate) - strtotime($beginDate)) / 24 / 3600 + 1) * 100 / 122, 2);
        // 获取最近一次备份数据月份
        $backupDataMonth = Yii::$app->db->createCommand("SELECT max(month) FROM site_target_all_backup_data WHERE role='开发'")->queryScalar();
        if($backupDataMonth){
            $beginDate = date('Y-m-01', strtotime('+1 month', strtotime($backupDataMonth)));
        }
//        print_r($beginDate);exit;
        try {
            //删除开发目标数据
            Yii::$app->db->createCommand("TRUNCATE TABLE site_target_all;")->execute();
            //更新开发目标完成度
            $seller = Yii::$app->db->createCommand("SELECT distinct username FROM site_target_user WHERE role='开发'")->queryAll();
            $condition = [
                'dateFlag' => 1,
                'beginDate' => $beginDate,
                'endDate' => $endDate,
                'seller' => implode(',', ArrayHelper::getColumn($seller, 'username')),
                'flag' => 1
            ];
            $devList = ApiReport::getDevelopReport($condition);
            foreach ($devList as $value) {
                $basicSql = "SELECT t.*,IFNULL(l.basic,0) AS basic,IFNULL(hl.high,0) AS high FROM site_target_user t
                            LEFT JOIN site_target_level l ON l.`level`=t.`level` AND l.role=t.role 
                            LEFT JOIN site_target_level hl ON hl.`high_level`=t.`high_level` AND hl.role=t.role 
                            WHERE t.role='开发' and username='{$value['salernameZero']}' ";
                $target = Yii::$app->db->createCommand($basicSql)->queryOne();
                $basic = $target['basic'] ?? 0;
                $high = $target['high'] ?? 0;
                $backupSql = "SELECT sum(profit_zn) AS profit_zn FROM site_target_all_backup_data 
                            WHERE role='开发' and username='{$value['salernameZero']}' GROUP BY username";
                $lastProfit = Yii::$app->db->createCommand($backupSql)->queryScalar();
                Yii::$app->db->createCommand()->insert(
                    'site_target_all',
                    [
                        'username' => $target['username'],
                        'depart' => $target['depart'],
                        'role' => $target['role'],
                        'level' => $target['level'],
                        'high_level' => $target['high_level'],
                        'profit' => $value['netprofitZero'] + $value['netprofitSix'] + $lastProfit,
                        'rate' => $basic != 0 ? round(($value['netprofitZero'] + $value['netprofitSix'] + $lastProfit) * 100.0 / $basic, 2) : 0,
                        'high_rate' => $high != 0 ? round(($value['netprofitZero'] + $value['netprofitSix'] + $lastProfit) * 100.0 / $high, 2) : 0,
                        'date_rate' => $dateRate,
                        'updatetime' => $endDate
                    ]
                )->execute();
            }

            //更新销售和部门目标完成度
//            $exchangeRate = ApiUkFic::getRateUkOrUs('USD');//美元汇率
            $rate = ApiSettings::getExchangeRate();
            $exchangeRate = $rate['salerRate']; //销售汇率
            $wishExchangeRate = $rate['wishSalerRate']; //wish销售汇率
            $sql = "CALL oauth_siteTargetAll($exchangeRate, $wishExchangeRate)";
            Yii::$app->db->createCommand($sql)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }

    /**
     * 更新产品销量变化（两个时间段对比）
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionSalesChange()
    {
        $begin = date('Y-m-01', strtotime('-1 day'));
        //$begin = '2020-01-01';
        $end = date('Y-m-d 23:59:59', strtotime('-1 day'));
//        var_dump($end);exit;
        $sql = "EXEC oauth_salesChangeOfTwoDateBlock_backup '{$begin}', '{$end}'";
        try {
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();

            Yii::$app->db->createCommand("delete from cache_sales_change where orderTime between '{$begin}' and '{$end}'")->execute();
            $step = 200;
            $num = ceil(count($list) / $step);
            for ($i = 0; $i < $num; $i++) {
                Yii::$app->db->createCommand()->batchInsert(
                    'cache_sales_change',
                    ['orderId', 'suffix', 'goodsCode', 'goodsName', 'qty', 'amt', 'orderTime', 'createDate'],
                    array_slice($list, $i * $step, $step)
                )->execute();
            }

            print date('Y-m-d H:i:s') . " INFO:success to update data of sales change!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales change cause of $why \n";
        }
    }

    /**
     * 更新主页今日爆款
     * Date: 2019-01-11 11:11
     * Author: henry
     */
    public function actionPros()
    {
        //获取昨天时间
        $beginDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d', strtotime('-1 days'));
        $sql = "EXEC oauth_siteGoods @DateFlag=:dateFlag,@BeginDate=:beginDate,@EndDate=:endDate";
        $params = [
            ':dateFlag' => 1,//发货时间
            ':beginDate' => $beginDate,
            ':endDate' => $endDate
        ];
        try {
            $list = Yii::$app->py_db->createCommand($sql)->bindValues($params)->queryAll();
            //清空数据表并插入新数据
            Yii::$app->db->createCommand("TRUNCATE TABLE site_goods")->execute();
            Yii::$app->db->createCommand()->batchInsert('site_goods',
                ['profit', 'salesNum', 'platform', 'goodsCode', 'goodsName', 'endTime', 'img', 'developer', 'linkUrl', 'cate', 'subCate'],
                $list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of today pros!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of today pros cause of $why \n";
        }
    }

    /**
     * 更新主页利润增长表
     * Date: 2019-01-11 11:55
     * Author: henry
     */
    public function actionProfit()
    {
        //获取上月时间
        $lastBeginDate = date('Y-m-01', strtotime('last day of -1 month -1 day'));
        $lastEndDate = date('Y-m-t', strtotime('last day of -1 month -1 day'));
        $beginDate = date('Y-m-01', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));

        try {
            //获取账号信息
            $params = [
                'platform' => [],
                'username' => [],
                'store' => []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $store = implode(',', $paramsFilter['store']);
            //获取ebay 和 wish 销售汇率
            $exchangeArr = ApiSettings::getExchangeRate();
            $exchangeRate = $exchangeArr['salerRate'];
            $wishExchangeRate = $exchangeArr['wishSalerRate'];

//            var_dump($rateArr);exit;
            //获取开发人员上月和本月毛利的初步数据.
            $devSql = "EXEC oauth_siteDeveloperProfit";
            $devData = Yii::$app->py_db->createCommand($devSql)->queryAll();
            //初步数据保存到Mysql数据库cache_developProfitTmp，进一步进行计算
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_developProfitTmp')->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_developProfitTmp',
                ['tableType', 'timegroupZero', 'salernameZero', 'salemoneyrmbusZero', 'salemoneyrmbznZero', 'costmoneyrmbZero',
                    'ppebayusZero', 'ppebayznZero', 'inpackagefeermbZero', 'expressfarermbZero', 'devofflinefeeZero', 'devOpeFeeZero',
                    'netprofitZero', 'netrateZero', 'timegroupSix', 'salemoneyrmbusSix', 'salemoneyrmbznSix', 'costmoneyrmbSix',
                    'ppebayusSix', 'ppebayznSix', 'inpackagefeermbSix', 'expressfarermbSix', 'devofflinefeeSix', 'devOpeFeeSix',
                    'netprofitSix', 'netrateSix', 'timegroupTwe', 'salemoneyrmbusTwe', 'salemoneyrmbznTwe', 'costmoneyrmbTwe',
                    'ppebayusTwe', 'ppebayznTwe', 'inpackagefeermbTwe', 'expressfarermbTwe', 'devofflinefeeTwe', 'devOpeFeeTwe',
                    'netprofitTwe', 'netrateTwe', 'salemoneyrmbtotal', 'netprofittotal', 'netratetotal',
                    'devRate', 'devRate1', 'devRate5', 'devRate7', 'type', 'updateTime'],
                $devData)->execute();


            //插入销售和开发毛利数据(存储过程插入)
            //$sql = "CALL oauth_site_profit(0,'{$lastBeginDate}','{$lastEndDate}','{$beginDate}','{$endDate}','{$exchangeRate}','{$wishExchangeRate}','{$store}');";
            //Yii::$app->db->createCommand($sql)->execute();

            //获取上月毛利
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_salerProfitTmp")->execute();
            $sql = 'call report_salesProfit(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse,:exchangeRate, :wishExchangeRate);';
            $sqlParams = [
                ':dateType' => 1,
                ':beginDate' => $lastBeginDate,
                ':endDate' => $lastEndDate,
                ':queryType' => 1,
                ':store' => $store,
                ':warehouse' => '',
                ':exchangeRate' => $exchangeRate,
                ':wishExchangeRate' => $wishExchangeRate
            ];
//            $lastProfit = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->getRawSql();
            $lastProfit = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
            foreach ($lastProfit as &$v) {
                /*Yii::$app->db->createCommand()->batchInsert('cache_salerProfitTmp',
                    ['pingtai','department','suffix','salesman','salemoney','salemoneyzn',
                        'ebayfeeebay','ebayfeeznebay','ppFee','ppFeezn','costmoney','expressFare',
                        'inpackagemoney','storename','refund','refundrate','diefeeZn','insertionFee',
                        'saleOpeFeeZn','grossprofit','grossprofitRate'],
                    $lastProfit)->execute();*/
                $v = array_merge($v, ['month' => 'last', 'updateTime' => $endDate]);
                Yii::$app->db->createCommand()->insert('cache_salerProfitTmp', $v)->execute();
            }

            //获取本月毛利
            $sqlParams[':beginDate'] = $beginDate;
            $sqlParams[':endDate'] = $endDate;
            $thisProfit = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
            foreach ($thisProfit as &$v) {
                $v = array_merge($v, ['month' => 'this', 'updateTime' => $endDate]);
                Yii::$app->db->createCommand()->insert('cache_salerProfitTmp', $v)->execute();
            }

            //汇总数据结果
            $sql = "CALL oauth_site_profit(0,'{$endDate}');";
            Yii::$app->db->createCommand($sql)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of profit changes!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of profit changes cause of $why \n";
        }

    }

    /**
     * 更新主页销售额增长表
     * Date: 2019-04-15 16:25
     * Author: henry
     */
    public function actionSalesAmt()
    {
        //获取上月时间
        $lastBeginDate = date('Y-m-01', strtotime('last day of -1 month -1 day'));
        $lastEndDate = date('Y-m-t', strtotime('last day of -1 month -1 day'));
        $beginDate = date('Y-m-01', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        try {
            //获取普源美元汇率
            $usRate = ApiUkFic::getRateUkOrUs('USD');
            //清空最终数据表
            Yii::$app->db->createCommand("TRUNCATE TABLE site_sales_amt;")->execute();


            //获取开发人员销售额
            $devSql = "EXEC oauth_siteDeveloperAmt";
            $devList = Yii::$app->py_db->createCommand($devSql)->queryAll();

            //获取现有开发人员及部门
            $sql = "SELECT u.username,u.avatar as img,
                        IFNULL(p.department, d.department) as depart 
                    FROM `user` u 
                    LEFT JOIN auth_assignment ass ON ass.user_id=u.id
                    LEFT JOIN auth_department_child dc ON dc.user_id=u.id
                    LEFT JOIN auth_department d ON d.id=dc.department_id
                    LEFT JOIN auth_department p ON p.id=d.parent
                    WHERE u.`status`=10 AND ass.item_name='产品开发';";
            $developers = Yii::$app->db->createCommand($sql)->queryAll();

            // 按销售表的排序重新组合现有开发人员表
            $tmpDevelopers = ArrayHelper::getColumn($developers, 'username');
            $tmpDevList = ArrayHelper::getColumn($devList, 'username');

            $resDevList = array_unique(array_merge(array_intersect($tmpDevList, $tmpDevelopers), $tmpDevelopers));

            $data = [];
            foreach ($resDevList as $k => $val) {
                $data[$k]['username'] = $val;
                $data[$k]['img'] = '';
                $data[$k]['depart'] = '';
                $data[$k]['role'] = '开发';
                $data[$k]['lastAmt'] = 0;
                $data[$k]['amt'] = 0;
                $data[$k]['amtDiff'] = 0;
                $data[$k]['rate'] = 0;
                $data[$k]['dateRate'] = 0;
                $data[$k]['updateTime'] = $endDate;
                foreach ($developers as $v) {
                    if ($val == $v['username']) {
                        $data[$k]['img'] = $v['img'];
                        $data[$k]['depart'] = $v['depart'];
                        continue;
                    }
                }
                foreach ($devList as $v) {
                    if ($val == $v['username']) {
                        $data[$k]['lastAmt'] = $v['lastAmt'];
                        $data[$k]['amt'] = $v['amt'];
                        $data[$k]['amtDiff'] = $v['amt'] - $v['lastAmt'];
                        $data[$k]['rate'] = $v['rate'];
                        $data[$k]['dateRate'] = $v['dateRate'];
                        continue;
                    }
                }
                if ($data[$k]['amt'] == 0 && $data[$k]['lastAmt'] == 0) {
                    unset($data[$k]);
                }
            }
            //var_dump($data);exit;
            //插入开发销售数据
            Yii::$app->db->createCommand()->batchInsert('site_sales_amt',
                ['username', 'img', 'depart', 'role', 'lastAmt', 'amt', 'amtDiff', 'rate', 'dateRate', 'updateTime'],
                $data)->execute();

            //插入销售销售额数据(存储过程插入) 并排名
            Yii::$app->db->createCommand("CALL oauth_site_amt('{$lastBeginDate}','{$lastEndDate}','{$beginDate}','{$endDate}','{$usRate}');")->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of amt changes!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of amt changes cause of $why \n";
        }

    }

    /**
     * Date: 2019-03-12 8:56
     * Author: henry
     */
    public function actionWeightDiff()
    {
        $beginDate = '2021-01-01';
        $endDate = date('Y-m-d');
        //print_r($endDate);exit;
        try {
            //获取开发人员毛利
            $sql = "EXEC oauth_weightDiff :beginDate,:endDate";
            $list = Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate, ':endDate' => $endDate])->queryAll();
            $step = 500;
            $count = ceil(count($list) / 500);
            //清空数据表
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_weightDiff')->execute();
            //插入数据
            if ($list) {
                for ($i = 0; $i <= $count; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_weightDiff',
                        ['trendId', 'suffix', 'orderCloseDate', 'orderWeight', 'skuWeight', 'weightDiff', 'profit'],
                        array_slice($list, $i * $step, $step))->execute();
                }
            }
            print date('Y-m-d H:i:s') . " INFO:success to update data of weight diff!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of weight diff cause of $why \n";
        }

    }

    public function actionPriceTrend()
    {
        $beginDate = '2018-10-01';
        $endDate = date('Y-m-d', strtotime('-1 day'));
        //print_r($endDate);exit;
        try {
            //获取开发人员毛利
            $sql = "EXEC oauth_weightDiff :beginDate,:endDate";
            $list = Yii::$app->py_db->createCommand($sql)->bindValues([':beginDate' => $beginDate, ':endDate' => $endDate])->queryAll();
            $step = 500;
            $count = ceil(count($list) / 500);
            //清空数据表
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_weightDiff')->execute();
            //插入数据
            if ($list) {
                for ($i = 0; $i <= $count; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_weightDiff',
                        ['trendId', 'suffix', 'orderCloseDate', 'orderWeight', 'skuWeight', 'weightDiff', 'profit'],
                        array_slice($list, $i * $step, $step))->execute();
                }
            }
            print date('Y-m-d H:i:s') . " INFO:success to update data of weight diff!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of weight diff cause of $why \n";
        }

    }

    /**
     *  销售排名
     * Date: 2019-05-07 16:15
     * Author: henry
     */
    public function actionSalesRanking()
    {
        //获取上月时间
        $lastBeginDate = date('Y-m-01', strtotime('last day of -1 month -1 day'));
        $lastEndDate = date('Y-m-t', strtotime('last day of -1 month -1 day'));
        $beginDate = date('Y-m-01', strtotime('-1 day'));
        $endDate = date('Y-m-d', strtotime('-1 day'));
        try {
            //获取账号信息
            $params = [
                'platform' => [],
                'username' => [],
                'store' => []
            ];
            $paramsFilter = Handler::paramsHandler($params);
            $store = implode(',', $paramsFilter['store']);
            //var_dump($store);
            //获取ebay 和 wish 销售汇率
            $exchangeArr = ApiSettings::getExchangeRate();
            $exchangeRate = $exchangeArr['salerRate'];
            $wishExchangeRate = $exchangeArr['wishSalerRate'];


            //获取上月毛利数据
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_salerProfitTmp")->execute();
            $sql = 'call report_salesProfit(:dateType,:beginDate,:endDate,:queryType,:store,:warehouse,:exchangeRate, :wishExchangeRate);';
            $sqlParams = [
                ':dateType' => 1,
                ':beginDate' => $lastBeginDate,
                ':endDate' => $lastEndDate,
                ':queryType' => 1,
                ':store' => $store,
                ':warehouse' => '',
                ':exchangeRate' => $exchangeRate,
                ':wishExchangeRate' => $wishExchangeRate
            ];
            $lastProfit = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
            foreach ($lastProfit as &$v) {
                $v = array_merge($v, ['month' => 'last', 'updateTime' => $endDate]);
                Yii::$app->db->createCommand()->insert('cache_salerProfitTmp', $v)->execute();
            }

            //获取本月毛利
            $sqlParams[':beginDate'] = $beginDate;
            $sqlParams[':endDate'] = $endDate;
            $thisProfit = Yii::$app->db->createCommand($sql)->bindValues($sqlParams)->queryAll();
            foreach ($thisProfit as &$v) {
                $v = array_merge($v, ['month' => 'this', 'updateTime' => $endDate]);
                Yii::$app->db->createCommand()->insert('cache_salerProfitTmp', $v)->execute();
            }

            //汇总数据结果
            $sql = "CALL oauth_site_profit(1,'{$endDate}');";
            Yii::$app->db->createCommand($sql)->execute();
            //插入销售毛利数据(存储过程插入)
            //Yii::$app->db->createCommand("CALL oauth_site_profit(1,'{$lastBeginDate}','{$lastEndDate}','{$beginDate}','{$endDate}');")->execute();
            print date('Y-m-d H:i:s') . " INFO:success to update data of sales profit ranking!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales profit ranking cause of $why \n";
        }
    }

    /**
     * 备货产品计算
     * 每天更新开发员在本月的可用备货数量，每月第一天（1号）更新备份数据
     * 访问方法: php yii scheduler/stock
     * Date: 2019-05-06 15:58
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionStock()
    {
        $end = date('Y-m-d');
        //$end = '2019-06-01';
        $startDate = date('Y-m-d', strtotime('-75 days', strtotime($end)));
        $endDate = date('Y-m-d', strtotime('-15 days', strtotime($end)));
        //print_r($startDate);
        //print_r($endDate);exit;
        //获取订单数详情
        $orderList = Yii::$app->py_db->createCommand("EXEC oauth_stockGoodsNumber '" . $startDate . "','" . $endDate . "','';")->queryAll();
        //获取开发产品列表
        $goodsSql = "SELECT developer,goodsCode,stockUp FROM proCenter.oa_goodsinfo gs
                      WHERE LEFT(devDatetime,10) BETWEEN '{$startDate}' AND '{$endDate}' AND ifnull(mid,0)=0;";
        $goodsList = Yii::$app->db->createCommand($goodsSql)->queryAll();
        //获取开发员备货产品数，不备货产品数，总产品数
        $list = Yii::$app->db->createCommand("CALL proCenter.oa_stockGoodsNum('{$startDate}','{$endDate}');")->queryAll();
        //统计出单数，爆旺款数量
        $developer = [];
        foreach ($goodsList as $k => $v) {
            $orderNum = 0;
            $goodsStatus = '';
            foreach ($orderList as $value) {
                if ($v['goodsCode'] == $value['goodsCode']) {
                    $orderNum += $value['l_qty'];//出单数
                    $goodsStatus = $value['goodsStatus'];
                    break;
                }
            }
            $v['orderNum'] = $orderNum;
            $v['goodsStatus'] = $goodsStatus;
            $developer[$k] = $v;
        }
        //print_r($developer);exit;
        $orderNumList = $nonOrderNumList = [];
        foreach ($list as $k => $value) {
            $stockOrderNum = $nonStockOrderNum = $hot = $exu = $nonHot = $nonExu = 0;
            foreach ($developer as $v) {

                if ($value['username'] === $v['developer']) {
                    $nonStockOrderNum = ($v['stockUp'] === '否' && $v['orderNum'] > 0) ? $nonStockOrderNum + 1 : $nonStockOrderNum;
                    $stockOrderNum = ($v['stockUp'] == '是' && $v['orderNum'] > 0) ? $stockOrderNum + 1 : $stockOrderNum;
                    $hot = ($v['goodsStatus'] == '爆款' && $v['stockUp'] == '是' && $v['orderNum'] > 0) ? $hot + 1 : $hot;
                    $exu = ($v['goodsStatus'] == '旺款' && $v['stockUp'] == '是' && $v['orderNum'] > 0) ? $exu + 1 : $exu;
                    $nonHot = ($v['goodsStatus'] == '爆款' && $v['stockUp'] == '否' && $v['orderNum'] > 0) ? $nonHot + 1 : $nonHot;
                    $nonExu = ($v['goodsStatus'] == '旺款' && $v['stockUp'] == '否' && $v['orderNum'] > 0) ? $nonExu + 1 : $nonExu;
                }
            }


            //计算 备货和不备货的爆旺款率
            $hotAndExuRate = $value['stockNum'] == 0 ? 0 : round(($hot + $exu) * 1.0 / $value['stockNum'], 4) * 100;
            $nonHotAndExuRate = $value['nonStockNum'] == 0 ? 0 : round(($nonHot + $nonExu) * 1.0 / $value['nonStockNum'], 4) * 100;
            //计算 备货和不备货的出单率
            $orderRate = $value['stockNum'] == 0 ? 0 : round($stockOrderNum * 1.0 / $value['stockNum'], 4) * 100;
            $nonOrderRate = $value['nonStockNum'] == 0 ? 0 : round($nonStockOrderNum * 1.0 / $value['nonStockNum'], 4) * 100;
            //计算 出单率评分
            $rate1 = round(max(1 - max((80 - $orderRate), 0) * 0.025, 0.5), 2);
            $nonRate1 = round(max(1 - max((80 - $nonOrderRate), 0) * 0.025, 0.5), 2);
            //计算 爆旺款率评分
            $rate2 = round(2 - max((30 - $hotAndExuRate) * 0.04, 0), 2);
            $nonRate2 = round(2 - max((30 - $nonHotAndExuRate) * 0.04, 0), 2);

            $item1['developer'] = $item2['developer'] = $value['username'];
            $item1['number'] = (int)$value['stockNum'];
            $item1['orderNum'] = $stockOrderNum;
            $item1['hotStyleNum'] = $hot;
            $item1['exuStyleNum'] = $exu;
            $item1['rate1'] = $rate1;
            $item1['rate2'] = $rate2;
            $item1['createDate'] = date('Y-m-d H:i:s');
            $item1['isStock'] = 'stock';

            $item2['number'] = (int)$value['nonStockNum'];
            $item2['orderNum'] = $nonStockOrderNum;
            $item2['hotStyleNum'] = $nonHot;
            $item2['exuStyleNum'] = $nonExu;
            $item2['rate1'] = $nonRate1;
            $item2['rate2'] = $nonRate2;
            $item2['createDate'] = date('Y-m-d H:i:s');
            $item2['isStock'] = 'nonstock';

            $orderNumList[$k] = $item1;
            $nonOrderNumList[$k] = $item2;
        }
        $tran = Yii::$app->db->beginTransaction();
        try {
            //插入数据表oa_stockGoodsNum
            Yii::$app->db->createCommand()->truncateTable('proCenter.oa_stockGoodsNumReal')->execute();
            Yii::$app->db->createCommand()->batchInsert('proCenter.oa_stockGoodsNumReal',
                ['developer', 'number', 'orderNum', 'hotStyleNum', 'exuStyleNum', 'rate1', 'rate2', 'createDate', 'isStock'], $orderNumList)->execute();
            Yii::$app->db->createCommand()->batchInsert('proCenter.oa_stockGoodsNumReal',
                ['developer', 'number', 'orderNum', 'hotStyleNum', 'exuStyleNum', 'rate1', 'rate2', 'createDate', 'isStock'], $nonOrderNumList)->execute();
            //更新 可用数量  判断当前日期是本月1号，数据还要插入备份表
            if (substr($end, 8, 2) !== '01') {
                $sql = " UPDATE proCenter.oa_stockGoodsNumReal r,proCenter.oa_stockGoodsNum s 
                    SET r.stockNumThisMonth = s.stockNumThisMonth,
				        r.stockNumLastMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END
                    WHERE r.developer=s.developer AND substring(r.createDate,1,7) = substring(s.createDate,1,7) AND r.isStock=s.isStock ";
                Yii::$app->db->createCommand($sql)->execute();
            } else {
                //如果当前日期是本月1号，先查询有没有备份数据，在插入备份表
                $sql = " UPDATE proCenter.oa_stockGoodsNumReal r,proCenter.oa_stockGoodsNum s 
                    SET r.stockNumThisMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END,
				        r.stockNumLastMonth = CASE when ifnull(s.number,0)=0 THEN s.stockNumThisMonth 
				                              ELSE ROUND(ifnull(s.stockNumThisMonth, 30)*r.rate1*r.rate2,0) END
                    WHERE r.developer=s.developer AND r.isStock=s.isStock
                    AND substring(date_add(r.createDate, interval -1 month),1,10) = substring(s.createDate,1,10) AND r.isStock=s.isStock ";
                Yii::$app->db->createCommand($sql)->execute();
                //判断备份表是否有备份数据, 没有则插入
                $checkSql = "SELECT * FROM proCenter.oa_stockGoodsNum WHERE substring(createDate,1,10)='{$end}'";
                $check = Yii::$app->db->createCommand($checkSql)->queryAll();
                if (!$check) {
                    $sqlRes = "INSERT INTO proCenter.oa_stockGoodsNum(developer,number,orderNum,hotStyleNum,exuStyleNum,rate1,rate2,discount,stockNumThisMonth,stockNumLastMonth,createDate,isStock)
                            SELECT developer,number,orderNum,hotStyleNum,exuStyleNum,rate1,rate2,discount,stockNumThisMonth,stockNumLastMonth,createDate,isStock 
                            FROM  proCenter.oa_stockGoodsNumReal WHERE substring(createDate,1,10) = '{$end}'";
                    Yii::$app->db->createCommand($sqlRes)->execute();
                }
            }
            $tran->commit();
            echo date('Y-m-d H:i:s') . " (new)The stock data update successful!\n";;
        } catch (\Exception $e) {
            $tran->rollBack();
            echo date('Y-m-d H:i:s') . " (new)The stock data update failed!\n";
        }

    }


    /**
     * 查询wish平台商品状态、采购到货天数并更新oa_goodsinfo表数据
     * Date: 2019-05-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionWish()
    {
        $res = Yii::$app->py_db->createCommand("P_oa_updateGoodsStatusToTableOaGoodsInfo")->queryAll();
        //更新 oa_goodsinfo 表的stockDays，goodsStatus
        foreach ($res as $v) {
            Yii::$app->db->createCommand()->update('proCenter.oa_goodsinfo', $v, ['goodsCode' => $v['goodsCode']])->execute();
        }

        // 更新 oa_goodsinfo 表的wishPublish
        $sql = "UPDATE proCenter.oa_goodsinfo SET wishPublish=
	            CASE WHEN stockDays>0 AND storeName='义乌仓' AND IFNULL(dictionaryName,'') not like '%wish%' and  (completeStatus NOT LIKE '%Wish%' OR completeStatus IS NULL) then 'Y' 
			          ELSE 'N' END ";
        $ss = Yii::$app->db->createCommand($sql)->execute();
        if ($ss) {
            echo date('Y-m-d H:i:s') . " Update successful!\n";
        } else {
            echo date('Y-m-d H:i:s') . " Update failed!\n";
        }
    }

    /**
     * 海外仓备货
     * Date: 2019-06-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionOverseasReplenish()
    {
        $step = 400;
        try {
            //清空数据表
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_overseasReplenish;")->execute();

            //插入UK虚拟仓补货数据
            $ukVirtualList = Yii::$app->py_db->createCommand("EXEC oauth_ukVirtualReplenish;")->queryAll();
            $max = ceil(count($ukVirtualList) / $step);
            for ($i = 0; $i < $max; $i++) {
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', 'hopeUseNum',
                        'amount', 'totalHopeUN', 'hopeSaleDays', 'purchaseNum', 'price', 'purCost', 'type'
                    ],
                    array_slice($ukVirtualList, $i * $step, $step))->execute();
            }

            //插入AU真补货数据
            $auRealList = Yii::$app->py_db->createCommand("EXEC oauth_auRealReplenish")->queryAll();
            $max = ceil(count($auRealList) / $step);
            for ($i = 0; $i < $max; $i++) {
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                        'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                    ],
                    array_slice($auRealList, $i * $step, $step))->execute();
            }

            //插入UK真仓补货数据
            $ukRealList = Yii::$app->py_db->createCommand("EXEC oauth_ukRealReplenish")->queryAll();
            $max = ceil(count($ukRealList) / $step);
            for ($i = 0; $i < $max; $i++) {
                Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                    [
                        'SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                        'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                        'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                    ],
                    array_slice($ukRealList, $i * $step, $step))->execute();
            }

            echo date('Y-m-d H:i:s') . " Get overseas replenish data successful!\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Get overseas replenish data failed!\n";
            //echo $e->getMessage();
        }

    }

    /**
     * 库存情况
     * Date: 2019-06-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionStockStatus()
    {
        $beginTime = time();
        $step = 100;
        try {
            //插入库存预警数据
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_stockWaringTmpData;")->execute();

            //分页获取数据
            for ($k = 1; ; $k++) {
                $stockList = Yii::$app->py_db->createCommand("EXEC oauth_stockStatus 1,'{$k}';")->queryAll();
                if (!count($stockList)) {
                    break;
                }
                $max = ceil(count($stockList) / $step);
                for ($i = 0; $i < $max; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_stockWaringTmpData',
                        [
                            'goodsCode', 'sku', 'class', 'skuName', 'storeName', 'goodsStatus', 'salerName',
                            'createDate', 'costPrice', 'useNum', 'costmoney', 'notInStore', 'notInCostmoney',
                            'hopeUseNum', 'totalCostmoney', 'sellCount1', 'sellCount2', 'sellCount3', 'weight',
                            'sellCostMoney' ,'threeSellCount','sevenSellCount','fourteenSellCount','thirtySellCount','trend',
                            'updateTime' ,'updateMonth'
                        ],
                        array_slice($stockList, $i * $step, $step))->execute();
                }
            }


            //插入30天销售数据
            /*Yii::$app->db->createCommand("TRUNCATE TABLE cache_30DayOrderTmpData;")->execute();

            $saleList = Yii::$app->py_db->createCommand("EXEC oauth_stockStatus")->queryAll();
            $max = ceil(count($saleList) / $step);
            for ($i = 0; $i < $max; $i++) {
                Yii::$app->db->createCommand()->batchInsert('cache_30DayOrderTmpData',
                    [
                        'sku', 'salerName', 'storeName', 'goodsStatus', 'costMoney', 'updateTime',
                        'threeSellCount', 'sevenSellCount', 'fourteenSellCount', 'thirtySellCount', 'trend'
                    ],
                    array_slice($saleList, $i * $step, $step))->execute();
            }*/
            //计算耗时
            $endTime = time();
            $diff = $endTime - $beginTime;
            if ($diff >= 3600) {
                $hour = floor($diff / 3600);
                $diff = $diff % 3600;
                $minute = floor($diff / 60);
                $second = $diff % 60;
                $message = "It takes {$hour} hours,{$minute} minutes and {$second} seconds!";
            } elseif ($diff >= 60) {
                $minute = floor($diff / 60);
                $second = $diff % 60;
                $message = "It takes {$minute} minutes and {$second} seconds!";
            } else {
                $message = "It takes {$diff} seconds!";
            }
            echo date('Y-m-d H:i:s') . " Get stock status data successful! $message\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Get stock status data failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }

    }


    /**
     * @brief 更新主页各人员目标完成度
     */
    public function actionZzTarget()
    {
        $startDate = '2019-05-31';
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $endDate = '2019-08-31';
        //计算时间进度
        $dateRate = round((strtotime($endDate) - strtotime($startDate)) / 86400 / 92, 4);
        //计算销售数据
        $startDate = date('Y-m-01');
        $startDate = date('2019-08-01');
        //$endDate = date('2019-07-31');
        try {
            ConScheduler::getZzTargetData($startDate, $endDate, $dateRate);
            print date('Y-m-d H:i:s') . " INFO:success to update data of zz target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of zz target completion cause of $why \n";
        }
    }


    /**
     * @brief 根据最近两周销售SKU重量更新普源SKU重量
     */
    public function actionUpdateWeight()
    {
        $endDate = date('Y-m-d', strtotime('-1 day'));
        $startDate = date('Y-m-d', strtotime('-9 day', strtotime('-1 day')));
        //计算时间进度
        try {
            Yii::$app->py_db->createCommand("EXEC B_py_ModifyProductWeight '{$startDate}','{$endDate}'")->execute();
            print date('Y-m-d H:i:s') . " INFO:success to update weight of b_goods!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update weight of b_goods cause of $why \n";
        }
    }

    /**
     * 销量变化
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionSalesChangeInTenDays()
    {
        try {
            $stmt = "EXEC z_demo_zongchange @suffix='',@SalerName='',@pingtai='' ";
            $list = Yii::$app->py_db->createCommand($stmt)->queryAll();
            //print_r($data);exit;
            Yii::$app->db->createCommand("TRUNCATE TABLE cache_salesChangeInTenDays")->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_salesChangeInTenDays',
                ['pingtai', 'suffix', 'goodsCode', 'goodsName', 'goodsSkuStatus', 'categoryName', 'salerName', 'salerName2', 'createDate',
                    'jinyitian', 'shangyitian', 'changeOneDay', 'jinwutian', 'shangwutian', 'changeFiveDay', 'jinshitian', 'shangshitian', 'changeTenDay', 'updateDate'],
                $list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update data of sales change in ten days!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales change in ten days cause of $why \n";
        }
    }

    /**
     * 修改普源图片地址
     * Date: 2018-12-29 11:55
     * Author: henry
     */
    public function actionUpdateUrl()
    {
        try {
            $sql1 = "UPDATE B_GoodsSKU SET BmpFileName='http://121.196.233.153/images/'+ case when CHARINDEX('_',sku, 0) = 0 then sku else SUBSTRING(sku,0, CHARINDEX('_',sku, 0)) end +'.jpg'  
                    WHERE BmpFileName LIKE '%Shop Elf%' OR BmpFileName LIKE '%普源%' OR BmpFileName='' OR BmpFileName NOT LIKE '%121.196.233.153%' ";
            $sql2 = "UPDATE B_Goods SET BmpFileName='http://121.196.233.153/images/'+SKU+'.jpg' 
                    WHERE BmpFileName LIKE '%Shop Elf%' OR BmpFileName LIKE '%普源%' OR BmpFileName='' OR BmpFileName NOT LIKE '%121.196.233.153%'";
            Yii::$app->py_db->createCommand($sql1)->execute();
            Yii::$app->py_db->createCommand($sql2)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to update picture url of shopElf!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update picture url of shopElf of $why \n";
        }
    }


    /**
     * 获取最近一个月产品销量
     * Date: 2019-08-08 15:21
     * Author: henry
     */
    public function actionAmtLatestMonth()
    {
        try {
            $sql = "EXEC guest.oauth_getSalesAmtOfLatestMonth";
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();
            Yii::$app->db->createCommand()->truncateTable('data_salesAmtOfLatestMonth')->execute();
            Yii::$app->db->createCommand()->batchInsert('data_salesAmtOfLatestMonth', ['goodsCode', 'createDate', 'developer', 'possessMan1', 'amt', 'updateTime'], $list)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to get sales amt of latest month!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get sales amt of latest month because $why \n";
        }
    }


    /**
     * 备份本月账号产品利润数据
     * 2020-01-09 改到python获取数据
     * Date: 2019-10-10 15:21
     * Author: henry
     */
    public function actionSuffixSkuProfit()
    {
        try {
            $flagArr = [0, 1];//时间类型  0- 交易时间 1- 发货时间
            $step = 300;

            $beginDate = date('Y-m', strtotime('-1 days')) . '-01';//上月或本月1号时间
            $endDate = date('Y-m-d', strtotime('-1 days'));//昨天时间

            $beginDate = '2019-12-01';
            $endDate = '2020-12-31';
            //删除已有时间段内数据，重新获取保存
            Yii::$app->db->createCommand("DELETE FROM cache_suffixSkuProfitReport WHERE orderDate BETWEEN '{$beginDate}' AND '{$endDate}' ")->execute();

            foreach ($flagArr as $v) {
                $sql = "EXEC guest.oauth_reportSuffixSkuProfitBackup $v, '{$beginDate}', '{$endDate}'";
                $list = Yii::$app->py_db->createCommand($sql)->queryAll();
                //var_dump(count($list));exit;
                $count = ceil(count($list) / $step);
                for ($i = 0; $i < $count; $i++) {
                    Yii::$app->db->createCommand()->batchInsert('cache_suffixSkuProfitReport',
                        ['dateFlag', 'orderDate', 'suffix', 'pingtai', 'goodsCode', 'goodsName', 'storeName', 'salerName', 'skuQty', 'saleMoneyRmb', 'refund', 'profitRmb']
                        , array_slice($list, $i * $step, $step))->execute();
                }

            }
            print date('Y-m-d H:i:s') . " INFO:Successful backup suffix sku profit data from time '{$beginDate}' to '{$endDate}'!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:failed backup suffix sku profit data from time '{$beginDate}' to '{$endDate}' because $why \n";
        }
    }

    /**
     * 获取海外仓补货数据
     * Date: 2019-11-06 17:41
     * Author: henry
     */
    public function actionGetRepData()
    {
        try {
            $ukList = Yii::$app->py_db->createCommand("EXEC LY_eBayUKRealWarehouse_Replenishment_20191105")->queryAll();
            //Yii::$app->db->createCommand()->truncateTable('cache_overseasReplenish')->execute();

            //获取UK真仓补货
            Yii::$app->db->createCommand("DELETE  FROM cache_overseasReplenish WHERE type ='UK真仓';")->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                ['SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                    'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                    'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                ],
                $ukList)->execute();
            // 获取AU真仓补货
            Yii::$app->db->createCommand("DELETE  FROM cache_overseasReplenish WHERE type ='AU真仓';")->execute();
            $auList = Yii::$app->py_db->createCommand("EXEC LY_eBayAURealWarehouse_Replenishment_20191105")->queryAll();
            Yii::$app->db->createCommand()->batchInsert('cache_overseasReplenish',
                ['SKU', 'SKUName', 'goodsCode', 'salerName', 'goodsStatus', 'price', 'weight', 'purchaser', 'supplierName',
                    'saleNum3days', 'saleNum7days', 'saleNum15days', 'saleNum30days', 'trend', 'saleNumDailyAve', '399HopeUseNum',
                    'uHopeUseNum', 'totalHopeUseNum', 'uHopeSaleDays', 'hopeSaleDays', 'purchaseNum', 'shipNum', 'purCost', 'shipWeight', 'type'
                ],
                $auList)->execute();

            print date('Y-m-d H:i:s') . " INFO:success to get replenishment data!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get replenishment data because $why \n";
        }
    }

    /**
     * 获取仓库积分数据 并 计算 积分排行榜
     * Date: 2020-03-27 11:12
     * Author: henry
     */
    public function actionWarehouseIntegral($begin = '', $end = '')
    {
        try {
            ConScheduler::getWarehouseIntegralData($begin, $end);
            print date('Y-m-d H:i:s') . " INFO:success to get warehouse integral data of $end\n";
        } catch (Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get warehouse integral data because $why \n";
        }
    }

    /**
     * 获取仓库KPI数据
     * Date: 2021-05-27 11:12
     * Author: henry
     */
    public function actionWarehouseKpi($begin = '', $end = '')
    {
        try {
            ConScheduler::getWarehouseKpiData($begin, $end);
            print date('Y-m-d H:i:s') . " INFO:success to get warehouse integral data of $end \n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " fail to get warehouse KPI data because '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }
    }

    /**
     * 更新普源商品权限
     * 访问方法: php yii site/update-rights
     * @throws \yii\db\Exception
     */
    public function actionUpdateRights()
    {
        $res = Yii::$app->py_db->createCommand("EXEC z_update_B_goods_rights")->execute();
        if ($res) {
            echo date('Y-m-d H:i:s') . " Goods rights update successful!\n";
        } else {
            echo date('Y-m-d H:i:s') . " Goods rights update failed!\n";
        }
    }

    /**
     * 获取价格保护 数据
     * Date: 2021-03-18 11:33
     * Author: henry
     */
    public function actionFetchPriceProtectionData()
    {
        try {
            $suffixPar = ['username' => []];
            $allSuffix = Handler::paramsParse($suffixPar);
            $allSuffix = implode(',', $allSuffix);
            $sql = "EXEC oauth_goodsPriceProtection '{$allSuffix}';";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            $step = 100;
            $max = ceil(count($data) / $step);

            Yii::$app->db->createCommand("TRUNCATE TABLE cache_priceProtectionData;")->execute();
            for ($i = 0; $i < $max; $i++) {
                Yii::$app->db->createCommand()->batchInsert('cache_priceProtectionData',
                    [
                        'goodsCode', 'mainImage', 'storeName', 'plat', 'saler', 'goodsName', 'goodsStatus', 'cate', 'subCate',
                        'salerName', 'createDate', 'number', 'soldNum', 'personSoldNum', 'turnoverDays', 'rate', 'maxPrice',
                        'aveAmt', 'foulSaler', 'amt', 'foulSalerSoldNum', 'updateTime'
                    ],
                    array_slice($data, $i * $step, $step))->execute();
            }
            echo date('Y-m-d H:i:s') . " Get goods price protection data successful!\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Get stock status data failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }

    }


    /**
     * 发货时效备份数据（已发货已归档数据，操作日志处理）
     * Date: 2021-03-29 8:49
     * Author: henry
     */
    public function actionFetchDeliverTimeData($begin = '', $end = '')
    {
        try {
            $beginDate = '2021-01-01';
//            $beginDate = date('Y-m-01', strtotime('last day of -2 month -1 day'));
            $beginDate = date('Y-m-d', strtotime('-1 day'));
            $endDate = date('Y-m-d');
//            var_dump($beginDate,$endDate);exit;
            $sql = "EXEC oauth_warehouse_tools_deliver_trade_backup '{$beginDate}','{$endDate}';";
            $data = Yii::$app->py_db->createCommand($sql)->execute();
            echo date('Y-m-d H:i:s') . " Goods deliver time data successful!\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Get deliver time data failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }

    }

    /**
     * 入库时效备份数据（已发货已归档数据，操作日志处理）
     * Date: 2021-03-29 8:49
     * Author: henry
     */
    public function actionFetchStorageTimeData($beginDate = '', $endDate = '')
    {
        try {
            if(!$beginDate || !$endDate){
                $beginDate = date('Y-m-d', strtotime('-3 day'));
                $endDate = date('Y-m-d');
            }
//            var_dump($beginDate,$endDate);exit;
            // 获取 最近新订单
//            $sql = "EXEC oauth_warehouse_tools_in_storage_time_rate '{$beginDate}','{$endDate}';";
//            Yii::$app->py_db->createCommand($sql)->execute();
            // 获取需要更新1688包裹状态的采购单
            $sqlRate = "SELECT m.nid as OrderNID,AliasName1688,m.alibabaorderid,o.* 
                        FROM oauth_in_storage_time_rate_data_copy o
                        LEFT JOIN CG_StockOrderM m ON o.stockNo = m.BillNumber
                        WHERE ISNULL(stockNo,'')<>'' AND ISNULL(OPDate,'')='' -- AND stockNo = 'GD-2021-08-30-1224' ";
            $list = Yii::$app->py_db->createCommand($sqlRate)-> queryAll();
            //获取1688 账号token信息
            $tokenSql = "select m.AliasName, m.LastSyncTime,m.AccessToken,m.RefreshToken  
                 from S_AlibabaCGInfo m with(nolock)  
                 inner join S_AlibabaCGInfo d with(nolock) on d.mainLoginId=m.loginId  
                 where d.AliasName='caigoueasy'";
            $tokenInfo = Yii::$app->py_db->createCommand($tokenSql)->queryOne();

            foreach ($list as $value){
//                var_dump($value);
                $order = array_merge($value, $tokenInfo);
                $res = ConScheduler::syncOrderInfo1688($order);
                if($res) echo $res;
            }
            echo date('Y-m-d H:i:s') . " Goods deliver time data successful !\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Get deliver time data failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }

    }



    /**
     * 每月更新供应商等级
     * Date: 2021-03-30 8:49
     * Author: henry
     */
    public function actionUpdateSupplierLevel()
    {
        try {
            $lastBeginDate = date('Y-m-01', strtotime('last day of -1 month -1 day'));
            $lastEndDate = date('Y-m-t', strtotime('last day of -1 month -1 day'));
//            print($lastBeginDate);
//            print($lastEndDate);
            $sql = "oauth_data_center_update_supplier_level '{$lastBeginDate}','{$lastEndDate}';";
            $data = Yii::$app->py_db->createCommand($sql)->execute();
            echo date('Y-m-d H:i:s') . " Update supplier level successful!\n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Update supplier level failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }

    }


    public function actionClearDeadlockShopElf()
    {
        try {
            $data = Yii::$app->py_db->createCommand("P_lockinfo 0,0")->queryAll();
            foreach ($data as $v) {
                if ($v['deadlock'] > 100) {
                    Yii::$app->py_db->createCommand("P_lockinfo 1,0")->execute();
                    echo date('Y-m-d H:i:s') . " Clear deadlock successful!\n";
                }else{
                    echo date('Y-m-d H:i:s') . " Processes with no deadlocks to clean up\n";
                }
                break;
            }
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " Clear deadlock failed, cause of '{$e->getMessage()}'. \n";
            //echo $e->getMessage();
        }
    }


    /**
     * @brief 批量更新依赖毛利润报表的命令
     */

    public function actionMacroUpdateHomePage()
    {
        $this->actionProfit();
        $this->actionSalesRanking();
        $this->actionSalesAmt();
        $this->actionSite();
//        $this->actionWarehouseIntegral();
    }

    //////////////////////////////////eBay托管后的新退款 和 店铺杂费////////////////////////////////////////

    public static function actionSyncNewEbayRefund($beginDate = '', $endDate = ''){
        if (!$beginDate && !$endDate){
            $beginDate = date('Y-m-01');
            $endDate = date('Y-m-d', strtotime('-1 days')) . ' 23:59:59';
        }
        $del_sql = "DELETE FROM cache_refund_details_ebay_new WHERE refundTime BETWEEN '{$beginDate}' AND '{$endDate}'";
        Yii::$app->db->createCommand($del_sql)->execute();

        //按订单汇总退款
        $query = EbayRefund::find()
            ->andFilterWhere(['transactionDate' => ['$gte' => $beginDate, '$lte' => $endDate]])
            ->asArray()->all();
        $versionArr = ArrayHelper::getColumn($query, 'orderId');
        $version = implode(',', $versionArr);
        $sql = "EXEC get_ebay_new_refund_order '{$version}' ";
        $data = Yii::$app->py_db->createCommand($sql)->queryAll();
        foreach ($query as $v){
            foreach ($data as $k => $order){
                if($v['orderId'] == $order['version']){
                    $item['refMonth'] = substr($v['transactionDate'],0,7);
                    $item['refund'] = $v['amountValue'];
                    $item['suffix'] = $v['suffix'];
                    $item['currencyCode'] = $v['currency'];
                    $item['refundTime'] = date('Y-m-d H:i:s',strtotime($v['transactionDate']));;
                    $item['refundId'] = $v['transactionId'];
                    $item['orderId'] = $v['orderId'];
                    $item['dateDelta'] = floor((strtotime($v['transactionDate']) - strtotime($order['orderTime']))/24/3600);
                    $item['expressWay'] = $order['expressWay'];
                    $item['goodsCode'] = $order['goodsCode'];
                    $item['goodsName'] = $order['goodsName'];
                    $item['goodsSku'] = $order['sku'];
                    $item['mergeBillId'] = $order['mergeBillId'];
                    $item['orderCountry'] = $order['orderCountry'];
                    $item['orderTime'] = $order['orderTime'];
                    $item['platform'] = $order['platform'];
                    $item['orderId'] = $order['orderId'];
                    $item['refundZn'] = $v['amountValue'] * $order['exchangeRate'];
                    $item['storeName'] = $order['storeName'];
                    $item['tradeId'] = $order['NID'];
//                    var_dump($item);exit;

                    Yii::$app->db->createCommand()->insert('cache_refund_details_ebay_new',$item)->execute();

                    unset($data[$k]);
                    break;
                }
            }
//            var_dump($item);exit;
//            var_dump(count($data));
        }

        print date('Y-m-d H:i:s') . " INFO:success to sync ebay refund \n";
    }


    //////////////////////////////////////////////////////////////////////////
    /**
     * 计算 昨日 收益
     * Date: 2021-08-05 11:12
     * Author: henry
     */
    public function actionIncomeGet()
    {
        try {
            $sql = "CALL u_fund.income_calculate;";





            Yii::$app->db->createCommand($sql)->execute();
            print date('Y-m-d H:i:s') . " INFO:success to get income \n";
        } catch (\Exception $e) {
            echo date('Y-m-d H:i:s') . " fail to get income because '{$e->getMessage()}'. \n";
            echo $e->getMessage();
        }
    }


}
