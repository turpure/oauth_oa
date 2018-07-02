<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018-06-28
 * Time: 10:01
 */

namespace backend\modules\v1\models;

use Yii;

class ApiUpload
{

    /**
     * get the rate
     * @return array|false
     * @throws \yii\db\Exception
     */
    public static function getExchangeRate(){
        $sql = "SELECT * FROM Y_RateManagement";
        return Yii::$app->py_db->createCommand($sql)->queryOne();
    }
    /**
     * update rate
     * @param $condition
     * @return array
     * @throws \yii\db\Exception
     */
    public static function updateExchangeRate($condition)
    {
        $sql = "UPDATE Y_RateManagement SET salerRate='{$condition['salerRate']}',devRate='{$condition['devRate']}'";
        $res = Yii::$app->py_db->createCommand($sql)->execute();
        if ($res) {
            $result = [
                'code' => 400,
                'message' => 'Data update success！',
            ];
        } else {
            $result = [
                'code' => 400,
                'message' => 'Data update failed！',
            ];
        }
        return $result;
    }











}