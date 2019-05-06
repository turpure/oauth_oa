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
        /*
        $pySql = "SELECT TOP 10 goodsId,supplierID,storeID,bgoodsId,stockDays,number,mid,
                        description,supplierName,declaredValue,devDatetime,updateTime,picCompleteTime,
                        goodsName,aliasCnName,aliasEnName,packName,purchaser,developer,
                        season,goodsCode,completeStatus,goodsStatus,
                        dictionaryName,storeName,picUrl,requiredKeywords,randomKeywords,wishTags,extendStatus,mapPersons,
                        possessMan1,possessMan2,achieveStatus,attributeName,picStatus,
                        isVar,stockUp,isLiquid,isPowder,isMagnetism,isCharged,wishPublish,headKeywords,tailKeywords
                        FROM oa_goodsinfo WHERE devDatetime BETWEEN '2019-01-01' AND '2019-04-15' AND isnull(mid,0)=0 ORDER BY pid DESC ";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
       // print_r($list);exit;
        $lis = $this->batchInsert('proCenter.oa_goodsinfo', [
            'goodsId', 'supplierID', 'storeID', 'bgoodsId', 'stockDays', 'number', 'mid',
            'description', 'supplierName', 'declaredValue','devDatetime', 'updateTime', 'picCompleteTime',
            'goodsName', 'aliasCnName', 'aliasEnName','packName', 'purchaser', 'developer',
            'season', 'goodsCode', 'completeStatus', 'goodsStatus',
            'dictionaryName', 'storeName', 'picUrl', 'requiredKeywords', 'randomKeywords', 'wishTags', 'extendStatus', 'mapPersons',
            'possessMan1', 'possessMan2','achieveStatus', 'attributeName','picStatus',
            'isVar', 'stockUp', 'isLiquid', 'isPowder', 'isMagnetism', 'isCharged', 'wishPublish','headKeywords', 'tailKeywords'
        ], $list);
        */
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
