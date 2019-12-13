<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019-08-15
 * Time: 16:49
 * Author: henry
 */

/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2019-08-15 16:49
 */


namespace console\controllers;
use backend\modules\v1\utils\Helper;
use console\models\ProductEngine;


use backend\models\OaDataMineDetail;
use backend\modules\v1\models\ApiReport;
use console\models\ConScheduler;
use yii\console\Controller;
use yii\db\Exception;
use yii\db\Query;
use Yii;
use yii\helpers\ArrayHelper;

class TestController extends Controller
{

    /**
     * 备份上个月目标完成度数据
     * Date: 2019-12-05 11:52
     * Author: henry
     */
    public function actionSite()
    {
        try {
            //备份上月开发目标完成度 TODO  备份数据的加入
            $condition = [
                'dateFlag' => 1,
                'beginDate' => '2019-11-01',
                'endDate' => '2019-11-30',
                'seller' => '胡小红,廖露露,常金彩,刘珊珊,王漫漫,陈微微,杨笑天,李永恒,崔明宽,张崇,史新慈,邹雅丽,杨晶媛',
            ];
            $devList = ApiReport::getDevelopReport($condition);
            //print_r($devList);exit;
            foreach ($devList as $value) {
                Yii::$app->db->createCommand()->insert(
                    'site_targetAllBackupData',
                    // ['username','role','profitZn','month','updateTime'],
                    [
                        'username' => $value['salernameZero'],
                        'role' => '开发',
                        'profitZn' => $value['netprofittotal'],
                        'month' => 11,
                        'updateTime' => '2019-12-06'
                    ]
                )->execute();
            }

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }

    /**
     * 拉取eBay账号及大小ppp
     * Date: 2019-12-05 11:53
     * Author: henry
     */
    public function actionGetEbayPp()
    {
        $query = (new Query())->select('ebayName ebay,h.paypal big,l.paypal small')
            ->from('proCenter.oa_ebaySuffix es')
            ->leftJoin('proCenter.oa_paypal h', 'es.high=h.id')
            ->leftJoin('proCenter.oa_paypal l', 'es.low=l.id')->all();
        //print_r($query);exit;

        try {
            \Yii::$app->py_db->createCommand()->truncateTable('guest.t1')->execute();
            $res = \Yii::$app->py_db->createCommand()->batchInsert('guest.t1', ["ebay", "big", "small"], $query)->execute();
            print_r($res);
            echo "\r\n";
            //exit;

        } catch (Exception $e) {
            print_r($e->getMessage());
            exit;
        }


    }



    /**
     * 根据产品中心推荐数据处理Mongo认领状态
     */
    public function actionUpdateAccept()
    {
        $db = Yii::$app->mongodb;
        $beginDate = '2019-11-14';
        $endDate = '2019-11-30 23:59:59';

        //清空认领状态
        $db->getCollection('ebay_recommended_product')
            ->update(['recommendDate' => ['$gte' => $beginDate, '$lte' => $endDate]],['accept' => null]);
        //获取产品中心认领产品
        $sql = "SELECT * FROM proCenter.oa_goods WHERE introducer='proEngine'";
        if($beginDate && $endDate){
            $sql .= " AND createDate BETWEEN '$beginDate' AND '$endDate' ";
        }
        $data = Yii::$app->db->createCommand($sql)->queryAll();

        foreach ($data as $v){
            $recommendId = explode('.', $v['recommendId']);
            $product = $db->getCollection('ebay_recommended_product')
                ->find(["_id" => $recommendId[1]]);
            foreach ($product as $ele){
                //var_dump($v['developer']);
                //var_dump(array_values($ele['receiver']));
                if(!$ele) break;
                if(in_array($v['developer'], $ele['receiver'])){
                    $db->getCollection('ebay_recommended_product')->update(['_id' => $ele['_id']], ['accept' => [$v['developer']]]);
                }
            }
        }
        $step = (strtotime($endDate) + 1 - strtotime($beginDate)) / 3600 / 24;
        for ($i = 0; $i < $step; $i++) {
            if ($i == 0) {
                ConScheduler::getAndSetRecommendToPersons($beginDate);
            } else {
                $day = date('Y-m-d', strtotime("+1 days", strtotime($beginDate)));
                ConScheduler::getAndSetRecommendToPersons($day);
            }

        }
    }


    public function actionTest(){
        date('Y-m-d');
    }



}