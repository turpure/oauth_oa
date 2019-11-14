<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use console\models\ConScheduler;
use yii\console\Controller;

use Yii;
use yii\helpers\ArrayHelper;

class ProductEngineSchedulerController extends Controller
{

    /**
     * 给产品打标签
     */
    public function actionProductTag()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_new_product');
        $today = date('Y-m-d');
        $products = $col->find(['recommendDate' => ['$regex' => $today]]);
        foreach ($products as $pts) {
            $catName = $pts['cidName'];
            var_dump($catName);
        }

    }

    private function getTagCat()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('');

    }


    /**
     * 给开发打标签
     */
    public function actionPersonTag()
    {

    }

    /**
     *按照分配算法分配
     */
    public function actionDispatch()
    {

    }

    /**
     * 分配算法
     */
    private function dispatch()
    {

    }

    /**
     * 每日推荐
     */
    public function actionDailyRecommend()
    {   //默认ebay平台
        try {

            $sql = "SELECT u.username,a.item_name 
                    FROM `user` u
                    left Join auth_assignment a ON a.user_id=u.id
                    WHERE u.`status`=10 AND item_name='产品开发';";
            $query = Yii::$app->db->createCommand($sql)->queryAll();
            $allDeveloperList = ArrayHelper::getColumn($query,'username');
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

            }
            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
        } catch (\Exception $why) {
            print $why->getMessage();
            exit;
        }

    }


}