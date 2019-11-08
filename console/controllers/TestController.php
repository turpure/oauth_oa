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
                'beginDate' => '2019-09-01',
                'endDate' => '2019-09-30',
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
                        'month' => 9,
                        'updateTime' => '2019-10-09'
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
        $type = 'new';// 先分配新品
        $today = date('Y-m-d H:i:s');
        $db = Yii::$app->mongodb;
        $devList = [
            '刘珊珊', '陈微微', '王漫漫',
        ];
        //结果集保存到数据库中 格式如下
        //$resList = [   ['name' => 'A','type' => 'A1', 'dev' => ['A']]     ];
        try {
            for ($i = 1; $i < 6; $i++) {  //循环5遍  ，每遍选一个产品
                //遍历开发员
                foreach ($devList as $value) {
                    //判断当前开发所选产品数
                    //从数据库结果集中查找当前开发所选产品数量
                    $num = $db->getCollection('ebay_recommended_product')
                        ->count([
                            'productType' => $type,                               //新品推荐
                            'dispatchDate' =>  ['$regex' => $today],             //当天推荐
                            //'recommendDate' =>  ['$regex' => '2019-11-07'],
                            'receiver' => [$value]                               //当前开发的推荐
                        ]);
                    if ($num > 4) { //已经选够五款产品,不再选产品
                        continue;
                    }
                    //开始选产品

                    //开发员有类目限制，获取开发员的开发类目 产品，没有限制 获取全部产品
                    /*$cateList = $db->getCollection('ebay_cate_rule')
                        ->find([
                            'productType' => $type,                               //新品推荐
                            'recommendDate' =>  ['$regex' => date('Y-m-d')],//当天推荐
                        ]);*/
                    $cateList = [
                        ['marketplace' => 'EBAY_US','cate' => 'Clothing, Shoes & Accessories','subCate' => "Women's Accessories"],
                        ['marketplace' => 'EBAY_US','cate' => 'Clothing, Shoes & Accessories','subCate' => "Women's Clothing"],
                    ];
                    $marketplaceArr = array_unique(ArrayHelper::getColumn($cateList,'marketplace'));
                    //从产品表$productList中获取相关类目产品 并排序
                    $proList =  $db->getCollection('ebay_new_product')
                        ->find([
                            'marketplace' => $marketplaceArr,
                        ]);
                    //匹配符合当前开发类目的产品
                    $cateProList = [];
                    foreach ($proList as $v){
                        foreach($cateList as $val){
                            if($val['subCate'] && strpos($v['cidName'], $val['subCate']) !== false || //有子分类且 包含子分类
                                strpos($v['cidName'], $val['cate']) !== false  //没有子分类且 包含分类
                            ){
                                $cateProList[] = $v;
                                break;
                            }
                        }
                    }

                    //print_r(count($cateProList));exit;

                    foreach ($cateProList as $v) {
                        //数据库结果集中查找 当前产品
                        $resItem = $db->getCollection('ebay_recommended_product')
                            ->findOne(['itemId' =>  $v['itemId']]);
                           // ->findOne(['itemId' =>  '123123123']);
                        //print_r(gettype(strpos($resItem['dispatchDate'], $today)));exit;
                        //print_r($resItem['productType']);exit;
                        if (!$resItem) { //所选产品没有其他人选过(不在结果集) 添加到结果集
                            //print_r("as11111");exit;
                            $item = $v;
                            $item['productType'] = $type;   //类型
                            $item['dispatchDate'] = $today;   //时间
                            $item['receiver'][] = $value;
                            unset($item['_id']);
                            //print_r($item);exit;
                            $db->getCollection('ebay_recommended_product')->insert($item);
                        } elseif (
                            strpos($resItem['dispatchDate'], $today) !== false && //选择时间是当天
                            $resItem['productType'] == $type  &&                  //且是新品
                            count($resItem['receiver']) == 1 &&                   //只有一个人选
                            !in_array($value, $resItem['receiver'])               //不是当前开发选择
                        ) {  //只有其他一个人选过且不是当前开发所选时  更新结果集
                            $dev = $resItem['receiver'];
                            $dev[] = $value;// 先添加开发，再去重
                            //print_r($dev);exit;
                            $db->getCollection('ebay_recommended_product')->update(['_id' => $resItem['_id']], ['receiver' => $dev]);
                        } else {              //否则继续判断下一个产品
                            continue;
                        }
                    }
                }
            }
        } catch (\Exception $why) {

            return ['code' => 401, 'message' => $why->getMessage()];
        }


    }


}