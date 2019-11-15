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

    public function actionSite()
    {
        try {
            //备份上月开发目标完成度 TODO  备份数据的加入
            $condition = [
                'dateFlag' => 1,
                'beginDate' => '2019-10-01',
                'endDate' => '2019-10-31',
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
                        'month' => 10,
                        'updateTime' => '2019-11-08'
                    ]
                )->execute();
            }

            print date('Y-m-d H:i:s') . " INFO:success to get data of target completion!\n";
        } catch (\Exception $why) {
            print date('Y-m-d H:i:s') . " INFO:fail to get data of target completion cause of $why \n";
        }
    }


    public function actionTest()
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



    //给开发分配所有产品  2019-11-14
    public function actionTest2()
    {   //默认ebay平台
        $start = time();
        try {
            $plat = 'ebay';
            if($plat == 'ebay'){
                $typeArr = ['new','hot'];
                foreach ($typeArr as $type){
                    ConScheduler::getProducts($type, $plat);
                }
            }
            $time = time() - $start;
            if($time >= 3600){
                $day = $time/3600;
                $mi = ($time - $day*3600)/60;
                $sec = $time - $day*3600 - $mi*60;
                print "success, it costs '{$day} days, '{$mi}' minutes, '{$sec}' seconds!";
            }elseif ($time >= 60){
                $mi = $time/60;
                $sec = $time - $mi*60;
                print "success, it costs '{$mi}' minutes, '{$sec}' seconds!";
            }else{
                print "success, it costs '{$time}' seconds!";
            }
        } catch (\Exception $why) {
            print $why->getMessage();
            exit;
        }

    }


    //给开发分配指定数量产品  2019-11-14
    public function actionAllotProduct()
    {   //默认ebay平台
        try {
            $mongodb = Yii::$app->mongodb;
            $typeArr = ['new', 'hot'];
            $devList = $mongodb->getCollection('ebay_allot_rule')->find();
            foreach($devList as $dev){
                $proNum = $dev['productNum'] ? $dev['productNum'] : 5;
                //var_dump($proNum);exit;
                foreach ($typeArr as $value){
                    $proList = (new \yii\mongodb\Query())->from('ebay_all_recommended_product')
                        ->select(['_id' => 0])
                        ->andFilterWhere(['productType' => $value])  //类型  新品  热销
                        ->andFilterWhere(['receiver' => $dev['username']])
                        ->andFilterWhere(['recommendDate' => ['$regex' => date('Y-m-d')]])  //筛选当天获取得新数据
                        ->limit($proNum)->all();
                    foreach ($proList as $v){
                        $query = (new \yii\mongodb\Query())->from('ebay_recommended_product')
                            ->andFilterWhere(['itemId' => $v['itemId']])->one();
                        if(!$query){
                            $mongodb->getCollection('ebay_recommended_product')->insert($v);
                        }
                    }
                }
            }
            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
            print 'success';
        } catch (\Exception $why) {
            print $why->getMessage();
            exit;
        }

    }




}