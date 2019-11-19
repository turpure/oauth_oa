<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-11-14 17:04
 */

namespace console\models;

use Yii;

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
    public function __construct($products=[], $developer=[])
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
        $catMap =static::getTagCat();
        $products = $col->find(['recommendDate' => ['$regex' => $today]]);
        foreach ($products as $pt) {
            try {
                $catName = $pt['cidName'];
            }
            catch (\Exception  $why) {
                $catName = $pt['categoryStructure'];
            }
            $id = $pt['_id'];
            // 匹配类目
            foreach ($catMap as $cp) {
                similar_text($catName, $cp['platCate'].'-' .$cp['platSubCate'],$percent);
                if($percent >= 50) {
                    $tag = $cp['cateName'];
                    $col->update(['_id' => $id],['tag' => $tag]);
                    break;
                }
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
            $ele['name'] = $row['username'];
            $ele['deliveryLocation'] = $row['deliveryLocation'];
            $dev[] =$ele;
        }
        return $dev;
    }

    /**
     * @param $type
     * @return mixed
     */
    public static function getProducts($type)
    {
        if($type === 'new') {
            $table = 'ebay_new_product';
        }
        else{
            $table = 'ebay_hot_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $today = date('Y-m-d');
        $cur = $col->find(['recommendDate' => ['$regex' => $today]]);
        $dep = [];
        foreach ($cur as $row) {
            $ele['name'] = $row['itemId'];
            $ele['tag'] = isset($row['tag'])? $row['tag'] : '';
            $ele['itemLocation'] = $row['itemLocation'];
            $ele['type'] = $type;
            $dep[] = $ele;
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
                        foreach($subCate as $sc) {
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
        $turn = ceil(count($this->products) / count($this->developer));
        $developerNumber = count($this->developer);
        for ($i=0; $i<=$turn; $i++) {
            $this->developer = static::turnSort($this->developer,$i % $developerNumber);
            print_r("第".$i."轮选择开始");
            $res = static::pickUp();
            print_r("第".$i."轮选择结束");
            print_r("\n");
            $ret = array_merge($ret, $res);
        }
        return static::group($ret);
    }

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
     * @param $products
     * @param $developer
     * @return array
     */
    private  function pickUp()
    {
        $ret = [];
        $row = ['product' => '', 'developer' => ''];
        foreach ($this->developer as &$dp) {
            foreach ($this->products as &$pt) {
                $condition1 =  empty($dp['tag']) || in_array($pt['tag'],$dp['tag'], false);
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
     * 入库处理
     * @param $row
     * @param string $type
     */
    public static function pushDB($row, $type='all')
    {
        if($type === 'all') {
            $table = 'ebay_all_recommended_product';
        }
        else {
            $table = 'ebay_recommended_product';
        }
        $mongo = Yii::$app->mongodb;
        $col = $mongo->getCollection($table);
        $col->insert($row);
    }

}