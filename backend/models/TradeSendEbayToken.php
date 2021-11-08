<?php

namespace backend\models;

use yii\db\ActiveRecord;

/**
 * ebay token "trade_send_ebay_token".
 *
 * @property string $id
 * @property string $ebay_id
 * @property string $token
 * @property integer $expire_date
 * @property integer $status
 *
 */
class TradeSendEbayToken extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'trade_send_ebay_token';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['ebay_id', 'token', 'expire_date', 'status'], 'required'],
        ];
    }
}