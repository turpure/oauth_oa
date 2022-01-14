<?php
/**
 * @desc PhpStorm.
 * @author: turpure
 * @since: 2021-10-08 16:50
 */

namespace backend\modules\v1\services;


use backend\models\EbayGroupLog;
use yii\db\Exception;

class EbayGroupDispatchService
{
    const TOTAL = 10;

    /**
     * 获取一个可以使用的分组名称
     */
    public static function getOneWorkGroup()
    {
        $sql = "select id,groupName from proCenter.oa_ebay_group_log where full = 0 and  datediff(createdTime,NOW()) =0  limit 1 ";
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->queryOne();
        if($ret) {
            return $ret;
        }

        else {
            static::getDispatchBatch();
            $ret = $db->createCommand($sql)->queryOne();
            return $ret;

        }
    }

    /**
     * 增加分组的分配数量， 更新分组状态
     */

    public static function addWorkGroupNumber($id)
    {
        $sql = "update proCenter.oa_ebay_group_log set currentNumber = currentNumber + 1, `full` = if((rate - currentNumber) <= 0,1,0)  where id=". $id  ;
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->execute();
        return $ret;

    }

    /**
     * 当前批次分组栈
     */
    public static function getDispatchBatch()
    {
        $existed = static::existedActiveBatch();
        if($existed) {
            return static::getActiveBatch();
        }
        else {
            static::generateNewBatch();
            return static::getActiveBatch();
        }
    }


    private static function getActiveBatch()
    {
        $sql = "select id,groupName,groupId,rate,currentNumber,batchNumber,totalNumber from proCenter.oa_ebay_group_log where datediff(createdTime,NOW()) =0  ";
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->queryAll();
        return $ret;
    }

    /**
     * 是否有活动批次
     */
    private static function existedActiveBatch()
    {
        $sql = "select count(id) number from proCenter.oa_ebay_group_log where datediff(createdTime,NOW()) =0 and  full = 0 ";
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->queryScalar();
        if ($ret == 0) {
            return false;
        }
        return true;
    }

    /**
     * 生成新批次
     */
    private static function generateNewBatch()
    {
        $sql = "select id,groupName,rate from proCenter.oa_group_rule where rate >0 ";
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->queryAll();
        $transaction = \Yii::$app->db->beginTransaction();
        try {
            $batchNumber = static::generateBatchNumber();
            foreach ($ret as $row) {
                $groupLog = new EbayGroupLog();
                $groupLog->groupId = $row['id'];
                $groupLog->groupName = $row['groupName'];
                $groupLog->rate = $row['rate'] * static::TOTAL;
                $groupLog->totalNumber = static::TOTAL;
                $groupLog->batchNumber = $batchNumber;
                $groupLog->currentNumber = 0;
                $groupLog->createdTime = date('Y-m-d H:i:s');
                if(!$groupLog->save()) {
                    throw new Exception("保存失败");
                }
            }
            $transaction->commit();
            return true;
        }

        catch (\Exception $why) {
            $transaction->rollBack();
            return false;
        }

    }

    /**
     * 生成批次号
     */ private static function generateBatchNumber()
    {
        $sql = "select max(batchNumber) from proCenter.oa_ebay_group_log ";
        $db = \Yii::$app->db;
        $ret = $db->createCommand($sql)->queryScalar();
        if($ret === null){
            return 1;
        }
        return $ret + 1;
    }

}
