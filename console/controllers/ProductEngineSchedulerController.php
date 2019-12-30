<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2018-08-30 14:30
 */

namespace console\controllers;

use console\models\ConScheduler;
use console\models\ProductEngine;
use console\models\WishProductEngine;
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
     * 更新每日推荐的推荐人
     */
    public function actionSetRecommendToPersons()
    {
        $day = '2019-12-12';
        ConScheduler::getAndSetRecommendToPersons($day);
    }


    /**
     * 图像识别
     */
    public function actionDetectImages()
    {
        try {
            ProductEngine::detectImages();
        }
        catch (\Exception $why) {
            print_r('fail to detect image cause of '. $why->getMessage());

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

             //分配产品给开发
            $this->actionDispatchToPerson('new');
            $this->actionDispatchToPerson('hot');

            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
        } catch (\Exception $why) {
            print 'fail to recommend cause of ' . $why->getMessage();
        }
    }


    //====================================================================================================
    //Wish推荐
    /**
     * 给产品打标签
     */
    public function actionWishProductTag()
    {
        // 新品打标签
        WishProductEngine::setProductTag('new');

        //热销产品打标签
        WishProductEngine::setProductTag('hot');
    }

    /**
     * 按照分配算法分配产品
     * @param $type
     */
    public function actionWishDispatchAll($type='new')
    {
        $developers = ProductEngine::getDevelopers('运营一部');
        shuffle($developers);
        $products = WishProductEngine::getProducts($type);
        $engine = new WishProductEngine($products, $developers);
        $ret = $engine->dispatch();
        foreach ($ret as $itemId => $productResult) {
            $row = WishProductEngine::pullData($itemId, $productResult);
            WishProductEngine::pushData($row, 'all');
        }
    }

    /**
     * 按数量分配给每个开发员
     * @param string $type
     */
    public function actionWishDispatchToPerson($type='new')
    {
        $ret = WishProductEngine::dispatchToPersons($type);
        foreach ($ret as $itemId => $productResult) {
            $row = WishProductEngine::pullData($itemId, $productResult);
            WishProductEngine::pushData($row, 'person');
        }

    }

    /**
     * 更新每日推荐的推荐人
     */
    public function actionWishSetRecommendToPersons()
    {
        //$day = '2019-12-12';
        $day = date('Y-m-d');
        WishProductEngine::getAndSetRecommendToPersons($day);
    }


    /**
     * 每日推荐
     */
    public function actionWishDailyRecommend()
    {   //默认ebay平台
        try {

            //新品打标签
            //WishProductEngine::setProductTag('new');

            //热销产品打标签
            //WishProductEngine::setProductTag('hot');

            // 分配所有产品
            $this->actionWishDispatchAll('new');
            $this->actionWishDispatchAll('hot');

            //分配产品给开发
            $this->actionWishDispatchToPerson('new');
            $this->actionWishDispatchToPerson('hot');

            //更新每日推荐的推荐人
            $this->actionWishSetRecommendToPersons();
        } catch (\Exception $why) {
            print 'fail to recommend cause of ' . $why->getMessage();
        }
    }



}