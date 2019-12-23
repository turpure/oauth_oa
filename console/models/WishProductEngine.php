<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-11-14 17:04
 */

namespace console\models;

use backend\models\EbayAllotRule;
use backend\models\ShopElf\BGoods;
use backend\modules\v1\models\ApiProductsEngine;
use Yii;

use yii\mongodb\Query;

class WishProductEngine
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
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('wish_new_product');
        $today = date('Y-m-d');
        $catMap = static::getTagCat('wish');
        $products = $col->find(['ruleType' => $productType, 'cidName' => ['$nin' => []], 'recommendDate' => ['$regex' => $today]]);
        foreach ($products as $pt) {
            print_r($pt['_id'] . "\n");
            $catNameArr = self::getProductCidName($pt['cidName']);
            $id = $pt['_id'];
            // 匹配类目
            $tag = [];
            foreach ($catMap as $cp) {
                foreach ($catNameArr as $catName)
                //var_dump($cp['platCate']);exit;
                similar_text($catName, $cp['platCate'], $percent1);
                similar_text($catName, $cp['platSubCate'], $percent2);
                if ($percent1 >= 80 && $percent2 >= 80) {
                    $tag[] = $cp['cateName'];
                }
            }
            $newTag = array_values(array_unique($tag));
            if ($newTag) {
                $col->update(['_id' => $id], ['tag' => $newTag]);
            }
        }
    }



    /**
     * 获取产品
     * @param $type
     * @return mixed
     */
    public static function getProducts($type)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('wish_new_product');
        $filter_stores = static::getFilterStores();
        $today = date('Y-m-d');
        $cur = $col->find([
            'ruleType' => $type,
            'recommendDate' => ['$regex' => $today],
            'merchant' => ['$nin' => $filter_stores],
        ]);
        $dep = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['pid'];
            $ele['tag'] = isset($row['tag']) ? $row['tag'] : '';
            $ele['type'] = $type;
            if (empty($row['recommendToPersons'])) {
                $dep[] = $ele;
            }
        }
        return $dep;

    }


    /**
     * 产品分配算法
     * @return array
     */
    public function dispatch()
    {
        $ret = [];

        //一直分配 直到人用完，或者产品用完
        if ($this->products > $this->developer) {
            $turn = ceil(count($this->products) / count($this->developer));
        } else {
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
    public static function dispatchToPersons($type = 'new')
    {

        $persons = static::personNumberLimit($type);
        $products = static::getAllProducts($type);
        $ret = [];
        foreach ($persons as $pn) {
            $productNumber = 0;
            foreach ($products as $pt) {
                if ($productNumber <= (integer)$pn['limit'] && in_array($pn['name'], $pt['receiver'], false)) {
                    $row['product'] = $pt['pid'];
                    $row['developer'] = $pn['name'];
                    $row['type'] = $type;
                    $productNumber++;
                    $ret[] = $row;
                }
                if ($productNumber >= (integer)$pn['limit']) {
                    break;
                }
            }
        }
        return static::group($ret);
    }

    /**
     * 获取wish产品类目
     * @param array $item
     * Date: 2019-12-19 17:23
     * Author: henry
     * @return array
     */
    private static function getProductCidName($item = [])
    {
        $ret = [];
        if (is_array($item) && $item) {
            foreach ($item as $value) {
                $ret[] = $value['pl1Name'] . $value['cname'];
            }
        }
        return $ret;
    }

    /**
     *获取要过滤掉的店铺
     * @return array
     */
    private static function getFilterStores()
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('wish_stores');
        $stores = $col->find();
        $ret = [];
        foreach ($stores as $st) {
            $ret[] = $st['eBayUserID'];
        }
        return $ret;
    }

    /**
     * 获取平台类目对应的业务类目
     * @return array
     */
    private static function getTagCat($plat = 'wish')
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('ebay_cate_rule');
        $cats = $col->find();
        $ret = [];
        $row = ['cateName' => '', 'plat' => $plat, 'marketplace' => '', 'platCate' => '', 'platSubCate' => ''];
        foreach ($cats as $ct) {
            // 类目名称
            $row['cateName'] = $ct['pyCate'];
            $detail = $ct['detail'];
            foreach ($detail as $k => $dt) {
                if ($dt['plat'] != $plat) continue;
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
     * 所有产品
     * @param $type
     * @return array
     */
    private static function getAllProducts($type = 'new')
    {
        $today = date('Y-m-d');
        $query = new Query();
        $cur = $query->select([])
            ->from('wish_all_recommended_product')
            ->where(['productType' => $type, 'recommendDate' => ['$regex' => $today]])
            ->orderBy(['maxNumBought' => SORT_DESC]);
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
    private static function currentPersonNumberLimit($username, $type)
    {
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection('wish_recommended_product');
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
    private function pickUp()
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
                $condition1 = empty($dp['tag']) || $tag;
                //$condition2 = static::matchLocation($dp['deliveryLocation'], $pt['itemLocation']);
                $limit = isset($pt['limit']) ? $pt['limit'] : 0;
                if ($limit === 0) {
                    $pt['limit'] = 0;
                }
                $condition3 = $limit < 2;
                $dProducts = isset($pt['products']) ? $pt['products'] : [];
                $condition4 = !in_array($pt['name'], $dProducts, false);
                //if ($condition1 && $condition2 && $condition3 && $condition4) {
                if ($condition1 && $condition3 && $condition4) {
                    $row['product'] = $pt['name'];
                    $row['developer'] = $dp['name'];
                    $row['type'] = $pt['type'];
                    $pt['limit'] += 1;
                    $dp['products'][] = $pt['name'];
                    $ret[] = $row;
                    print_r("\n");
                    print_r($dp['name'] . " 选中:" . $pt['name']);
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
        $locationMap = [
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
        if (empty($developerLocation)) {
            return true;
        }
        foreach ($developerLocation as $dl) {
            if (strpos($productLocation, $locationMap[$dl]) !== false) {
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
        $first = [];
        $left = [];
        $right = [];
        $length = count($list);
        for ($cur = 0; $cur < $length; ++$cur) {
            if ($cur < $index) {
                $left[] = $list[$cur];
            } elseif ($cur > $index) {
                $right[] = $list[$cur];
            } else {
                $first[] = $list[$cur];
            }
        }
        return array_merge($first, $right, $left);
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
        $col = $mongo->getCollection('wish_new_product');
        $ret = $col->findOne(['ruleType' => $type, 'pid' => (string)$itemId]);

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
            $table = 'wish_all_recommended_product';
        }
        else {
            $table = 'wish_recommended_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        try {
            $col->insert($row);
        }
        catch (\Exception  $why) {
            print 'fail to save '. $row['pid'] . ' cause of ' . $why->getMessage();
        }
        print_r('pushing ' . $row['pid'] . ' into ' . $table . "\n");
    }


    /**
     * 获取并更新每日推荐的推荐人
     */
    public static function getAndSetRecommendToPersons($today = '')
    {
        if(!$today) $today = date('Y-m-d'); //设置默认今天
        // 清空今日推荐人
        static::clearTodayPersons($today);
        $products = static::getRecommendToPersons($today);
        foreach ($products as $recommendProduct) {
            $productType = $recommendProduct['productType'];
            $developers = $recommendProduct['receiver'];
            $itemId = $recommendProduct['pid'];
            static::setRecommendToPersons($recommendProduct,$developers, $productType, $itemId);
        }
    }

    /**
     * 获取推荐人列表
     * @return mixed
     */
    private static function getRecommendToPersons($today)
    {
        $mongodb = Yii::$app->mongodb;
        $table = 'wish_recommended_product';
        $col = $mongodb->getCollection($table);
        $products = $col->find(['dispatchDate' => ['$regex' => $today]]);
        return $products;
    }

    /**
     * 清空今日推荐人
     */
    private static function clearTodayPersons($today)
    {
        $tables = ['wish_new_product'];
        $mongo = Yii::$app->mongodb;
        foreach ($tables as $ts) {
            $col = $mongo->getCollection($ts);
            $products = $col->find(['recommendDate' => ['$regex' => $today]]);
            foreach ($products as $row) {
                $col->update(['_id' => $row['_id']],['recommendToPersons' => []]);
            }
        }
    }


    /**
     * 为每日推荐列表设置推荐人
     * @param $products
     * @param $developers
     * @param $productType
     * @param $itemId
     */
    private static function setRecommendToPersons($products,$developers, $itemId)
    {
        $mongodb = Yii::$app->mongodb;
        $table = 'wish_new_product';
        $col = $mongodb->getCollection($table);
        $currentPersons = static::insertOrUpdateOrDeleteRecommendToPersons($products,$developers);
        $col->update(['pid' => $itemId], ['recommendToPersons' => $currentPersons]);

    }

    /**
     * 更新或新增推荐人
     * @param $product
     * @param $persons
     * @return array
     */
    private static function insertOrUpdateOrDeleteRecommendToPersons($product, $persons)
    {
        $refuse = isset($product['refuse']) ? $product['refuse'] : [];
        $accept = isset($product['accept']) ? $product['accept'] : [];
        $person = ['name' =>'', 'status' => '', 'reason' => ''];
        $ret = [];
        foreach ($persons as $pn) {
            if(in_array($pn, $accept, false)) {
                $row = $person;
                $row['name'] = $pn;
                $row['status'] = 'accept';
                $ret[] = $row;
            }
            elseif(array_key_exists($pn, $refuse)) {
                $row = $person;
                $row['name'] = $pn;
                $row['status'] = 'refuse';
                $row['reason'] = $refuse[$pn];
                $ret[] = $row;
            }
            else {
                $row = $person;
                $row['name'] = $pn;
                $ret[] = $row;
            }
        }

        return $ret;

    }


}