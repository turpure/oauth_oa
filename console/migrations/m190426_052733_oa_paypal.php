<?php

use yii\db\Migration;

/**
 * Class m190426_052733_oa_paypal
 */
class m190426_052733_oa_paypal extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pySql = "SELECT paypalName,GETDATE() AS createDate FROM oa_paypal";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();
        foreach ($list as $v){
            $re = Yii::$app->db->createCommand("SELECT * FROM proCenter.oa_paypal WHERE paypalName='{$v['paypalName']}'")->queryOne();
            //print_r($v);exit;
            if(!$re){
                $this->insert('proCenter.oa_paypal',  $v);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190426_052733_oa_paypal cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190426_052733_oa_paypal cannot be reverted.\n";

        return false;
    }
    */
}
