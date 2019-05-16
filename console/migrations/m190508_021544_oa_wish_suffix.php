<?php

use yii\db\Migration;

/**
 * Class m190508_021544_oa_wish_suffix
 */
class m190508_021544_oa_wish_suffix extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        //wish字典
       /* $pySql = "SELECT ibaySuffix,shortName,suffix,rate,mainImg,parentCategory FROM oa_wishSuffixDictionary";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        $lis = $this->batchInsert('proCenter.oa_wishSuffix', [
            'ibaySuffix','shortName','suffix','rate','mainImg','parentCategory'
        ], $list);*/

       //运输方式
        /*$pySql = "SELECT nid AS id,servicesName,type,siteId AS site,ibayShipping FROM oa_shippingService";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        $this->truncateTable('proCenter.oa_shippingService');
        $this->batchInsert('proCenter.oa_shippingService', [
            'id','servicesName','type','site','ibayShipping'
        ], $list);*/

        //开发采购对应关系
       /* $pySql = "SELECT ruleName,ruleKey,ruleValue,ruleType FROM oa_sysRules";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        $this->truncateTable('proCenter.oa_sysRules');
        $this->batchInsert('proCenter.oa_sysRules', [
            'ruleName','ruleKey','ruleValue','ruleType'
        ], $list);*/


       //推广状态
        $pySql = "SELECT goodsinfo_id AS infoId,status,saler,createTime FROM oa_goodsinfo_extend_status";
       $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
       $this->truncateTable('proCenter.oa_goodsinfoExtendsStatus');
       $this->batchInsert('proCenter.oa_goodsinfoExtendsStatus', [
           'infoId','status','saler','createTime'
       ], $list);


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_021544_oa_wish_suffix cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190508_021544_oa_wish_suffix cannot be reverted.\n";

        return false;
    }
    */
}
