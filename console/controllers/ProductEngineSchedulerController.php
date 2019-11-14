<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use console\models\ConScheduler;
use console\models\ProductEngine;
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
       // 新品打标签
        ProductEngine::setProductTag('new');

        //热销产品打标签
        ProductEngine::setProductTag('hot');
    }




    /**
     * 给开发打标签
     */
    public function actionPersonTag()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_allot_rule');
        $personTags = $col->find();
        $ret = [];
        foreach ($personTags as $pt) {
            $row['person'] = $pt['username'];
            $row['tag'] = empty($pt['category']) ? []: $pt['category'];
            $ret[] = $row;
        }
         return $ret;
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