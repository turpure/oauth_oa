<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 发货记录表 "trade_send".
 *
 * @property string $id
 * @property string $order_id
 * @property string $suffix
 * @property string $closingdate
 * @property string $track_no
 * @property string $logistic_name
 * @property float $total_weight
 * @property string $ack
 * @property string $shiptocountry_code
 * @property string $shiptocountry_name
 * @property string $transaction_type
 * @property string $store_name
 *
 */

class TradeSend extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trade_send';
    }

    public function getLogisticsTrack()
    {
        return $this->hasOne(TradeSendLogisticsTrack::className(),['order_id'=>'order_id']);
    }
}