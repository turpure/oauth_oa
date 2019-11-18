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
    public function actionDispatch($type='new')
    {
        $developers = ProductEngine::getDevelopers();
        shuffle($developers);
        $products = ProductEngine::getProducts($type);
        $engine = new ProductEngine($products, $developers);
        $ret = $engine->dispatch();
        foreach ($ret as $pro => $dev) {
            print_r($pro);
            print_r(':');
            print_r($dev);
            print_r("\n");
        }

    }



    /**
     * 每日推荐
     */
    public function actionDailyRecommend()
    {   //默认ebay平台
        try {
            $this->actionDispatch('new');
            $this->actionDispatch('hot');
            //更新每日推荐的推荐人
            ConScheduler::getAndSetRecommendToPersons();
        } catch (\Exception $why) {
            print $why->getMessage();
            exit;
        }
    }


}