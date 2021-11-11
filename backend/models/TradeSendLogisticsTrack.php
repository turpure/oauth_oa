<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 物流轨迹表 "trade_send_logistics_track".
 *
 * @property string $id
 * @property string $order_id
 * @property string $track_no
 * @property string $logistic_name
 * @property integer $logistic_type
 * @property integer $closing_date
 * @property int $status
 * @property int $first_time
 * @property string $first_detail
 * @property int $newest_time
 * @property string $newest_detail
 * @property int $elapsed_time
 * @property int $stagnation_time
 * @property string $track_detail
 * @property int $icount
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
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
        ];

    }
}