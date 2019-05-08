<?php

use yii\db\Migration;

/**
 * Class m190508_093010_oa_ebay_goods
 */
class m190508_093010_oa_ebay_goods extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //ebay_goods数据
        $this->truncateTable('proCenter.oa_ebayGoods');
        $count = Yii::$app->py_db->createCommand("SELECT max(nid) as num from oa_templates")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT nid, goodsId, prepareDay, quantity, infoId,
                description, extraPage, specifics,
                CASE WHEN stockUp=1 THEN '是' ELSE '否' END AS stockUp,
                nowPrice, inFirstCost1, inSuccessorCost1, inFirstCost2, inSuccessorCost2, outFirstCost1,
                outSuccessorCost1, outFirstCost2, outSuccessorCost2,
                location, brand, shape, features, regionManufacture, sku, iBayTemplate,
                inShippingMethod1, inShippingMethod2, outShippingMethod1, outShippingMethod2,
                country, postCode, site, listedCate, listedSubcate, unit, bundleListing,
                title, subTitle, outShipToCountry1, outShipToCountry2, mainPage,
                UPC, EAN, MPN, color, type, material, headKeywords, tailKeywords,
                intendedUse, reserveField, requiredKeywords, randomKeywords
                        FROM oa_templates WHERE pid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_ebayGoods', [
                'nid', 'goodsId', 'prepareDay', 'quantity', 'infoId',
                'description', 'extraPage', 'specifics','stockUp',
                'nowPrice', 'inFirstCost1', 'inSuccessorCost1', 'inFirstCost2', 'inSuccessorCost2', 'outFirstCost1',
                'outSuccessorCost1', 'outFirstCost2', 'outSuccessorCost2',
                'location', 'brand', 'shape', 'features', 'regionManufacture', 'sku', 'iBayTemplate',
                'inShippingMethod1', 'inShippingMethod2', 'outShippingMethod1', 'outShippingMethod2',
                'country', 'postCode', 'site', 'listedCate', 'listedSubcate', 'unit', 'bundleListing',
                'title', 'subTitle', 'outShipToCountry1', 'outShipToCountry2', 'mainPage',
                'UPC', 'EAN', 'MPN', 'color', 'type', 'material', 'headKeywords', 'tailKeywords',
                'intendedUse', 'reserveField', 'requiredKeywords', 'randomKeywords'
            ], $list);
        }


        //ebay_goodssku 数据

        $this->truncateTable('proCenter.oa_ebayGoodsSku');
        $count = Yii::$app->py_db->createCommand("SELECT max(sid) as num from oa_templatesVar")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT sid AS id,pid AS infoId,sku,property1,property2,property3,weight,memo1,memo2,memo3,memo4,
                        linkUrl,goodsSkuId,retailPrice,costPrice,stockNum,did,joomPrice,joomShipping
                        FROM oa_goodssku WHERE sid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_ebayGoodsSku', [
                'id', 'infoId','sku','property1','property2','property3','weight','memo1','memo2','memo3','memo4',
                'linkUrl','goodsSkuId','retailPrice','costPrice','stockNum','did','joomPrice','joomShipping'
            ], $list);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_093010_oa_ebay_goods cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190508_093010_oa_ebay_goods cannot be reverted.\n";

        return false;
    }
    */
}
