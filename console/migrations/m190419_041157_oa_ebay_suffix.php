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
        $pySql = "SELECT ebayName,ebaySuffix,nameCode,mainImg,ibayTemplate,storeCountry, high,l.paypalName AS low
                  FROM (
                      SELECT es.*, h.paypalName AS high
                      FROM oa_ebay_suffix es
                      LEFT JOIN oa_ebay_paypal ep ON ep.ebayId=es.nid AND ep.mapType='high'
                      LEFT JOIN oa_paypal h ON ep.paypalId=h.nid 
                  ) aa  
                  LEFT JOIN oa_ebay_paypal epp ON epp.ebayId=aa.nid AND epp.mapType='low'
                  LEFT JOIN oa_paypal l ON epp.paypalId=l.nid 
                  ";
        $list = Yii::$app->py_db->createCommand($pySql)->queryAll();

        foreach ($list as $v){
            //$re = Yii::$app->db->createCommand("SELECT * FROM proCenter.oa_ebaySuffix WHERE ebayName='{$v['ebayName']}'")->queryOne();
            $high = \backend\models\OaPaypal::findOne(['paypal' => $v['high']])['id'];
            $low = \backend\models\OaPaypal::findOne(['paypal' => $v['low']])['id'];
            $this->update('proCenter.oa_ebaySuffix',['high' => $high, 'low' => $low],['ebaySuffix' => $v['ebaySuffix']]);
            //print_r($v);exit;
            /*if(!$re){
                $this->insert('proCenter.oa_ebaySuffix',  $v);
            }*/
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
