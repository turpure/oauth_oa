<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * 物流上网时效 trad_send_logistics_time_frame
 * @property string $logistic_company
 * @property string $logistic_type
 * @property string $logistic_name
 * @property string $closing_date
 * @property integer $totol_num
 * @property integer $first_num
 * @property integer $second_num
 * @property integer $third_num
 * @property integer $not_find_num
 *
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