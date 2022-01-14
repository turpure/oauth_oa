<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 物流方式 "trade_send_logistics_company".
 *
 * @property string $id
 * @property string $name
 * @property string $type
 * @property string $level
 */

class TradeSendLogisticsCompany extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trade_send_logistics_company';
    }

}