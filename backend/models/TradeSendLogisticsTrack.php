<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 物流轨迹表 "trade_send_logistics_track".
 *
 * @property string $id
 * @property string $order_id
 * @property int $status
 * @property int $first_time
 * @property string $first_detail
 * @property int $newest_time
 * @property string $newest_detail
 * @property int $elapsed_time
 * @property int $stagnation_time
 */
class TradeSendLogisticsTrack extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trade_send_logistics_track';
    }

}