<?php

use yii\db\Migration;

/**
 * Class m190508_093010_oa_ebay_goods
 */
class m190508_093010_oa_ebay_goods extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {

        /*$this->truncateTable('proCenter.oa_ebayKeyword');
        $count = Yii::$app->db->createCommand("SELECT max(nid) as num from proCenter.oa_ebayGoods")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT s.goodsCode,s.goodsName
                FROM proCenter.oa_ebayGoods t
                LEFT JOIN proCenter.oa_goodsinfo s ON s.id=t.infoId
                WHERE s.goodsCode is not null t.nid BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->db->createCommand($pySql)->queryAll();
            //print_r($list);exit;
            $this->batchInsert('proCenter.oa_ebayKeyword', [
                'goodsCode', 'goodsName'
            ], $list);
        }*/




        //更新平均单价和重量 数据

        //$this->truncateTable('proCenter.oa_ebayGoodsSku');
        $count = Yii::$app->db->createCommand("SELECT max(id) AS num from proCenter.oa_ebayKeyword")->queryOne()['num'];
        $step = 400;
        $max = ceil($count/$step);
        for ($i = 0;$i<=$max;$i++) {
            $pySql = "SELECT goodsCode
                        FROM proCenter.oa_ebayKeyword WHERE id BETWEEN " . ($i*$step + 1) . " AND " . ($i + 1)*$step;
            $list = Yii::$app->db->createCommand($pySql)->queryAll();
            $codeArr = \yii\helpers\ArrayHelper::getColumn($list,'goodsCode');
            $codeStr = implode("','",$codeArr);
            $sql = "SELECT goodsCode,sum(goodsprice)/count(goodsCode) AS costPrice,round(sum(weight)/count(goodsCode),0) AS weight 
                    FROM Y_R_tStockingWaring WHERE goodsCode IN('{$codeStr}') GROUP BY goodsCode";
            $data = Yii::$app->py_db->createCommand($sql)->queryAll();
            foreach($data as $v){
                $this->update('proCenter.oa_ebayKeyword', $v, ['goodsCode' => $v['goodsCode']]);
            }
        }


    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_093010_oa_ebay_goods cannot be reverted.\n";

        return false;
    }

    /*
    // Use up()/down() to run migration code without a transaction.
    public function up()
    {

    }

    public function down()
    {
        echo "m190508_093010_oa_ebay_goods cannot be reverted.\n";

        return false;
    }
    */
}
