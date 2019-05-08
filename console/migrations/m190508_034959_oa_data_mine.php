<?php

use yii\db\Migration;

/**
 * Class m190508_034959_oa_data_mine
 */
class m190508_034959_oa_data_mine extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp()
    {
        $pySql = "select top 10 id,proId,platForm,progress,creator,createTime,updateTime,detailStatus,cat,subCat,goodsCode,devStatus,mainImage,pyGoodsCode,infoId,spAttribute,isLiquid,isPowder,isMagnetism,isCharged from oa_data_mine ";
        $ret = Yii::$app->py_db->createCommand($pySql)->queryAll();
        foreach ($ret as $row) {
            $this->insert('proCenter.oa_dataMine', $row);
        }
//        ['id', 'proId', 'platForm', 'progress', 'creator', 'createTime', 'updateTime', 'detailStatus', 'cat', 'subCat', 'goodsCode', 'devStatus', 'mainImage', 'pyGoodsCode', 'infoId', 'spAttribute', 'isLiquid', 'isPowder', 'isMagnetism', 'isCharged'],
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown()
    {
        echo "m190508_034959_oa_data_mine cannot be reverted.\n";

        return false;
    }

}
