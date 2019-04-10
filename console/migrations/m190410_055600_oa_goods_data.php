<?php

use yii\db\Migration;

/**
 * Class m190410_055600_oa_goods_data
 */
class m190410_055600_oa_goods_data extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pySql = "SELECT TOP 10 cate,devNum,introducer,devStatus,checkStatus,createDate,updateDate,
                  img,subCate,vendor1,vendor2,vendor3,origin2,origin3,salePrice,hopeSale,hopeMonthProfit,
                  origin1,hopeRate,hopeWeight,developer,introReason,catNid,approvalNote,bGoodsid,hopeCost,stockUp,mineId FROM oa_goods";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        $lis = $this->batchInsert('proCenter.oa_goods', [
            'cate', 'devNum', 'introducer', 'devStatus', 'checkStatus', 'createDate', 'updateDate',
            'img', 'subCate', 'vendor1', 'vendor2', 'vendor3', 'origin2', 'origin3',
            'salePrice', 'hopeSale', 'hopeMonthProfit', 'origin1', 'hopeRate', 'hopeWeight', 'developer',
            'introReason', 'catNid', 'approvalNote', 'bGoodsid', 'hopeCost', 'stockUp', 'mineId'
        ], $list);
        //print_r($lis);exit;

    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190410_055600_oa_goods_data cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190410_055600_oa_goods_data cannot be reverted.\n";

        return false;
    }
    */
}
