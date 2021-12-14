<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 物流上网时效 trad_send_logistics_time_frame
 * @property string $logistic_company
 * @property string $logistic_type
 * @property string $logistic_name
 * @property string $closing_date
 * @property integer $total_num
 * @property integer $intraday_num
 * @property integer $first_num
 * @property integer $second_num
 * @property integer $third_num
 * @property integer $above_num
 * @property integer $intraday_ratio
 * @property integer $first_ratio
 * @property integer $second_ratio
 * @property integer $third_ratio
 * @property integer $above_ratio
 * @property integer $status
 */
class TradSendLogisticsTimeFrame extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trad_send_logistics_time_frame';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['logistic_company', 'logistic_name', 'logistic_type', 'closing_date', 'totol_num', 'second_num', 'third_num', 'not_find_num'], 'required'],
        ];

    }
}