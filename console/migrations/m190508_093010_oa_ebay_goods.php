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
            $pySql = "SELECT t.nid, goodsId, prepareDay, quantity, infoId,
                description, replace(replace(replace(extraPage,'{\"images\":[\"',''),'\"]}',''),'\",\"','\n') AS extraPage, 
                specifics,
                CASE WHEN stockUp=1 THEN '是' ELSE '否' END AS stockUp,
                nowPrice, inFirstCost1, inSuccessorCost1, inFirstCost2, inSuccessorCost2, outFirstCost1,
                outSuccessorCost1, outFirstCost2, outSuccessorCost2,
                location, brand, shape, features, regionManufacture, sku, iBayTemplate,
                sss.servicesName AS inShippingMethod1, 
                ssss.servicesName AS inShippingMethod2,
                s.servicesName AS outShippingMethod1, 
                ss.servicesName AS outShippingMethod2,
                country, postCode, site, listedCate, listedSubcate, unit, bundleListing,
                title, subTitle, outShipToCountry1, outShipToCountry2, mainPage,
                UPC, EAN, MPN, color, t.type, material, headKeywords, tailKeywords,
                intendedUse, reserveField, requiredKeywords, randomKeywords
                FROM oa_templates t
                LEFT JOIN oa_shippingService s ON s.nid=t.outShippingMethod1
                LEFT JOIN oa_shippingService ss ON ss.nid=t.outShippingMethod2
                LEFT JOIN oa_shippingService sss ON sss.nid=t.inShippingMethod1
                LEFT JOIN oa_shippingService ssss ON ssss.nid=t.inShippingMethod2
                WHERE t.nid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
             //print_r($list);exit;
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

        /*$this->truncateTable('proCenter.oa_ebayGoodsSku');
        $count = Yii::$app->py_db->createCommand("SELECT max(nid) as num from oa_templatesVar")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT nid AS id,tid AS itemId, sid, pid AS infoId,sku,quantity,retailPrice, imageUrl,property
                        FROM oa_templatesVar WHERE nid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_ebayGoodsSku', [
                'id','itemId', 'sid', 'infoId','sku','quantity','retailPrice', 'imageUrl','property'
            ], $list);
        }*/
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
