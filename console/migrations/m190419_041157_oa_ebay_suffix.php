<?php

use yii\db\Migration;

/**
 * Class m190419_041157_oa_ebay_suffix
 */
class m190419_041157_oa_ebay_suffix extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pySql = "SELECT ebayName,ebaySuffix,nameCode,mainImg,ibayTemplate,storeCountry FROM oa_ebay_suffix";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        foreach ($list as $v){
            $re = Yii::$app->db->createCommand("SELECT * FROM proCenter.oa_ebaySuffix WHERE ebayName='{$v['ebayName']}'")->queryOne();
            //print_r($v);exit;
            if(!$re){
                $this->insert('proCenter.oa_ebaySuffix',  $v);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190419_041157_oa_ebay_suffix cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190419_041157_oa_ebay_suffix cannot be reverted.\n";

        return false;
    }
    */
}
