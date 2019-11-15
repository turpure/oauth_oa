<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2019-11-14 17:04
 */

namespace console\models;

use Yii;

class ProductEngine
{


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
     * @param $products
     * @param $developer
     * @return array
     */
    public function dispatch($products, $developer)
    {
        $ret = [];

        //一直分配 直到人用完，或者产品用完
        $turn = ceil(count($products) / count($products));
        while ($turn > 0) {
            $current_developer = static::turnSort($developer);
            $res = static::pickUp($products, $current_developer);
            $ret = array_merge($ret, $res);
            --$turn;
        }
        return $ret;
    }


    /**
     * 挑一次产品
     * @param $products
     * @param $developer
     * @return array
     */
    private static function pickUp($products, $developer)
    {
        $ret = [];
        $row = ['product' => '', 'developer' => ''];
        foreach ($developer as $dp) {
            foreach ($products as $pt) {
                $condition1 = empty($dp['tag']) or in_array($pt['tag'],$dp['tag'], false);
                $condition2 = $pt['limit'] <= 2;
                if($condition1 && $condition2) {
                    $row['product'] = $pt['itemId'];
                    $row['developer'] = $dp['name'];
                    $pt['limit'] += 1;
                    $ret[] = $row;
                }
            }
        }
        return $ret;
    }

    /**
     * 人员排序
     * @param $list
     * @param $index
     * @return mixed
     */
    private static function turnSort($list, $index)
    {
        $first =  [];
        $left = [];
        $right = [];
        $length = count($list);
        $cur = 0;
        for ($cur; $cur<=$length; ++$cur) {
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




}