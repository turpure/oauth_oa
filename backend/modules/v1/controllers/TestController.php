<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020-05-29
 * Time: 16:02
 * Author: henry
 */
/**
 * @name TestController.php
 * @desc PhpStorm.
 * @author: Create by henry
 * @since: Created on 2020-05-29 16:02
 */


namespace backend\modules\v1\controllers;


use backend\models\OaGoodsinfo;
use backend\models\OaJoomSuffix;
use backend\models\OaSmtGoods;
use backend\models\OaSmtGoodsSku;
use backend\models\OaWishGoods;
use backend\models\OaWishGoodsSku;
use backend\modules\v1\models\ApiGoodsinfo;
use backend\modules\v1\utils\Helper;
use Yii;
class TestController extends AdminController
{
    public $modelClass = 'backend\models\OaGoodsinfo';

    public $serializer = [
        'class' => 'yii\rest\Serializer',
        'collectionEnvelope' => 'items',
    ];

    public  function actionTest(){
        try {

            //$str = "a:9:{i:0;a:11:{s:3:\"sku\";s:14:\"7A553701@#E278\";s:5:\"color\";s:6:\"Yellow\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_1_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:1;a:11:{s:3:\"sku\";s:14:\"7A553702@#E278\";s:5:\"color\";s:5:\"Green\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_2_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:2;a:11:{s:3:\"sku\";s:14:\"7A553703@#E278\";s:5:\"color\";s:5:\"Black\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_3_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:3;a:11:{s:3:\"sku\";s:14:\"7A553704@#E278\";s:5:\"color\";s:6:\"Orange\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_4_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:4;a:11:{s:3:\"sku\";s:14:\"7A553705@#E278\";s:5:\"color\";s:8:\"Rose red\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_5_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:5;a:11:{s:3:\"sku\";s:14:\"7A553706@#E278\";s:5:\"color\";s:4:\"Navy\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_6_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:6;a:11:{s:3:\"sku\";s:14:\"7A553707@#E278\";s:5:\"color\";s:10:\"Light blue\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_7_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:7;a:11:{s:3:\"sku\";s:14:\"7A553708@#E278\";s:5:\"color\";s:3:\"Red\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_8_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}i:8;a:11:{s:3:\"sku\";s:14:\"7A553709@#E278\";s:5:\"color\";s:4:\"Pink\";s:4:\"size\";N;s:9:\"inventory\";s:5:\"10000\";s:5:\"price\";d:2.9900000000000002131628207280300557613372802734375;s:8:\"shipping\";i:1;s:4:\"msrp\";i:8;s:13:\"shipping_time\";s:4:\"7-21\";s:10:\"main_image\";s:55:\"https://www.tupianku.com/view/full/10023/7A5537-_9_.jpg\";s:23:\"localized_currency_code\";s:3:\"CNY\";s:15:\"localized_price\";d:20.57000000000000028421709430404007434844970703125;}}";
            //var_dump(json_encode(unserialize($str)));exit;

            //测试wish接口
            $sql = "SELECT AccessToken as access_token,aliasname as suffix FROM S_WishSyncInfo WHERE  
               aliasname is not null
                and  AliasName like '%WIS02-zone%'  and aliasname not in 
              (select DictionaryName from B_Dictionary where CategoryID=12 and used=1 and FitCode='Wish')";
            $tokens = Yii::$app->py_db->createCommand($sql)->queryAll();
//            var_dump($tokens);exit;
            $data = [
                'name' => '', 'description' => '', 'tags' => '', 'sku' => '', 'color' => '', 'size' => '',
                'inventory' => '', 'price' => '', 'localized_price' => '', 'shipping' => '', 'localized_shipping' => '',
                'localized_currency_code' => 'CNY', 'country_shipping_prices' => '', 'msrp' => '', 'shipping_time' => '',
                'main_image' => '', 'parent_sku' => '', 'requested_product_brand_id' => '', 'landing_page_url' => '',
                'upc' => '', 'extra_images' => '', 'clean_image' => '', 'max_quantity' => '', 'length' => '', 'width' => '',
                'height' => '', 'weight' => '', 'declared_name' => '', 'declared_local_name' => '', 'pieces' => '',
                'declared_value' => '', 'hscode' => '', 'origin_country' => '',
                'has_powder' => false, 'has_liquid' => false, 'has_battery' => false, 'has_metal' => false,
            ];
            $db = Yii::$app->get('mongodb2');
            $cur = (new \yii\mongodb\Query())
                ->from('wish_template')
                ->andFilterWhere(['_id' => '5edef9f70a6d8a174c0063f2'])
                ->one($db);
            unset($cur['_id'], $cur['creator'], $cur['created'], $cur['updated']);

//            var_dump($cur);exit;
            $url= "https://merchant.wish.com/api/v2/product/multi-get";
            foreach ($tokens as $value){
                $cur['access_token'] = $value['access_token'];
//                var_dump($cur);exit;
                $res = Helper::curlRequest($url, $value);
                var_dump($res);exit;
            }









        } catch (\Exception $why) {
            return ['code' => 400, 'message'=>$why->getMessage()];
        }
    }

}
