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
        $this->truncateTable('proCenter.oa_goods');
        $count = Yii::$app->py_db->createCommand("SELECT max(nid) as num from oa_goods")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++){
            $pySql = "SELECT nid,cate,devNum,introducer,devStatus,checkStatus,createDate,updateDate,
                  img,subCate,vendor1,vendor2,vendor3,origin2,origin3,salePrice,hopeSale,hopeMonthProfit,
                  origin1,hopeRate,hopeWeight,developer,introReason,catNid,approvalNote,bGoodsid,hopeCost,
                  CASE WHEN stockUp=1 THEN '是' ELSE '否' END AS stockUp,
                  mineId FROM oa_goods
                  WHERE nid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
            $this->batchInsert('proCenter.oa_goods', [
                'nid','cate', 'devNum', 'introducer', 'devStatus', 'checkStatus', 'createDate', 'updateDate',
                'img', 'subCate', 'vendor1', 'vendor2', 'vendor3', 'origin2', 'origin3',
                'salePrice', 'hopeSale', 'hopeMonthProfit', 'origin1', 'hopeRate', 'hopeWeight', 'developer',
                'introReason', 'catNid', 'approvalNote', 'bGoodsid', 'hopeCost', 'stockUp', 'mineId'
            ], $list);
        }

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
