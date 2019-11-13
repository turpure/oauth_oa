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

    public function actionTest2()
    {   //默认ebay平台
        try {
            /*
            $sql = "SELECT u.username,a.item_name 
                    FROM `user` u
                    left Join auth_assignment a ON a.user_id=u.id
                    WHERE u.`status`=10 AND item_name='产品开发';";
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            $allDeveloperList = ArrayHelper::getColumn($query,'username');
            //print_r($allDeveloperList);exit();
            $plat = 'ebay';
            if($plat == 'ebay'){
                $type = 'new';
                //有产品类目限制的开发优先获取产品
                $devList = [
                    '陈微微','刘珊珊','胡小红','杨笑天','李星','史新慈','詹莹莹','常金彩','廖露露','王丽6','毕郑强','王雪姣','张崇','崔明宽','邹雅丽','张小辉','刘霄敏','徐胜东','杨晶媛','刘爽','潘梦晗','胡宁','辜星燕','徐含','张杜娟','王咏','宋现中','王漫漫','李永恒',
                ];
                //新品
                ConScheduler::getDevelopRecommendProduct($devList, $type, $plat);

                //老品
                ConScheduler::getDevelopRecommendProduct($devList, 'hot', $plat);

                //没有有产品类目限制的开发再获取产品
                //$devList = ['杨笑天', '宋现中', '胡小红',];
                //ConScheduler::getDevelopRecommendProduct($devList, $type, $plat);
            }
            */
            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
        } catch (\Exception $why) {
            print $why->getMessage();
            exit;
        }

    }


}