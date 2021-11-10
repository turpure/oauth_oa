<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 妥投率 trad_send_succ_ratio
 * @property string $month
 * @property string $logistic_company
 * @property string $logistic_type
 * @property string $logistic_name
 * @property string $order_num
 * @property string $average
 * @property string $success_num
 * @property string $success_ratio
 * @property string $dont_succeed_num
 * @property string $dont_succeed_ratio
 */
class TradSendSuccRate extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trad_send_succ_ratio';
    }
}