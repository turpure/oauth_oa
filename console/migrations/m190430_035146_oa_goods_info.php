<?php

use yii\db\Migration;

/**
 * Class m190430_035146_oa_goods_info
 */
class m190430_035146_oa_goods_info extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //goodsinfo数据
        $this->truncateTable('proCenter.oa_goodsinfo');
        $count = Yii::$app->py_db->createCommand("SELECT max(pid) as num from oa_goodsinfo")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT pid AS id,goodsId,supplierID,storeID,bgoodsId,stockDays,number,mid,
                        description,supplierName,declaredValue,devDatetime,updateTime,picCompleteTime,
                        goodsName,aliasCnName,aliasEnName,packName,purchaser,developer,
                        season,goodsCode,completeStatus,goodsStatus,
                        dictionaryName,storeName,picUrl,requiredKeywords,randomKeywords,wishTags,extendStatus,mapPersons,
                        possessMan1,possessMan2,achieveStatus,attributeName,picStatus,
                        isVar,
                        CASE WHEN stockUp=1 THEN '是' ELSE '否' END AS stockUp,
                        CASE WHEN isLiquid=1 THEN '是' ELSE '否' END AS isLiquid,
                        CASE WHEN isPowder=1 THEN '是' ELSE '否' END AS isPowder,
                        CASE WHEN isMagnetism=1 THEN '是' ELSE '否' END AS isMagnetism,
                        CASE WHEN isCharged=1 THEN '是' ELSE '否' END AS isCharged,
                        wishPublish,headKeywords,tailKeywords
                        FROM oa_goodsinfo WHERE pid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_goodsinfo', [
                'id', 'goodsId', 'supplierID', 'storeID', 'bgoodsId', 'stockDays', 'number', 'mid',
                'description', 'supplierName', 'declaredValue', 'devDatetime', 'updateTime', 'picCompleteTime',
                'goodsName', 'aliasCnName', 'aliasEnName', 'packName', 'purchaser', 'developer',
                'season', 'goodsCode', 'completeStatus', 'goodsStatus',
                'dictionaryName', 'storeName', 'picUrl', 'requiredKeywords', 'randomKeywords', 'wishTags', 'extendStatus', 'mapPersons',
                'possessMan1', 'possessMan2', 'achieveStatus', 'attributeName', 'picStatus',
                'isVar', 'stockUp', 'isLiquid', 'isPowder', 'isMagnetism', 'isCharged', 'wishPublish', 'headKeywords', 'tailKeywords'
            ], $list);
        }


        //goodssku 数据

        $this->truncateTable('proCenter.oa_goodssku');
        $count = Yii::$app->py_db->createCommand("SELECT max(sid) as num from oa_goodssku")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT sid AS id,pid AS infoId,sku,property1,property2,property3,weight,memo1,memo2,memo3,memo4,
                        linkUrl,goodsSkuId,retailPrice,costPrice,stockNum,did,joomPrice,joomShipping
                        FROM oa_goodssku WHERE sid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            // print_r($list);exit;
            $this->batchInsert('proCenter.oa_goodssku', [
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
        echo "m190430_035146_oa_goods_info cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190430_035146_oa_goods_info cannot be reverted.\n";

        return false;
    }
    */
}
