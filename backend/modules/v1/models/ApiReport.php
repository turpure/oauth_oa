<?php
/**
 * @desc PhpStorm.
 * @author: Administrator
 * @since: 2018-06-12 14:22
 */

namespace backend\modules\v1\models;

use Yii;
use yii\db\Query;
use yii\helpers\ArrayHelper;

class ApiReport
{
    /**
     * @brief sales profit report
     * @param $condition
     * @return array
     */

    public static function getSalesReport($condition)
    {
        $sql = 'meta_saleProfit @pingtai=:plat,@DateFlag=:dateFlag,@BeginDate=:beginDate,@endDate=:endDate,'.
        '@SalerAliasName=:suffix,@Saler=:seller,@StoreName=:storeName';
        $con = Yii::$app->py_db;
        $params = [
            ':plat' => $condition['plat'],
            ':dateFlag' => $condition['dateFlag'],
            ':beginDate' => $condition['beginDate'],
            ':endDate' => $condition['endDate'],
            ':suffix' => $condition['suffix'],
            ':seller' => $condition['seller'],
            ':storeName' => $condition['storeName']
        ];
        try {
            return $con->createCommand($sql)->bindValues($params)->queryAll();
        }
        catch (\Exception $why) {
            return [$why];
        }

    }

}