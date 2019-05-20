<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use backend\models\OaGoodsinfo;
use yii\console\Controller;

use Yii;
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
        $con = \Yii::$app->py_db;
        $sql = "EXEC oauth_target_procedure";
        try {
            $con->createCommand($sql)->execute();
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
        $sql = "EXEC oauth_salesChangeOfTwoDateBlock";
        try {
            $list = Yii::$app->py_db->createCommand($sql)->queryAll();

            Yii::$app->db->createCommand()->batchInsert(
                'cache_sales_change',
                ['suffix', 'goodsCode', 'goodsName', 'lastNum', 'lastAmt', 'num', 'amt', 'numDiff', 'amtDiff', 'createDate'],
                $list
            )->execute();

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
        $lastBeginDate = date('Y-m-01', strtotime('-1 month'));
        $lastEndDate = date('Y-m-t', strtotime('-1 month'));
        $beginDate = date('Y-m-01');
        $endDate = date('Y-m-d', strtotime('-1 day'));
        try {
            //获取开发人员上月和本月毛利的初步数据.
            $devSql = "EXEC oauth_siteDeveloperProfit";
            $devData = Yii::$app->py_db->createCommand($devSql)->queryAll();
            //初步数据保存到Mysql数据库cache_developProfitTmp，进一步进行计算
            Yii::$app->db->createCommand('TRUNCATE TABLE cache_developProfitTmp')->execute();
            Yii::$app->db->createCommand()->batchInsert('cache_developProfitTmp',
                ['tableType','timegroupZero','salernameZero','salemoneyrmbusZero','salemoneyrmbznZero','costmoneyrmbZero',
                    'ppebayusZero','ppebayznZero','inpackagefeermbZero','expressfarermbZero','devofflinefeeZero','devOpeFeeZero',
                    'netprofitZero','netrateZero','timegroupSix','salemoneyrmbusSix','salemoneyrmbznSix','costmoneyrmbSix',
                    'ppebayusSix','ppebayznSix','inpackagefeermbSix','expressfarermbSix','devofflinefeeSix','devOpeFeeSix',
                    'netprofitSix','netrateSix','timegroupTwe','salemoneyrmbusTwe','salemoneyrmbznTwe','costmoneyrmbTwe',
                    'ppebayusTwe','ppebayznTwe','inpackagefeermbTwe','expressfarermbTwe','devofflinefeeTwe','devOpeFeeTwe',
                    'netprofitTwe','netrateTwe','salemoneyrmbtotal','netprofittotal','netratetotal','devRate','devRate1','devRate5','type'],
                $devData)->execute();

            //插入销售和开发毛利数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_profit(0);")->execute();

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
        try {

            //插入销售销售额数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_amt;")->execute();

            //获取开发人员销售额
            $devSql = "EXEC oauth_siteDeveloperAmt";
            $devList = Yii::$app->py_db->createCommand($devSql)->queryAll();

            //插入开发销售数据
            Yii::$app->db->createCommand()->batchInsert('site_sales_amt',
                ['username', 'depart', 'role', 'lastAmt', 'amt', 'rate', 'dateRate', 'updateTime'],
                $devList)->execute();

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
    public function actionSalesRanking(){
        try {
            //插入销售毛利数据(存储过程插入)
            Yii::$app->db->createCommand("CALL oauth_site_profit(1);")->execute();
            print date('Y-m-d H:i:s') . " INFO:success to update data of sales profit ranking!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to update data of sales profit ranking cause of $why \n";
        }
    }

    /** 备货产品计算
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
        //获取订单数详情
        $orderList = Yii::$app->py_db->createCommand("EXEC oauth_stockGoodsNumber '" . $startDate . "','" . $endDate . "','';")->queryAll();
        //获取开发产品列表
        $goodsSql = "SELECT developer,goodsCode,stockUp FROM proCenter.oa_goodsinfo gs
                      WHERE devDatetime BETWEEN '{$startDate}' AND '{$endDate}' AND ifnull(mid,0)=0;";
        $goodsList = Yii::$app->db->createCommand($goodsSql)->queryAll();
        //获取开发员备货产品书，不备货产品书，总产品数
        $list = Yii::$app->db->createCommand("CALL proCenter.oa_stockGoodsNum('{$startDate}','{$endDate}');")->queryAll();
        //统计出单数，爆旺款数量
        $developer = [];
        foreach ($goodsList as $k => $v) {
            $orderNum = 0;
            foreach ($orderList as $value){
                if($v['goodsCode'] == $value['goodsCode']){
                    $orderNum += $value['l_qty'];//出单数
                    $v['goodsStatus'] = $value['goodsStatus'];
                }else{
                    $v['goodsStatus'] = '';
                }
            }
            $v['orderNum'] = $orderNum;
            $developer[$k] = $v;
        }
        $orderNumList = $nonOrderNumList = [];
       foreach($list as $k => $value){
            $stockOrderNum = $nonStockOrderNum = $hot = $exu = $nonHot = $nonExu = 0;
            foreach ($developer as $v){
                if($value['username'] == $v['developer']){
                    $nonStockOrderNum = $v['stockUp'] == '否' ? $nonStockOrderNum + 1 : $stockOrderNum;
                    $stockOrderNum = $v['stockUp'] == '是' ? $stockOrderNum + 1 : $stockOrderNum;
                    $hot = $v['goodsStatus'] == '爆款' && $v['stockUp'] == '是' ? $hot + 1 : $hot;
                    $exu = $v['goodsStatus'] == '旺款' && $v['stockUp'] == '是' ? $exu + 1 : $exu;
                    $nonHot = $v['goodsStatus'] == '爆款' && $v['stockUp'] == '否' ? $nonHot + 1 : $nonHot;
                    $nonExu = $v['goodsStatus'] == '旺款' && $v['stockUp'] == '否' ? $nonExu + 1 : $nonExu;
                }
            }
           //计算 备货和不备货的爆旺款率
           $hotAndExuRate = $value['stockNum'] == 0 ? 0 : round(($hot+$exu)*1.0/$value['stockNum'], 2)*100;
           $nonHotAndExuRate = $value['nonStockNum'] == 0 ? 0 : round(($nonHot+$nonExu)*1.0/$value['nonStockNum'], 2)*100;
           //计算 备货和不备货的出单率
           $orderRate = $value['stockNum'] == 0 ? 0 : round($stockOrderNum*1.0/$value['stockNum'], 2);
           $nonOrderRate = $value['nonStockNum'] == 0 ? 0 : round($nonStockOrderNum*1.0/$value['nonStockNum'], 2);
           //计算 出单率评分
           $rate1 = round(max(1-max((80-$orderRate),0)*0.025,0.5),2);
           $nonRate1 = round(max(1-max((80-$nonOrderRate),0)*0.025,0.5),2);
           //计算 爆旺款率评分
           $rate2 = round(2-max((30-$hotAndExuRate)*0.04,0),2);
           $nonRate2 = round(2-max((30-$nonHotAndExuRate)*0.04,0),2);

           $item1['developer'] = $item2['developer'] = $value['username'];
           $item1['number'] = (int)$value['stockNum'];
           $item1['orderNum'] = $stockOrderNum;
           $item1['hotStyleNum'] = $hot;
           $item1['exuStyleNum'] = $exu;
           $item1['rate1'] = $rate1;
           $item1['rate2'] = $rate2;
           $item1['createDate'] = $end;
           $item1['isStock'] = 'stock';

           $item2['number'] = (int)$value['nonStockNum'];
           $item2['orderNum'] = $nonStockOrderNum;
           $item2['hotStyleNum'] = $nonHot;
           $item2['exuStyleNum'] = $nonExu;
           $item2['rate1'] = $nonRate1;
           $item2['rate2'] = $nonRate2;
           $item2['createDate'] = $end;
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
                    SET r.stockNumThisMonth= s.stockNumThisMonth,r.stockNumLastMonth =s. stockNumLastMonth
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
                    AND substring(date_add(r.createDate, interval -1 month),1,10) = substring(s.createDate,1,10)  ";
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
            echo date('Y-m-d H:i:s')." (new)The stock data update successful!\n";;
        }catch (\Exception $e){
            $tran->rollBack();
            echo date('Y-m-d H:i:s')." (new)The stock data update failed!\n";
        }

    }


    /** 查询wish平台商品状态、采购到货天数并更新oa_goodsinfo表数据
     * Date: 2019-05-14 16:54
     * Author: henry
     * @throws \yii\db\Exception
     */
    public function actionWish(){
        $res = Yii::$app->py_db->createCommand("P_oa_updateGoodsStatusToTableOaGoodsInfo")->queryAll();
        //更新 oa_goodsinfo 表的stockDays，goodsStatus
        foreach ($res as $v){
            Yii::$app->db->createCommand()->update('proCenter.oa_goodsinfo',$v,['goodsCode' => $v['goodsCode']])->execute();
        }

        // 更新 oa_goodsinfo 表的wishPublish
        $sql = "UPDATE proCenter.oa_goodsinfo SET wishPublish=
	            CASE WHEN stockDays>0 AND storeName='义乌仓' AND IFNULL(dictionaryName,'') not like '%wish%' and  (completeStatus NOT LIKE '%Wish%' OR completeStatus IS NULL) then 'Y' 
			          ELSE 'N' END ";
        $ss = Yii::$app->db->createCommand($sql)->execute();
        if($ss){
            echo date('Y-m-d H:i:s')." Update successful!\n";
        }else{
            echo date('Y-m-d H:i:s')." Update failed!\n";
        }
    }

}