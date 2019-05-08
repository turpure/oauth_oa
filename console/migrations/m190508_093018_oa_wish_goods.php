<?php

use yii\db\Migration;

/**
 * Class m190508_093018_oa_wish_goods
 */
class m190508_093018_oa_wish_goods extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //wish_goods数据
        $this->truncateTable('proCenter.oa_wishGoods');
        $count = Yii::$app->py_db->createCommand("SELECT max(itemid) as num from oa_wishgoods")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT itemid AS id,description, extra_images AS extraImages,
                CASE WHEN stockUp=1 THEN '是' ELSE '否' END AS stockUp,
                inventory, goodsId, infoId,
                price, msrp, shipping,sku,title, main_image AS mainImage,
                shippingTime, headKeywords, tailKeywords,
                tags, wishTags,requiredKeywords, randomKeywords
                        FROM oa_wishgoods WHERE itemid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_wishGoods', [
                'id', 'description', 'extraImages','stockUp',
                'inventory', 'goodsId', 'infoId',
                'price', 'msrp', 'shipping', 'sku', 'title', 'mainImage',
                'shippingTime', 'headKeywords', 'tailKeywords',
                'tags', 'wishTags', 'requiredKeywords', 'randomKeywords'
            ], $list);
        }


        //wish_goodssku 数据

        $this->truncateTable('proCenter.oa_wishGoodsSku');
        $count = Yii::$app->py_db->createCommand("SELECT max(itemid) as num from oa_wishgoodssku")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT itemid AS id,pid AS infoId, sid, inventory, goodsSkuId,
                      price, shipping, msrp, weight, joomPrice, joomShipping,sku, color, size,shipping_time AS shippingTime,linkUrl
                      FROM oa_wishgoodssku WHERE itemid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_wishGoodsSku', [
                'id','infoId', 'sid', 'inventory', 'goodsSkuId',
                'price', 'shipping', 'msrp', 'weight', 'joomPrice', 'joomShipping',
                'sku', 'color', 'size', 'shippingTime', 'linkUrl'
            ], $list);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_093018_oa_wish_goods cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190508_093018_oa_wish_goods cannot be reverted.\n";

        return false;
    }
    */
}
