<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */
namespace console\controllers;

use yii\console\Controller;

use Yii;
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
            if(!$ret) {
                throw new \Exception('fail to truncate table');
            }
            $dateFlags = [0, 1];
            $dateRanges = [0, 1, 2];
            foreach ($dateFlags as $flag) {
                foreach ($dateRanges as $range) {
                    $updateSql = "exec meta_saleProfit $flag, $range";
                    $re = $con->createCommand($updateSql)->execute();
                    if(!$re) {
                        throw new \Exception('fail to update data');
                    }
                }
            }
            print date('Y-m-d H:i:s')."INFO:success to get sale-report data\n";
            $trans->commit();
        }
        catch (\Exception $why) {
            print date('Y-m-d H:i:s')."INFO:fail to get sale-report data cause of $why \n";
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
            print date('Y-m-d H:i:s')."INFO:success to get sku out of stock!\n";
        }
        catch (\Exception $why) {
            print date('Y-m-d H:i:s')."INFO:fail to get sku out of stock cause of $why \n";
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
            print date('Y-m-d H:i:s')." INFO:success to get data of target completion!\n";
        }
        catch (\Exception $why) {
            print date('Y-m-d H:i:s')." INFO:fail to get data of target completion cause of $why \n";
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
                    ['suffix','goodsCode','goodsName','lastNum','lastAmt','num','amt','numDiff','amtDiff','createDate'],
                    $list
                )->execute();

            print date('Y-m-d H:i:s')." INFO:success to update data of sales change!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s')." INFO:fail to update data of sales change cause of $why \n";
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
        $beginDate = date('Y-m-d',strtotime('-30 days'));
        $endDate = date('Y-m-d',strtotime('-1 days'));
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
            Yii::$app->db->createCommand()->batchInsert('site_goods', ['profit', 'platform','goodsCode','goodsName','endTime','img'], $list)->execute();

            print date('Y-m-d H:i:s')." INFO:success to update data of today pros!\n";
        }
        catch (\Exception $why) {
            print date('Y-m-d H:i:s')." INFO:fail to update data of today pros cause of $why \n";
        }
    }

    /**
     * 更新主页利润增长表
     * Date: 2019-01-11 11:55
     * Author: henry
     */
    public function actionProfit()
    {
        try {
            //获取销售人员毛利
            //$sql = "CALL oauth_site_profit;";
            //$saleList = Yii::$app->db->createCommand($sql)->queryAll();
            //获取开发人员毛利
            $sql = "CALL oauth_site_profit;";
            $list = Yii::$app->db->createCommand($sql)->queryAll();
            //print_r($list);exit;
            //清空数据表并插入新数据
            Yii::$app->db->createCommand("TRUNCATE TABLE site_profit")->execute();
            Yii::$app->db->createCommand()->batchInsert('site_profit', ['username','depart','role','storename','lastProfit','profit', 'rate','dateRate','updateTime'], $list)->execute();

            print date('Y-m-d H:i:s')." INFO:success to update data of profit changes!\n";
        }
        catch (\Exception $why) {
            print date('Y-m-d H:i:s')." INFO:fail to update data of profit changes cause of $why \n";
        }

    }


}