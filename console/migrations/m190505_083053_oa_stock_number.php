<?php

use yii\db\Migration;

/**
 * Class m190505_083053_oa_stock_number
 */
class m190505_083053_oa_stock_number extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pySql = "SELECT developer,number,orderNum,hotStyleNum,exuStyleNum,rate1,rate2,
                  stockNumThisMonth,stockNumLastMonth,createDate,isStock FROM oa_stock_goods_number";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        $lis = $this->batchInsert('proCenter.oa_stockGoodsNum', [
            'developer','number','orderNum','hotStyleNum','exuStyleNum','rate1','rate2',
            'stockNumThisMonth','stockNumLastMonth','createDate','isStock'
        ], $list);
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190505_083053_oa_stock_number cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190505_083053_oa_stock_number cannot be reverted.\n";

        return false;
    }
    */
}
