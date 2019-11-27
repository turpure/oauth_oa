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
            $row['deliveryLocation'] = $pt['deliveryLocation'];
            $ret[] = $row;
        }
         return $ret;
    }

    /**
     * 按照分配算法分配产品
     * @param $type
     */
    public function actionDispatchAll($type='hot')
    {
        //var_dump(in_array('123', [], false));exit;
        $developers = ProductEngine::getDevelopers();
        shuffle($developers);
        $products = ProductEngine::getProducts($type);
        $engine = new ProductEngine($products, $developers);
        $ret = $engine->dispatch();
        foreach ($ret as $itemId => $productResult) {
            $row = ProductEngine::pullData($itemId, $productResult);
            ProductEngine::pushData($row, 'all');
        }
    }


    /**
     * 按数量分配给每个开发员
     * @param string $type
     */
    public function actionDispatchToPerson($type='hot')
    {
        $ret = ProductEngine::dispatchToPersons($type);
        foreach ($ret as $itemId => $productResult) {
            $row = ProductEngine::pullData($itemId, $productResult);
            ProductEngine::pushData($row, 'person');
        }


    }


    /**
     * 每日推荐
     */
    public function actionDailyRecommend()
    {   //默认ebay平台
        try {

             //新品打标签
            ProductEngine::setProductTag('new');

            //热销产品打标签
            ProductEngine::setProductTag('hot');

            // 分配所有产品
            $this->actionDispatchAll('new');
            $this->actionDispatchAll('hot');

            // 分配产品给开发
            $this->actionDispatchToPerson('new');
            $this->actionDispatchToPerson('hot');

            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
        } catch (\Exception $why) {
            print 'fail to recommend cause of ' . $why->getMessage();
        }
    }


}