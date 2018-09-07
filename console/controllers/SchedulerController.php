<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */
namespace console\controllers;

use yii\console\Controller;


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
            print date('Y-m-d h:i:s')."INFO:success to get sale-report data\n";
            $trans->commit();
        }
        catch (\Exception $why) {
            print date('Y-m-d h:i:s')."INFO:fail to get sale-report data cause of $why \n";
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
            print date('Y-m-d h:i:s')."INFO:success to get sku out of stock!\n";
        }
        catch (\Exception $why) {
            print date('Y-m-d h:i:s')."INFO:fail to get sku out of stock cause of $why \n";
        }
    }
}