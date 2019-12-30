<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-11-14 17:04
 */

namespace console\models;

use backend\models\EbayAllotRule;
use backend\models\EbayHotRule;
use backend\models\EbayNewRule;
use backend\models\ShopElf\BGoods;
use backend\models\WishRule;
use backend\modules\v1\models\ApiProductsEngine;
use Yii;

use yii\helpers\ArrayHelper;
use yii\mongodb\Query;

class ProductEngine
{
    private $products;
    private $developer;


    /**
     * ProductEngine constructor.
     * @param $products
     * @param $developer
     */
    public function __construct($products = [], $developer = [])
    {
        $this->products = $products;
        $this->developer = $developer;
    }


    /**
     * 给产品打标签
     * @param $productType
     */
    public static function setProductTag($productType)
    {
        $table = $productType === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $today = date('Y-m-d');
        $catMap = static::getTagCat();
        $products = $col->find(['recommendDate' => ['$regex' => $today]]);
        foreach ($products as $pt) {
            print_r($pt['_id']."\n");
            try {
                $catName = $pt['cidName'];
            } catch (\Exception  $why) {
                $catName = $pt['categoryStructure'];
            }
            $id = $pt['_id'];
            // 匹配类目
            $catNameArr = explode(' - ', $catName);
            $tag = [];
            if(count($catNameArr) > 1){
                foreach ($catMap as $cp) {
                    similar_text($catNameArr[0], $cp['platCate'], $percent1);
                    similar_text($catNameArr[1], $cp['platSubCate'], $percent2);
                    if ($percent1 >= 80 && $percent2 >= 80) {
                        $tag[] = $cp['cateName'];
                    }
                }
                $newTag = array_values(array_unique($tag));
                $col->update(['_id' => $id], ['tag' => $newTag]);
            }
        }
    }

    /**
     * 所有开发
     */
    public static function getDevelopers()
    {
        $mongo = Yii::$app->mongodb;
        $table = 'ebay_allot_rule';
        $col = $mongo->getCollection($table);
        $cur = $col->find();
        $dev = [];
        foreach ($cur as $row) {
            $ele['tag'] = $row['category'];
            $ele['excludeTag'] = $row['excludePyCate'];
            $ele['name'] = $row['username'];
            $ele['deliveryLocation'] = $row['deliveryLocation'];
            $dev[] = $ele;
        }
        return $dev;
    }

    /**
     *获取要过滤掉的店铺
     * @return array
     */
    private static function getFilterStores()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_stores');
        $stores = $col->find();
        $ret = [];
        foreach ($stores as $st) {
            $ret[] = $st['eBayUserID'];
        }
        return $ret;
    }

    /**
     * 获取产品
     * @param $type
     * @return mixed
     */
    public static function getProducts($type)
    {
        if ($type === 'new') {
            $table = 'ebay_new_product';
        } else {
            $table = 'ebay_hot_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $filter_stores = static::getFilterStores();
        $today = date('Y-m-d');
        $cur = $col->find([
            'recommendDate' => ['$regex' => $today],
            'seller' => ['$nin' => $filter_stores ],
        ]);
        $dep = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['itemId'];
            $ele['tag'] = isset($row['tag']) ? $row['tag'] : '';
            $ele['itemLocation'] = $row['itemLocation'];
            $ele['type'] = $type;
            if(empty($row['recommendToPersons'])) {
                $dep[] = $ele;
            }
        }
        return $dep;

    }

    /**
     * 获取平台类目对应的业务类目
     * @return array
     */
    private static function getTagCat()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_cate_rule');
        $cats = $col->find();
        $ret = [];
        $row = ['cateName' => '', 'plat' => '', 'marketplace' => '', 'platCate' => '', 'platSubCate' => ''];
        foreach ($cats as $ct) {

            // 类目名称
            $row['cateName'] = $ct['pyCate'];

            $detail = $ct['detail'];
            foreach ($detail as $dt) {
                $row['plat'] = $dt['plat'];
                $platValue = $dt['platValue'];
                foreach ($platValue as $pt) {
                    $row['marketplace'] = $pt['marketplace'];
                    $marketplace = $pt['marketplaceValue'];
                    foreach ($marketplace as $mk) {
                        $row['platCate'] = $mk['cate'];
                        $subCate = $mk['cateValue']['subCateChecked'];
                        foreach ($subCate as $sc) {
                            $row['platSubCate'] = $sc;
                            $ret[] = $row;
                        }
                    }
                }
            }
        }
        return $ret;
    }


    /**
     * 产品分配算法
     * @return array
     */
    public function dispatch()
    {
        $ret = [];

        //一直分配 直到人用完，或者产品用完
        if(count($this->products) > count($this->developer)) {
            $turn = ceil(count($this->products) / count($this->developer));
        }
        else {
            $turn = count($this->products);
        }
        $developerNumber = count($this->developer);
        for ($i = 0; $i <= $turn; $i++) {
            $this->developer = static::turnSort($this->developer, $i % $developerNumber);
            print_r("第" . $i . "轮选择开始");
            $res = static::pickUp();
            print_r("第" . $i . "轮选择结束");
            print_r("\n");
            $ret = array_merge($ret, $res);
        }
        return static::group($ret);
    }

    /**
     * 按照数量分配给每个开发
     * @param $type
     * @return array
     */
    public static function dispatchToPersons($type='new')
    {

        $persons = static::personNumberLimit($type);
        $products = static::getAllProducts($type);
        $ret = [];
        foreach ($persons as $pn) {
            $productNumber = 0;
            foreach ($products as  $pt) {
                if($productNumber <= (integer)$pn['limit'] && in_array($pn['name'],$pt['receiver'], false)) {
                    $row['product'] = $pt['itemId'];
                    $row['developer'] = $pn['name'];
                    $row['type'] = $type;
                    $productNumber++;
                    $ret[] = $row;
                }
                if($productNumber >= (integer)$pn['limit']) {
                    break;
                }
            }
        }
        return static::group($ret);
    }

    /**
     * 所有产品
     * @param $type
     * @return array
     */
    private static function getAllProducts($type='new')
    {
        $today = date('Y-m-d');
        $query = new Query();
        $cur = $query->select([])
            ->from('ebay_all_recommended_product')
            ->where(['productType' => $type,'recommendDate' => ['$regex' => $today]])
            ->orderBy(['sold' => SORT_DESC]);
        $ret = $cur->all();
        return $ret;

    }

    /**
     * 给每个开发设置的产品数量限制
     * @param $type
     * @return mixed
     */
    private static function personNumberLimit($type)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_allot_rule');
        $cur = $col->find();
        $ret = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['username'];
            $ele['limit'] = $row['productNum'] - static::currentPersonNumberLimit($row['username'], $type);
            $ele['limit'] = $ele['limit'] > 0 ? $ele['limit'] : 0;
            $ret[] = $ele;
        }
        return $ret;
    }

    /**
     * 开发占用的产品数量
     * @param $username
     * @param $type
     * @return mixed
     */
    private static function currentPersonNumberLimit($username,$type)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_recommended_product');
        $today = date('Y-m-d');
        $cur = $col->find([
            'recommendDate' => ['$regex' => $today],
            'receiver' => $username,
            'productType' => $type
        ]);
        $limit = 0;
        foreach ($cur as $row) {
            ++$limit;
        }

        return $limit;
    }


    /**
     * 按itemId汇总推荐人
     * @param $ret
     * @return array
     */
    private static function group($ret)
    {
        $res = [];
        foreach ($ret as $row) {
            $res[$row['product']]['receiver'][] = $row['developer'];
            $res[$row['product']]['type'] = $row['type'];
        }
        return $res;
    }

    /**
     * 挑一次产品
     * @return array
     */
    private  function pickUp()
    {
        $ret = [];
        $row = ['product' => '', 'developer' => ''];
        foreach ($this->developer as &$dp) {
            //var_dump($dp);exit;
            foreach ($this->products as &$pt) {
                $tag = $excludeTag = [];
                if ($pt['tag']) {
                    $tags = is_array($pt['tag']) ? $pt['tag'] : [$pt['tag']];
                    foreach ($tags as $v) {
                        if (in_array($v, $dp['excludeTag'], false)) {
                            $excludeTag[] = $v;
                        }
                        if (in_array($v, $dp['tag'], false)) {
                            $tag[] = $v;
                        }
                    }
                }
                if ($excludeTag) continue;    //属于过滤类别的产品，直接跳过
                //$condition1 =  empty($dp['tag']) || in_array($pt['tag'],$dp['tag'], false);
                $condition1 =  empty($dp['tag']) || $tag;
                $condition2 = static::matchLocation($dp['deliveryLocation'], $pt['itemLocation']);
                $limit = isset($pt['limit']) ? $pt['limit']  : 0;
                if($limit === 0) {
                    $pt['limit'] = 0;
                }
                $condition3 = $limit < 2;
                $dProducts = isset($pt['products']) ? $pt['products'] : [];
                $condition4 = !in_array($pt['name'], $dProducts, false);
                if($condition1 && $condition2 && $condition3 && $condition4) {
                    $row['product'] = $pt['name'];
                    $row['developer'] = $dp['name'];
                    $row['type'] = $pt['type'];
                    $pt['limit'] += 1;
                    $dp['products'][] = $pt['name'];
                    $ret[] = $row;
                    print_r("\n");
                    print_r($dp['name']." 选中:".$pt['name']);
                    break;
                }
            }
        }
        return $ret;
    }

    /**
     * 发货地址匹配
     * @param $developerLocation
     * @param $productLocation
     * @return bool
     */
    private static function matchLocation($developerLocation, $productLocation)
    {
        $locationMap =  [
            '中国' => 'CN',
            '香港' => 'HK',
            '美国' => 'US',
            '英国' => 'GB',
            '法国' => 'FR',
            '德国' => 'DE',
            '荷兰' => 'NL',
            '爱尔兰' => 'IE',
            '加拿大' => 'CA',
            '意大利' => 'IT',
            '澳大利亚' => 'AU'
        ];
        if(empty($developerLocation)) {
            return true;
        }
        foreach ($developerLocation as $dl) {
            if(strpos($productLocation,$locationMap[$dl]) !== false) {
                return true;
            }
        }
        return false;
    }

    /**
     * 轮流排序
     * @param $list
     * @param $index
     * @return mixed
     */
    public static function turnSort($list, $index)
    {
        $first =  [];
        $left = [];
        $right = [];
        $length = count($list);
        for($cur=0; $cur<$length; ++$cur) {
           if($cur < $index) {
               $left[] = $list[$cur];
           }
            elseif($cur > $index) {
                $right[] = $list[$cur];
            }
            else{
               $first[] = $list[$cur];
            }
        }
        return array_merge($first, $right, $left);
    }

    /**
     * 获取每日推荐统计数据
     * Date: 2019-11-21 8:43
     * Author: henry
     * @return array
     */
    public static function getDailyReportData()
    {
        $db = Yii::$app->mongodb;
        //获取eBay新品统计数据
        $ebayNewData = self::getEbayDailyData('new');

        //获取eBay热销品统计数据
        $ebayHotData = self::getEbayDailyData('hot');

        //=========================================================================================
        //获取wish统计数据
        $wishData = self::getWishDailyData();

        //获取开发数据统计
        $devList = EbayAllotRule::find()->all();
        $devData = [];
        foreach ($devList as $v){
            $dispatchNum = $db->getCollection('ebay_recommended_product')
                ->count(['receiver' => $v['username'], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
            $claimNum = $db->getCollection('ebay_recommended_product')
                ->count(['accept' => $v['username'], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
            $filterNum = $db->getCollection('ebay_recommended_product')
                ->count([
                    '$or' => [
                        [
                            "refuse.".$v['username'] => null,
                            'accept' => ['$nin' => [null, $v['username']]],
                        ],
                        [
                            "refuse.".$v['username'] => ['$ne' => null]
                        ]
                    ],
                    "receiver" => $v['username'] ,
                    'dispatchDate' => ['$regex' => date('Y-m-d')]
                ]);
            $unhandledNum = $db->getCollection('ebay_recommended_product')
                ->count([
                    "refuse.".$v['username'] => null,
                    'accept' => null,
                    "receiver" => $v['username'] ,
                    'dispatchDate' => ['$regex' => date('Y-m-d')]
                ]);
            $devItem['username'] = $v['username'];
            $devItem['depart'] = $v['depart'];
            $devItem['dispatchNum'] = $dispatchNum;//当天分配数量
            $devItem['claimNum'] = $claimNum;      //认领数量
            $devItem['claimRate'] = $dispatchNum ? round($claimNum * 1.0/$dispatchNum * 100, 2) : 0;
            $devItem['filterNum'] = $filterNum;    //过滤数量
            $devItem['filterRate'] = $dispatchNum ? round($filterNum * 1.0 / $dispatchNum * 100, 2) : 0;
            $devItem['unhandledNum'] = $unhandledNum;    //过滤数量
            $devItem['unhandledRate'] = $dispatchNum ? round($unhandledNum * 1.0 / $dispatchNum * 100, 2) : 0;
            
            $devData[] = $devItem;
        }
        $claimData = [];
        for ($i=7;$i>0;$i--){
            $date = date('Y-m-d', strtotime("-$i day"));
            $claimData[] = [
                'name' => $date,
                'value' => $db->getCollection('ebay_recommended_product')
                    ->count(['accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => $date]])
                + $db->getCollection('wish_recommended_product')
                        ->count(['accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => $date]])
            ];
        }
        //print_r()
        return array_merge($ebayNewData, $ebayHotData, $wishData, ['devData' => $devData, 'claimData' => $claimData]);
    }

    /**
     * 获取eBay每日统计数据
     * @param $type
     * Date: 2019-12-27 10:16
     * Author: henry
     * @return array
     */
    public static function getEbayDailyData($type){
        $db = Yii::$app->mongodb;
        $table = $type == 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        //获取产品统计数
        $totalNum = $db->getCollection($table)
            ->count(['recommendDate' => ['$regex' => date('Y-m-d')]]);
        //分配总数
        $dispatchNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => $type, 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //认领总数量
        $claimNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => $type, 'accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //过滤总数量
        $filterNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => $type, "refuse" => ['$ne' => null], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //未处理总数量
        $unhandledNum = $db->getCollection('ebay_recommended_product')
            ->count(['productType' => $type, "refuse" => null, 'accept' => null, 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        if($type == 'new'){
            return [
                'totalNewNum' => $totalNum,        //获取新品总数
                'dispatchNewNum' => $dispatchNum,  //分配新品总数
                'claimNewNum' => $claimNum,        //认领新品总数
                'filterNewNum' => $filterNum,      //过滤新品总数
                'unhandledNewNum' => $unhandledNum,//未处理新品总数
            ];
        }else{
            return [
                'totalHotNum' => $totalNum,        //获取热销品总数
                'dispatchHotNum' => $dispatchNum,  //分配热销品总数
                'claimHotNum' => $claimNum,        //认领热销品总数
                'filterHotNum' => $filterNum,      //过滤热销品总数
                'unhandledHotNum' => $unhandledNum,//未处理热销品总数
            ];
        }
    }

    /**
     * 获取Wish每日统计数据
     * @param $type
     * Date: 2019-12-27 10:16
     * Author: henry
     * @return array
     */
    public static function getWishDailyData(){
        $db = Yii::$app->mongodb;
        //获取产品统计数
        $totalNum = $db->getCollection('wish_new_product')
            ->count(['recommendDate' => ['$regex' => date('Y-m-d')]]);
        //分配总数
        $dispatchNum = $db->getCollection('wish_recommended_product')
            ->count(['dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //认领总数量
        $claimNum = $db->getCollection('wish_recommended_product')
            ->count(['accept' => ['$size' => 1], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //过滤总数量
        $filterNum = $db->getCollection('wish_recommended_product')
            ->count(["refuse" => ['$ne' => null], 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        //未处理总数量
        $unhandledNum = $db->getCollection('wish_recommended_product')
            ->count(["refuse" => null, 'accept' => null, 'dispatchDate' => ['$regex' => date('Y-m-d')]]);
        return [
            'totalWishNum' => $totalNum,        //获取新品总数
            'dispatchWishNum' => $dispatchNum,  //分配新品总数
            'claimWishNum' => $claimNum,        //认领新品总数
            'filterWishNum' => $filterNum,      //过滤新品总数
            'unhandledWishNum' => $unhandledNum,//未处理新品总数
        ];

    }

    /**
     * 获取eBay规则数据统计
     * @param $type
     * @param $ruleName
     * @param $beginDate
     * @param $endDate
     * Date: 2019-12-27 10:45
     * Author: henry
     * @return array
     */
    public static function getEbayRuleData($plat, $type, $ruleName, $beginDate, $endDate){
        $data = [];
        $db = Yii::$app->mongodb;
        if($type == 'new'){
            $table = 'ebay_new_product';
            $newRuleList = EbayNewRule::find()->andFilterWhere(['ruleName' => ['$regex' => $ruleName]])->all();
        }else{
            $table = 'ebay_hot_product';
            $newRuleList = EbayHotRule::find()->andFilterWhere(['ruleName' => ['$regex' => $ruleName]])->all();
        }
        foreach ($newRuleList as $v) {
            $item['plat'] = $plat;
            $item['ruleType'] = $type;
            $item['ruleName'] = $v['ruleName'];

            $totalNum = $db->getCollection($table)
                ->count(['rules' => $v['_id'], 'recommendDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dispatchNum = $db->getCollection('ebay_recommended_product')
                ->count(['productType' => $type, 'rules' => $v['_id'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $claimNum = $db->getCollection('ebay_recommended_product')
                ->count(['productType' => $type, 'rules' => $v['_id'], 'accept' => ['$size' => 1], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $filterNum = $db->getCollection('ebay_recommended_product')
                ->count(['productType' => $type, 'rules' => $v['_id'], "refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $unhandledNewNum = $db->getCollection('ebay_recommended_product')
                ->count(['productType' => $type,'rules' => $v['_id'],  "refuse" => null, 'accept' => null, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);


            $item['totalNum'] = $totalNum;
            $item['dispatchNum'] = $dispatchNum;
            $item['claimNum'] = $claimNum;
            $item['filterNum'] = $filterNum;
            $item['unhandledNewNum'] = $unhandledNewNum;

            //获取智能推荐新品 爆旺款全部产品
            $dataList = $hotQuery = (new \yii\db\Query())
                ->from('proCenter.oa_goodsinfo')
                ->select(['g.recommendId','goodsStatus'])
                ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
                ->andWhere(['g.introducer' => 'proEngine'])
                ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
                ->andFilterWhere(['like', 'g.recommendId', $type])
                ->andFilterWhere(['goodsStatus' => ['爆款', '旺款']])
                ->all();
            $hotNum = $popNum = 0;
            foreach ($dataList as $value) {
                $recommend = $db->getCollection('ebay_recommended_product')->count(['_id' => explode('.', $value['recommendId'])[1]]);
                if ($value['goodsStatus'] == '爆款' && $recommend) {
                    $hotNum += 1;
                } elseif ($value['goodsStatus'] == '旺款' && $recommend) {
                    $popNum += 1;
                }
            }
            $item['hotNum'] = $hotNum;
            $item['popNum'] = $popNum;

            $item['claimRate'] = $dispatchNum ? round($claimNum*1.0/$dispatchNum,4) : 0;
            $item['filterRate'] = $dispatchNum ? round($filterNum*1.0/$dispatchNum,4) : 0;
            $item['hotRate'] = $claimNum ? round($hotNum*1.0/$claimNum,4) : 0;
            $item['popRate'] = $claimNum ? round($popNum*1.0/$claimNum,4) : 0;

            $data[] = $item;
        }
        return $data;
    }

    /**
     * 获取wish规则数据统计
     * @param $type
     * @param $ruleName
     * @param $beginDate
     * @param $endDate
     * Date: 2019-12-27 10:45
     * Author: henry
     * @return array
     */
    public static function getWishRuleData($plat, $type, $ruleName, $beginDate, $endDate){
        $data = [];
        $db = Yii::$app->mongodb;
        $newRuleList = WishRule::find()->andFilterWhere(['ruleName' => ['$regex' => $ruleName]])->all();
        foreach ($newRuleList as $v) {
            $item['plat'] = $plat;
            $item['ruleType'] = $v['ruleType'];
            $item['ruleName'] = $v['ruleName'];

            $totalNum = $db->getCollection('wish_new_product')
                ->count(['rules' => [$v['_id']], 'recommendDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $dispatchNum = $db->getCollection('wish_recommended_product')
                ->count(['rules' => $v['_id'], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $claimNum = $db->getCollection('wish_recommended_product')
                ->count(['rules' => $v['_id'], 'accept' => ['$size' => 1], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $filterNum = $db->getCollection('wish_recommended_product')
                ->count(['rules' => $v['_id'], "refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
            $unhandledNewNum = $db->getCollection('wish_recommended_product')
                ->count(['rules' => $v['_id'],  "refuse" => null, 'accept' => null, 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);


            $item['totalNum'] = $totalNum;
            $item['dispatchNum'] = $dispatchNum;
            $item['claimNum'] = $claimNum;
            $item['filterNum'] = $filterNum;
            $item['unhandledNewNum'] = $unhandledNewNum;

            //获取智能推荐新品 爆旺款全部产品
            $dataList = $hotQuery = (new \yii\db\Query())
                ->from('proCenter.oa_goodsinfo')
                ->select('g.recommendId,goodsStatus')
                ->leftJoin('proCenter.oa_goods g', 'g.nid=goodsid')
                ->andWhere(['g.introducer' => 'proEngine'])
                ->andFilterWhere(['between', 'left(g.createDate,10)', $beginDate, $endDate])
                ->andFilterWhere(['like', 'g.recommendId', $plat])
                ->andFilterWhere(['goodsStatus' => ['爆款', '旺款']])
                ->all();
            $hotNum = $popNum = 0;
            foreach ($dataList as $value) {
                $recommend = $db->getCollection('wish_recommended_product')->count(['_id' => explode('.', $value['recommendId'])[1]]);
                if ($value['goodsStatus'] == '爆款' && $recommend) {
                    $hotNum += 1;
                } elseif ($value['goodsStatus'] == '旺款' && $recommend) {
                    $popNum += 1;
                }
            }
            $item['hotNum'] = $hotNum;
            $item['popNum'] = $popNum;

            $item['claimRate'] = $dispatchNum ? round($claimNum*1.0/$dispatchNum,4) : 0;
            $item['filterRate'] = $dispatchNum ? round($filterNum*1.0/$dispatchNum,4) : 0;
            $item['hotRate'] = $claimNum ? round($hotNum*1.0/$claimNum,4) : 0;
            $item['popRate'] = $claimNum ? round($popNum*1.0/$claimNum,4) : 0;

            $data[] = $item;
        }
        return $data;
    }

    /**
     * 获取过滤理由统计
     * @param $plat
     * @param $beginDate
     * @param $endDate
     * Date: 2019-12-27 12:03
     * Author: henry
     * @return array
     */
    public static function getRefuseData($plat, $beginDate, $endDate){
        $db = Yii::$app->mongodb;
        $table = $plat == 'ebay' ? 'ebay_recommended_product' : 'wish_recommended_product';
        $product = $db->getCollection($table)
            ->find(["refuse" => ['$ne' => null], 'dispatchDate' => ['$gte' => $beginDate, '$lte' => $endDate]]);
        $refuseArr = ArrayHelper::getColumn($product,'refuse');
        $arr = [
            '1: 重复' => 0,
            '2: 侵权' => 0,
            '3: 不好运输' => 0,
            '4: 销量不好' => 0,
            '5: 找不到货源' => 0,
            '6: 价格没优势' => 0,
            '7: 评分低' => 0,
            '8: 其他' => 0,
        ];
        $refuseData = [];
        foreach($refuseArr as $val) {
            foreach ($val as $v){
                if(strpos($v,'1:') !== false){
                    $arr['1: 重复'] += 1;
                }elseif (strpos($v,'2:') !== false){
                    $arr['2: 侵权'] += 1;
                }elseif (strpos($v,'3:') !== false){
                    $arr['3: 不好运输'] += 1;
                }elseif (strpos($v,'4:') !== false){
                    $arr['4: 销量不好'] += 1;
                }elseif (strpos($v,'5:') !== false){
                    $arr['5: 找不到货源'] += 1;
                }elseif (strpos($v,'6:') !== false){
                    $arr['6: 价格没优势'] += 1;
                }elseif (strpos($v,'7:') !== false){
                    $arr['7: 评分低'] += 1;
                }else if(strpos($v,'8:') !== false){
                    $arr['8: 其他'] += 1;
                    @$refuseData[$v]++;
                }
            }
        }
        return [$arr, $refuseData];
    }



    /**
     * 根据匹配结果，按照ItemID查找数据
     * @param $itemId
     * @param $pickupResult
     * @return array
     */
    public static function pullData($itemId,$pickupResult)
    {
        $mongo = Yii::$app->mongodb;
        $type = $pickupResult['type'];
        $table = $type === 'new' ? 'ebay_new_product' : 'ebay_hot_product';
        $col = $mongo->getCollection($table);
        $ret = $col->findOne(['itemId' => (string)$itemId]);

        $ret['receiver'] = $pickupResult['receiver'];
        $ret['dispatchDate'] = date('Y-m-d H:i:s');
        $ret['productType'] = $type;
        unset($ret['_id']);
        return $ret;

    }


    /**
     * 入库处理
     * @param $row
     * @param string $type
     */
    public static function pushData($row, $type='all')
    {
        if($type === 'all') {
            $table = 'ebay_all_recommended_product';
        }
        else {
            $table = 'ebay_recommended_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        try {
            $col->insert($row);
        }
        catch (\Exception  $why) {
            print 'fail to save '. $row['itemId'] . ' cause of ' . $why->getMessage();
    }
        print_r('pushing ' . $row['itemId'] . ' into ' . $table . "\n");
    }

    public static function detectImages()
    {
        $images = static::getImages();
        foreach ($images as $img) {
            $result = static::detectOneImage($img);
            $result = static::filterImage($result);
            self::saveImageDetectedResult($result);
        }

    }

    /**过滤掉重复出现的商品编码
     * @param $result
     * @return array
     */
    public static function filterImage($result)
    {
        $similarImages = $result['similarImages'];
        $goods = [];
        $sg = [];
        foreach ($similarImages as $ele) {
            $sm = $ele['similar'];
            $out = [];
            foreach ($sm as $row) {
                if(!in_array($row['GoodsCode'], $goods, false)) {
                    $goods[] = $row['GoodsCode'];
                    $goodsInfo = static::getImageGoodsInfo($row['GoodsCode']);
                    $row['goodsStatus'] = $goodsInfo['goodsStatus'];
                    $row['linkUrl'] = $goodsInfo['linkUrl'];
                    $out[] = $row;
                }
            }
            $sg[] = ['similar' =>$out, 'image' => $ele['image']];
        }
        $result['similarImages'] = $sg;
        return $result;
    }

    /**
     * 根据商品编码获取产品信息
     * @param $goodsCode
     * @return mixed
     */
    public static function getImageGoodsInfo($goodsCode)
    {
       $ret = BGoods::find()->select(['GoodsStatus','LinkUrl'])->where(['GoodsCode' => $goodsCode])->asArray()->one();
       $out = ['goodsStatus' => '', 'linkUrl' => ''];
       if(!empty($ret)) {
           $out['goodsStatus'] = $ret['GoodsStatus'];
           $out['linkUrl'] =  $ret['LinkUrl'];
       }
       return $out;
    }
    /**
     * 图像检测
     * @param $image
     * @return array
     **/
    private static function detectOneImage($image)
    {
        $result = ['itemId' => $image['itemId']];
        $images = $image['images'];
        $imgs = [];
        foreach ($images as $img) {
            $searchResult = ApiProductsEngine::imageSearch($img);
            $similarImages = $searchResult['Auctions'];
            $goodsCode = [];
            $thisResult = ['image' => $img];
            $ele = [];
            foreach ($similarImages as $simg) {
                if(!in_array($simg['GoodsCode'],$goodsCode, false)) {
                    $goodsCode[] = $simg['GoodsCode'];
                    $ele[] = $simg;
                }
            }
            $thisResult['similar'] = $ele;
            $imgs[] = $thisResult;
        }
        $result['similarImages'] = $imgs;
        return $result;
    }


    /**
     */
    private static function getImages()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_recommended_product');
        $cur = $col->find([
            'recommendDate' => ['$regex' => date('Y-m-d')],
//            'itemId' => '153750701056'
        ]);
        $ret = [];
        foreach ($cur as $row){
            $ele['itemId'] = $row['itemId'];
            $images = isset($row['images']) ? $row['images'] : [];
            $ele['images'] = array_merge([$row['mainImage']],$images);
            $ret[] = $ele;
        }
        return $ret;
    }

    private static function saveImageDetectedResult($row)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_recommended_product');
        $col->update(['itemId' => $row['itemId']],['similarImages' => $row['similarImages']] );
        print_r('success detect images of '. $row['itemId']);
        print_r("\n");



    }

}