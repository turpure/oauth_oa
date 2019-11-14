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




}